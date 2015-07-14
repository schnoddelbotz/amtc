<?php
/*
 * lib/amtcwebSpooler.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * wrapper class for interacting with amtc/-web via cli.
 * for scanning/logging, scheduled jobs - (via a single cronjob)
 */

class AmtcwebSpooler {
  static $now_wday;       // inited by CLI_main, current week day
  static $now_wday_mask;  // bitmask for current weekday
  static $now_hours;      // current time (hours)
  static $now_minutes;    // current time (minutes)
  static $now_hm;         // 60*now_hours + now_minutes
  static $now_unix0;      // today at 00:00 as unix timestamp

  static $shortOptions = Array(
    'l' => 'Loop 10 times, sleeping 5 seconds',
    'n' => 'no-action: Just show what would be done',
    'v' => 'verbose: tell what happens',
    'd' => 'debug: even more verbose',
    'V' => 'Version: of amtc-web'
  );
  static $longOptions = Array(
    'onlyScheduled'   => 'only display/apply on scheduled jobs',
    'onlyMonitoring'  => 'only display/apply on the monitoring job',
    'onlyInteractive' => 'only display/apply on interactive jobs',
    'backlog'         => '[listJobs] hours back in history to include [1 h]',
    'limitOU'         => 'limit action to given OU (by ID or name)',
  );
  static $actions = Array(
    'listJobs' => 'List jobs - only active/pending by default',
    'runJobs'  => 'Check for pending jobs - execute in order if pending'
  );
  static $descriptorspec = array(
   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
   2 => array("file", "/dev/null", "a") // stderr is a file to write to
  );


  static function cliMain($args)  {
    // CLI commands only allowed from CLI context...
    if (PHP_SAPI!=='cli')
      throw new Exception("CLI only.");

    $now = getdate();
    self::$now_wday      = $now['wday'];
    self::$now_wday_mask = pow(2,$now['wday']);
    self::$now_hours     = $now['hours'];
    self::$now_minutes   = $now['minutes'];
    self::$now_hm        = $now['hours'] * 60 + $now['minutes'];
    self::$now_unix0     = mktime(0,0,0,$now['mon'],$now['mday'],$now['year']);
    $options = getopt(join(array_keys(self::$shortOptions)),
                      array_keys(self::$longOptions));
    $actionPattern = '/^(' . implode('|', array_keys(self::$actions)) . ')$/';

    // php's getopt doesn't tell us which part of $argv were non-options... so:
    if ($match = preg_grep($actionPattern, $args)) {
      // run matched action method
      $action = array_shift($match);
      if (isset($options['l']/*[l]oop*/)) {
        for ($i=0; $i<5; $i++) {
          self::$action($options);
          sleep(10);
          // this polling sucks a bit ... improve, later...
        }
      } else {
        self::$action($options);
      }
    } else {
      // bad usage, show help
      self::showUsage();
    }
  }

  static function showUsage() {
    echo "\n # amtc-web.phpsh - part of amtc-web, part of amtc\n";
    echo " # https://github.com/schnoddelbotz/amtc\n";
    echo "\n Usage:\n  amtc-web.phpsh [short and long options] <action>\n";
    $sections = Array('shortOptions'=>Array(4,1),'longOptions'=>Array(15,2),'actions'=>Array(10,0));
    // produce formatted output of $shortOptions, $longOptions and $actions
    foreach ($sections as $section=>$sd) {
      echo "\n $section:\n";
      foreach (self::$$section as $k=>$v)
        printf("  ".str_repeat('-', $sd[1])."%-".$sd[0]."s  %s\n",$k,$v);
    }
    echo "\n";
  }

  static function stringifyDayMinute($min) {
    $h = intval($min/60);
    return sprintf('%2d:%02d', $h, $min-($h*60));
  }

