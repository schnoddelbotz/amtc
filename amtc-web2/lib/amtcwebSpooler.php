<?php
/*
 * lib/amtcwebSpooler.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * wrapper class for interacting with amtc/-web via cli.
 * for scanning/logging, scheduled jobs - (via a single cronjob)
 */

class amtcwebSpooler {

  static $shortOptions = Array(
    'l' => 'Loop 10 times, sleeping 5 seconds',
    'n' => 'no-action: Just show what would be done',
    'v' => 'verbose: tell what happens',
    'V' => 'Version: of amtc-web'
  );
  static $longOptions = Array(
    'listScheduled'   => '[listJobs] only display scheduled jobs',
    'listMonitoring'  => '[listJobs] only display the monitoring job',
    'listInteractive' => '[listJobs] only display interactive jobs',
    'backlog'         => '[listJobs] hours back in history to include [1 h]',
    'limitOU'         => 'limit action to given OU (by ID or name)',
  );
  static $actions = Array(
    'listJobs' => 'List jobs - only active/pending by default',
    'runJobs'  => 'Check for pending jobs - execute in order if pending'
  );


  static function CLI_main()  {
    // CLI commands only allowed from CLI context...
    PHP_SAPI=='cli' || die("CLI only.");

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
    isset($opt['listInteractive']) && $jobs->where('job_type',1);
    isset($opt['listScheduled'])   && $jobs->where('job_type',2);
    isset($opt['listMonitoring'])  && $jobs->where('job_type',3);
    foreach ($jobs->find_many() as $record) {
      $r = $record->as_array();
      echo "job: ". $record->description. " ... more to come\n";
    }
  }

  static function runJobs($opt) {
    echo "Running jobs --- -n(o-action) / test run: ".(isset($opt['n'])?'Yes':'NO!')."\n";
    $result = array();
    foreach (Job::find_many(/* .... */) as $record) {
      $r = $record->as_array();
      $result[] = $r;
    }
    #print_r($result);
    /*
      tbd
      # Job::find ( pending, order by type -interactive first-, only ONE )
      # Job::join ( same action and optionset ^ bites with ONE )
      # Job::exec (
        foreach $jobTypes () {
         foreach ($optionSet)
            execSinglejob($jobType, $jobAction, $optionSet, $hostList)
            jobs::saveResultsToDB
            jobs::logAction
        }
       )
      # sendNotification  -> notification 'powered up x in y seconds'
    */
  }

  static function getWeekdayBitmask($weekdayNumber) {
    // weekdayNumber as wday in http://php.net/manual/en/function.getdate.php
    return pow(2, $weekdayNumber);
  }

}
