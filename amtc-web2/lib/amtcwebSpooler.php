<?php
/*
 * lib/amtcwebSpooler.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * wrapper class for interacting with amtc/-web via cli.
 * for scanning/logging, scheduled jobs - (via a single cronjob)
 */

class amtcwebSpooler {

  const JOB_INTERACTIVE = 1;
  const JOB_SCHEDULED   = 2;
  const JOB_MONITORING  = 3;

  const STATUS_PENDING  = 0;
  const STATUS_RUNNING  = 1;
  const STATUS_DONE     = 2;
  const STATUS_ERROR    = 9;

  static $now_wday;       // inited by CLI_main, current week day
  static $now_wday_mask;  // bitmask for current weekday
  static $now_hours;      // current time (hours)
  static $now_minutes;    // current time (minutes)

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


  static function CLI_main()  {
    // CLI commands only allowed from CLI context...
    PHP_SAPI=='cli' || die("CLI only.");

    $now = getdate();
    self::$now_wday      = $now['wday'];
    self::$now_wday_mask = pow(2,$now['wday']);
    self::$now_hours     = $now['hours'];
    self::$now_minutes   = $now['minutes']; 
    $options = getopt(join(array_keys(self::$shortOptions)),
                      array_keys(self::$longOptions));
    $actionPattern = '/^(' . implode('|', array_keys(self::$actions)) . ')$/';

    // php's getopt doesn't tell us which part of $argv were non-options... so:
    global $argv;
    if ($match = preg_grep($actionPattern, $argv)) {
      // run matched action method
      $action = array_shift($match);
      self::$action($options);
    } else {
      // bad usage, show help
      self::showUsage();
    }
  }

  static function showUsage() {
    echo "\n # amtc-web.phpsh - part of amtc-web, part of amtc\n";
    echo " # https://github.com/schnoddelbotz/amtc\n";
    echo "\n Usage:\n  amtc-web.phpsh [short and long options] <action>\n";
    $sections = ['shortOptions'=>[4,1],'longOptions'=>[15,2],'actions'=>[10,0]];
    // produce formatted output of $shortOptions, $longOptions and $actions
    foreach ($sections as $section=>$sd) {
      echo "\n $section:\n";
      foreach (self::$$section as $k=>$v)
        printf("  ".str_repeat('-', $sd[1])."%-".$sd[0]."s  %s\n",$k,$v);
    }
    echo "\n";
    exit(0);
  }

  static function listJobs($opt) {
    echo "\nListing jobs:\n\n";
    $jobs = Model::factory('Job');
    isset($opt['onlyInteractive']) && $jobs->where('job_type', self::JOB_INTERACTIVE);
    isset($opt['onlyScheduled'])   && $jobs->where('job_type', self::JOB_SCHEDULED);
    isset($opt['onlyMonitoring'])  && $jobs->where('job_type', self::JOB_MONITORING);
    foreach ($jobs->find_many() as $record) {
      $r = $record->as_array();
      echo "job: ". $record->description. " ... more to come\n";
    }
  }

  static function runJobs($opt) {
    //echo "Running jobs --- -n(o-action) / test run: ".(isset($opt['n'])?'Yes':'NO!')."\n";
    $jobs = Job::order_by_asc('job_type')->where('job_status',self::STATUS_PENDING);
    
    isset($opt['onlyInteractive']) && $jobs->where('job_type', self::JOB_INTERACTIVE);
    isset($opt['onlyScheduled'])   && $jobs->where('job_type', self::JOB_SCHEDULED);
    isset($opt['onlyMonitoring'])  && $jobs->where('job_type', self::JOB_MONITORING);

    foreach ($jobs->find_many() as $job) {
      // skip non-interactive jobs that don't have to run today; better do it in SQL?
      if ( ($job->job_type == self::JOB_SCHEDULED || $job->job_type == self::JOB_MONITORING)  &&
            ! (self::$now_wday_mask & $job->repeat_days) ) {
        continue;
      }
      $j[] = $job;
    }

    // merge same-optionset-same-command-same-time MONITORING jobs here;
    // resolve higher-level OUs to contained rooms.
    // ...
    foreach ($j as $job) {
      amtcwebSpooler::execJob($job,$opt);
    }
  }

  static function execJob($job,$opt) {
    switch ($job->job_type) {
      case amtcwebSpooler::JOB_INTERACTIVE:
        //echo "execJob INTERACTIVE PROCESS: ".$job->id."  ".$job->description."\n";
        #self::execAmtCommand($job,$opt);
      break;
      case amtcwebSpooler::JOB_SCHEDULED:
        //echo "SKIP execJob SCHEDULED   PROCESS: ".$job->id."  ".$job->description."\n";
        #self::execAmtCommand($job,$opt);
      break;
      case amtcwebSpooler::JOB_MONITORING;        
        // find OUs that have monitoring enabled, join by optionset_id
        $optsetgroup = [];
        foreach (Ou::where('logging',1)->find_many() as $ou) {
          $optsetgroup[$ou->optionset_id][] = $ou->id;
        }
        // now exec amtc -I ... on each group of hosts with same optionset
        foreach ($optsetgroup as $optsetid=>$ou_array) {          
          $hosts = [];
          foreach ($ou_array as $ou_id) {
            $hosts = array_merge($hosts, self::getOuHosts($ou_id,false,true));
          }
          // FIXME:  only take $optionSet->maxThreads ... while ...
          $job->amtc_hosts = implode(',', $hosts);
          $job->ou_id = $ou_id; // one OU setting fits all here, as it's the same...
          self::execAmtCommand($job,$opt);
        }
      break;
    }
    # sendNotification  -> notification 'powered up x in y seconds'
  }