  static function listJobs($opt) {
    echo "\n";
    $jobs = Model::factory('Job');
    $jobFormat = "%6d  %s%s%s%s%s%s%s %12s %8s %8s %6s %13s  %s\n";
    echo " JobId  MTWTFSS     Job type  Command   Status  Start      Last run  Description\n";
    echo "------ -------- ------------ -------- -------- ------ ------------- ------------\n";
    isset($opt['onlyInteractive']) && $jobs->where('job_type', Job::JOB_INTERACTIVE);
    isset($opt['onlyScheduled'])   && $jobs->where('job_type', Job::JOB_SCHEDULED);
    isset($opt['onlyMonitoring'])  && $jobs->where('job_type', Job::JOB_MONITORING);
    foreach ($jobs->find_many() as $record) {
      printf($jobFormat,  $record->id,
                          $record->repeat_days & 2  ? 'x' : '-', // monday
                          $record->repeat_days & 4  ? 'x' : '-',
                          $record->repeat_days & 8  ? 'x' : '-',
                          $record->repeat_days & 16 ? 'x' : '-',
                          $record->repeat_days & 32 ? 'x' : '-',
                          $record->repeat_days & 64 ? 'x' : '-', // sat
                          $record->repeat_days & 1  ? 'x' : '-', // sun
                          Job::$jobTypeMap[$record->job_type],
                          $record->amtc_cmd,
                          Job::$jobStatusMap[$record->job_status],
                          self::stringifyDayMinute($record->start_time),
                          $record->last_started ? date("M d H:i",$record->last_started) : 'n/a',
                          $record->description);
    }
    echo "\n";
  }

  static function runJobs($opt) {
    $jobs = Job::order_by_asc('job_type')->where('job_status',Job::STATUS_PENDING);

    isset($opt['onlyInteractive']) && $jobs->where('job_type', Job::JOB_INTERACTIVE);
    isset($opt['onlyScheduled'])   && $jobs->where('job_type', Job::JOB_SCHEDULED);
    isset($opt['onlyMonitoring'])  && $jobs->where('job_type', Job::JOB_MONITORING);

    $j=Array();
    foreach ($jobs->find_many() as $job) {
      // skip non-interactive jobs that don't have to run today; better do it in SQL?
      if ( ($job->job_type == Job::JOB_SCHEDULED || $job->job_type == Job::JOB_MONITORING)  &&
            ! (self::$now_wday_mask & $job->repeat_days) ) {
        continue;
      }
      // skip sched jobs if daytime not reached yet ... or already run (only once / day)
      if ($job->job_type == Job::JOB_SCHEDULED && (self::$now_hm < $job->start_time || $job->last_done > self::$now_unix0 )) {
        continue;
      }
      // skip sched jobs if should have run way earlier today (e.g. cfg change...)
      if ($job->job_type == Job::JOB_SCHEDULED && $job->start_time + 30 < self::$now_hm) {
        continue;
      }
      // skip monitoring if this script was re-executed below repeat_interval minutes
      if ($job->job_type == Job::JOB_MONITORING && time() < $job->last_started + $job->repeat_interval * 60 ) {
        continue;
      }
      $j[] = $job;
    }

    foreach ($j as $job) {
      self::execJob($job,$opt);
    }
  }

  static function execJob($job,$opt) {
    if (isset($opt['n'])) {
      echo "Dry-Run-Skip execJob: ".$job->id.": ".$job->description."\n";
      return;
    }
    switch ($job->job_type) {
      case Job::JOB_INTERACTIVE:
        $job->job_status = Job::STATUS_RUNNING;
        $job->last_started = time();
        $job->save();
        self::execAmtCommand($job,$opt);
        $job->last_done = time();
        $job->job_status = Job::STATUS_DONE;
        $job->save();
        // how to trigger increased monitoring until job completed?
        if ($notification = Notification::create()) {
          $notification->message = "Job $job->id completed";
          $notification->ntype = "envelope";
          $notification->user_id = 2; // hardcoded spooler user
          $notification->save();
        }
      break;
      case Job::JOB_SCHEDULED:
        $job->job_status = Job::STATUS_RUNNING;
        $job->last_started = time();
        $job->save();
        self::execAmtCommand($job,$opt);
        $job->last_done = time();
        $job->job_status = Job::STATUS_PENDING;
        $job->save();
        if ($notification = Notification::create()) {
          $notification->message = "$job->description completed";
          // choose powerup-powerdown icons here! use execAmtCommand retval?
          switch ($job->amtc_cmd) {
            case 'U': $notification->ntype = 'toggle-on';  break;
            case 'D': $notification->ntype = 'toggle-off'; break;
            default:  $notification->ntype = 'envelope';
          }
          $notification->user_id = 2; // hardcoded spooler user
          $notification->save();
        }
      break;
      case Job::JOB_MONITORING;
        // find OUs that have monitoring enabled, join by optionset_id
        self::execMonitoringJob($job,$opt);
      break;
    }
    # sendNotification  -> notification 'powered up x in y seconds'
  }

