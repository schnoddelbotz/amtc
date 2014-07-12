<?php
/*
 * class.amtc.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * a simple class to interact with amtc command line tool
 */

namespace amtcweb;

class amtc {

  public static $amtc_err = array('OK','Too many hosts', 'Bad usage',
                    'No AMT_PASSWORD set', 'Error opening password file');

  public static $descriptorspec = array(
   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
   2 => array("file", "/dev/null", "a") // stderr is a file to write to
  );

  static function run($cmd, $opts, $hostlist) {
    $cmd = sprintf("%s -vv -p %s %s %s %s >>/tmp/amtc.log",
             AMTC_EXE, AMT_PW_FILE, "-".$cmd, $opts, $hostlist);
    return `$cmd`;
  }

  static function getRoomState($roomName) {
    $r = Room::getRoom($roomName);
    if (!$r)
      return;
    $amtc_opts = $r['amt_version'] >= 8 ? '-d' : '';
    if ($r['amt_version'] == 5)
      $amtc_opts = '-5';
    if ($r['amt_version'] > 9)
      $amtc_opts = '-dgn';
    $cmd = sprintf("%s %s %s -p %s %s",
             AMTC_EXE,$amtc_opts, AMTC_OPTS, AMT_PW_FILE, implode(' ',$r['hosts']));
    $retval = 0;

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
      throw new \Exception("amtc reported error ".$retval.": ".self::$amtc_err[$retval]);
    }
    
    // urgh. first intension behind amtc's -j option was to pass its json output
    // directly through. now we decode its output here... well.
    return FrontendCtrl::createResponse(true,json_decode($res),'getRoomState success');
  }

}