  // construct amtc binary command line options based on job
  static function buildAmtcCommandline($job,$opt) {
    $ou        = Ou::find_one($job->ou_id);
    $optionset = Optionset::find_one($ou->optionset_id);

    $cmd_opts = ['-j']; // amtc shall always produce parsable json output
    $optionset->sw_scan22       && $cmd_opts[] = '-s';
    $optionset->sw_scan3389     && $cmd_opts[] = '-r';
    $optionset->sw_v5           && $cmd_opts[] = '-5';
    $optionset->sw_dash         && $cmd_opts[] = '-d';
    $optionset->sw_usetls       && $cmd_opts[] = '-g';
    $optionset->sw_skiptcertchk && $cmd_opts[] = '-n';
    $optionset->opt_timeout     && $cmd_opts[] = '-t '.$optionset->opt_timeout;
    $optionset->opt_maxthreads  && $cmd_opts[] = '-m '.$optionset->opt_maxthreads;
    $optionset->opt_passfile    && $cmd_opts[] = '-p '.$optionset->opt_passfile;
    $optionset->opt_cacertfile  && $cmd_opts[] = '-c '.$optionset->opt_cacertfile;
    $job->amtc_delay            && $cmd_opts[] = '-w '.$job->amtc_delay;
    #$job->amtc_bootdevice      && $cmd_opts[] = '-b '.$job->amtc_bootdevice;
    
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
  static function execAmtCommand($job,$opt) {
    $rportmap = array('ssh'=>22, 'rdp'=>3389, 'none'=>0, 'skipped'=>0);
    $cmd = self::buildAmtcCommandline($job,$opt);
    
    if (isset($opt['n'])) {
      echo "WOULD EXEC: $cmd\n";
      return;
    }

    // REALLY DO IT!
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
      fwrite(STDERR,"ABORTING at Job #$job->id! Error #$retval returned by amtc: $res");
      exit($retval);
    }

    // decode amtc json output
    $data = json_decode($res);
    // fetch last state of all hosts
    $last = [];
    foreach( Laststate::find_many() as $host ) {
      $last[$host->hostname] = $host->as_array();
    }
    // map hostname -> id (amtc has no clue of those IDs...); improve...
    $hostnameMap = [];
    foreach (Host::find_many() as $host) {
      $hostnameMap[$host->hostname] = $host->id;
    }
    // compare current and last, update laststate where needed
    foreach ($data as $host=>$hostnow) {
      if (!isset($last[$host])) {
        isset($opt['v']) && printf("NEW %s: initially set [%d|%d|%d] (%s)\n", $host,
                                $hostnow->amt, $hostnow->http, $rportmap[$hostnow->oport], $hostnow->msg);
        $r = Statelog::create();
        $r->state_amt  = $hostnow->amt;
        $r->state_http = $hostnow->http;
        $r->host_id    = $hostnameMap[$host];
        $r->open_port  = $rportmap[$hostnow->oport];
        $r->save();
        // save $hostnow->msg here!
      } elseif ( $hostnow->amt  != $last[$host]['state_amt'] ||
                 $hostnow->http != $last[$host]['state_http'] ||
                 $rportmap[$hostnow->oport] != @$rportmap[$last[$host]['open_port']] ) {
        isset($opt['v']) && printf("UPD %s: set [%d|%d|%d] (%s), was [%d|%d|%d]\n", $host,
                                $hostnow->amt, $hostnow->http, $rportmap[$hostnow->oport], $hostnow->msg,
                                $last[$host]['state_amt'], $last[$host]['state_http'],@$rportmap[$last[$host]['open_port']]);
        // actually the same as for a new record... to keep record.
        $r = Statelog::create();
        $r->state_amt  = $hostnow->amt;
        $r->state_http = $hostnow->http;
        $r->host_id    = $hostnameMap[$host];
        $r->open_port  = $rportmap[$hostnow->oport];
        $r->save();        
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
    $hosts = [];
    foreach (Host::where_id_in(explode(',',$ids))->find_many() as $host) {
      $hosts[] = $host->hostname;
    }
    return implode(' ', $hosts);    
  }

  static function getOuHosts($ouid, $recursive=false, $getIdArray=false) {
    $hosts = [];
    $ids = [];
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