  static function execMonitoringJob(Job $job,$opt) {
    // fetch last state of all hosts
    $laststates = Array();
    foreach( Laststate::find_many() as $host ) {
      $laststates[$host->hostname] = $host->as_array();
    }
    //
    $optsetgroup = Array();
    $job->last_started = time();
    $job->job_status = Job::STATUS_RUNNING;
    $job->save();
    foreach (Ou::where('logging',1)->find_many() as $ou) {
      $optsetgroup[$ou->optionset_id][] = $ou->id;
    }
    // now exec amtc -I ... on each group of hosts with same optionset
    foreach ($optsetgroup as $optsetid=>$ou_array) {
      $hosts = Array();
      foreach ($ou_array as $ou_id) {
        $hosts = array_merge($hosts, self::getOuHosts($ou_id,true));
      }
      $maxThreads = 180; // This should be a global config option!
      if (count($hosts) < $maxThreads) {
        $job->amtc_hosts = implode(',', $hosts);
        $job->ou_id = $ou_array[0]; // one OU setting fits all here, as it's the same...
        self::updateHostState( self::execAmtCommand($job,$opt), $opt, $laststates );
      } else {
        // more than maxThreads hosts to scan, slice hosts array
        $hostsCompleted = 0;
        while ($hostsCompleted<count($hosts)) {
          $workpack = array_slice($hosts, $hostsCompleted, $maxThreads);
          $hostsCompleted += count($workpack);
          $job->amtc_hosts = implode(',', $workpack);
          $job->ou_id = $ou_array[0]; // one OU setting fits all here, as it's the same...
          self::updateHostState( self::execAmtCommand($job,$opt), $opt, $laststates );
        }
      }
    }
    $job->last_done = time();
    $job->job_status = Job::STATUS_PENDING;
    $job->ou_id = NULL;
    $job->amtc_hosts = NULL;
    $job->save();
  }

  // construct amtc binary command line options based on job
  static function buildAmtcCommandline(Job $job) {
    $ou        = Ou::find_one($job->ou_id);
    $optionset = Optionset::find_one($ou->optionset_id);

    // - throw error if optionset is nil (fresh install...)
    // - maxThreads was a per-optionset setting, but should be global/config

    $cmd_opts = Array('-j'); // amtc shall always produce parsable json output
    $optionset->sw_scan22       && $cmd_opts[] = '-s';
    $optionset->sw_scan3389     && $cmd_opts[] = '-r';
    $optionset->sw_v5           && $cmd_opts[] = '-5';
    $optionset->sw_dash         && $cmd_opts[] = '-d';
    $optionset->sw_usetls       && $cmd_opts[] = '-g';
    $optionset->sw_skiptcertchk && $cmd_opts[] = '-n';
    $optionset->opt_timeout     && $cmd_opts[] = '-t '.$optionset->opt_timeout;
    $optionset->opt_passfile    && $cmd_opts[] = '-p '.$optionset->opt_passfile;
    $optionset->opt_cacertfile  && $cmd_opts[] = '-c '.$optionset->opt_cacertfile;
    $job->amtc_delay            && $cmd_opts[] = '-w '.$job->amtc_delay;

    // decide whether to act on whole OU or a given list of hosts
    if ($job->amtc_hosts) {
      $hosts = self::resolveHostIds($job->amtc_hosts);
    } else {
      $hosts = self::getOuHosts($job->ou_id);
    }

    $cmd = sprintf('%s %s -%s %s', AMTC_BIN, implode(' ',$cmd_opts), $job->amtc_cmd, $hosts);
    return $cmd;
  }

  // execute AMTC command, log results
  static function execAmtCommand(Job $job,$opt) {
    $cmd = self::buildAmtcCommandline($job);

    isset($opt['d']) && print("[debug] execAmtCommand for job #$job->id: $cmd\n");
    if (isset($opt['n'])) {
      echo "SKIPPING as -n(o action) flag was given:\n  $cmd\n";
      return Array();
    }

    $cwd = '/tmp';
    $env = array();
    $process = proc_open($cmd, self::$descriptorspec, $pipes, $cwd, $env);
    if (is_resource($process)) {
      $res = stream_get_contents($pipes[1]);
      fclose($pipes[0]);
      fclose($pipes[1]);
      $retval = proc_close($process);
    }
    if ($retval!=0) {
      fwrite(STDERR,"ABORTING at job #$job->id! Fatal error #$retval returned by amtc: $res");
      return Array();
    }

    // decode amtc json output
    return json_decode($res);
  }

  static function updateHostState($data /* = amtc json_decoded output */,$opt,$last/*states*/) {
    // map amtc string output to db-usable open_port(int) value
    $rportmap = array('ssh'=>22, 'rdp'=>3389, 'none'=>0, 'skipped'=>0, 'noscan'=>0);
    // map hostname -> id (amtc has no clue of those IDs...); improve...
    $hostnameMap = Array();
    foreach (Host::find_many() as $host) {
      $hostnameMap[$host->hostname] = $host->id;
    }
    // compare current and last, update laststate where needed
    foreach ($data as $host=>$hostnow) {
      if (!isset($last[$host])) {
        isset($opt['v']) && printf("NEW %s: initially set [%d|%d|%d] (%s)\n", $host,
                                $hostnow->amt, $hostnow->http, $rportmap[$hostnow->oport], $hostnow->msg);
        // Insert into statelog/history table
        $r = Statelog::create();
        $r->state_amt  = $hostnow->amt;
        $r->state_http = $hostnow->http;
        $r->host_id    = $hostnameMap[$host];
        $r->open_port  = $rportmap[$hostnow->oport];
        $r->save();
        // Insert into current-state/laststate table
        $x = Laststate::create();
        $x->state_amt  = $hostnow->amt;
        $x->state_http = $hostnow->http;
        $x->host_id    = $hostnameMap[$host];
        $x->hostname   = $host;
        $x->state_begin= time();
        $x->open_port  = $rportmap[$hostnow->oport];
        $x->save();
        // save $hostnow->msg here!
      } elseif ( $hostnow->amt  != $last[$host]['state_amt'] ||
                 $hostnow->http != $last[$host]['state_http'] ||
                 $rportmap[$hostnow->oport] != $last[$host]['open_port'] ) {
        isset($opt['v']) && printf("UPD %s: set [%d|%d|%d] (%s), was [%d|%d|%d]\n", $host,
                                $hostnow->amt, $hostnow->http, $rportmap[$hostnow->oport], $hostnow->msg,
                                $last[$host]['state_amt'], $last[$host]['state_http'], $last[$host]['open_port'] );
        // actually the same as for a new record... to keep record.
        $r = Statelog::create();
        $r->state_amt  = $hostnow->amt;
        $r->state_http = $hostnow->http;
        $r->host_id    = $hostnameMap[$host];
        $r->open_port  = $rportmap[$hostnow->oport];
        $r->save();
        $x = Model::factory('Laststate')->where('host_id',$hostnameMap[$host])->find_one();
        $x->state_amt  = $hostnow->amt;
        $x->state_http = $hostnow->http;
        $x->host_id    = $hostnameMap[$host];
        $x->hostname   = $host;
        $x->state_begin= time();
        $x->open_port  = $rportmap[$hostnow->oport];
        $x->save();
        // save $hostnow->msg here!
      } else {
        // this should happen most of the time: no change. only tell in -debug mode.
        isset($opt['d']) && printf("0CH %s: still [%d|%d|%d] (%s)\n", $host,
                                $hostnow->amt, $hostnow->http, $rportmap[$hostnow->oport], $hostnow->msg);
      }
    }
  }

  // turn ,-separated string list of host IDs to space-separated string list of hostnames
  static function resolveHostIds($ids) {
    $hosts = Array();
    foreach (Host::where_id_in(explode(',',$ids))->find_many() as $host) {
      $hosts[] = $host->hostname;
    }
    return implode(' ', $hosts);
  }

  static function getOuHosts($ouid, $getIdArray=false) {
    $hosts = Array();
    $ids = Array();
    foreach (Host::where('ou_id', $ouid)->find_many() as $host) {
      $hosts[] = $host->hostname;
      $ids[] = $host->id;
    }
    if ($getIdArray)
      return $ids;
    return implode(' ', $hosts);
  }

  static function getWeekdayBitmask($weekdayNumber) {
    // weekdayNumber as wday in http://php.net/manual/en/function.getdate.php
    return pow(2, $weekdayNumber);
  }

}
