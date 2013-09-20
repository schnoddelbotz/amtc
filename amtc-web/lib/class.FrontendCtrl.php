<?php
/*
 * class.FrontendCtrl.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * a frontend controller class that does request sanitizing,
 * parsing and routing to static class methods. quick, dirty.
 */

namespace amtcweb;

class FrontendCtrl {

  private $cfg;
  private $actionClass = 'FrontendCtrl';
  private $actionMethod = 'usage';
  public $args = Array();
  public $sitecfg;
  private $content = Array();
  public $outputMode = 'ajax';
  private $isAdmin = false;
  
  /* constructor. store router configuration, apply config defaults */
  /* also defines cfg constants as used by static class methods */
  function __construct($cfg,$sitecfg=Array(),$sitecfg_defaults) {
    $script = basename($_SERVER['SCRIPT_FILENAME']);
    $this->cfg = $cfg;
    $this->sitecfg = $sitecfg;
    $this->isAdmin = ($script=='admin.php')?true:false;
    foreach ($sitecfg_defaults as $defname=>$defval) {
      if (!isset($this->sitecfg[$defname]))
        $this->sitecfg[$defname] = $defval;
    }

    foreach ($this->sitecfg as $k=>$v)
      define(strtoupper($k), $v);

    if (PHP_SAPI=='cli')
      $this->outputMode = 'cli'; // FIXME: switch to csv by default? 
                                 // (success)             #SUCCESS
                                 // (usermsg)             #Message:
                                 // (keys data)           #roomname,roomid,hostlist
                                 // (values data),csv     #hgg1,32,shgg01 shgg02
                                 // + -getColumns=roomname[,has_amt...] cmdline opt
                                 // + -quiet=1?
    else
      $this->outputMode = 'ajax'; 
  }
  
  /* call the static (action-)method requested including args */
  function processRequest() {
    $method = "\\amtcweb\\$this->actionClass::$this->actionMethod";
    return call_user_func_array($method, $this->args);
  }

  /* produce content for config/admin section, passthru to AmtcWebSetup */
  private function config($mode) {
    $method = "\\amtcweb\\AmtcWebSetup::config_$mode";
    // pass $this by ref to let $method influence $this->outputMode (and access sitecfg)
    return call_user_func_array($method, array(&$this));
  }

  /* return dumbly rendered content, depending on $this->outputMode */
  function renderContent($content) {
    //print_r($content);
    switch ($this->outputMode) {
      case 'cli':  
        if ($content['success']===true) {
          $c  = $content['usermsg']."\n";
          $c .= print_r($content['data'],1);
        } else  {
          $c = $content['usermsg']."\n";
        }
      break;
      case 'ajax': 
          $c = json_encode($content); 
          header('Content-type: application/json'); 
      break;
      case 'raw': 
          $c = $content; 
      break;
      default: 
          throw new \Exception("Unexpected outputMode");
    }
    //print_r($c);
    return $c;
  }
  
  /* determine static class::method to fire */
  function getAction() {
    if (count($this->cfg)==0)
      return;
    global $argv;
    $action = (PHP_SAPI=='cli') ? @$argv[1] : @$_GET['action'];
    if (in_array($action,array_keys($this->cfg['actions']))) {
      $this->actionMethod = $action;
      $this->actionClass = $this->cfg['actions'][$action];
    } 
  }
  
  /* sanitize $argv/$_REQUEST, check for mandatory arguments */
  function getArgs() {
    if (count($this->cfg)==0)
      return;
    global $argv;
    $myargs = $this->cfg['args'][$this->actionMethod];
    if (count($myargs)==0)
      return;
    $safeargs  = array(); // sanitized args
    $finalargs = array(); // sanitized args in correct order
    if (PHP_SAPI=='cli') {
      foreach ($argv as $i=>$arg) {
        if (preg_match('/^-{1,2}([A-Za-z0-9]+)=(.*)$/',$arg,$m)) {
          $argname = $m[1];
          $argval  = $m[2];
          if (in_array($argname, array_keys($myargs)) &&
              preg_match($myargs[$argname], $argval, $m)) {
              $safeargs[$argname] = $m[1];
          } else throw new \Exception("Invalid argument for $argname");
        }
      }
    } else {
      foreach ($myargs as $argname=>$regex) {
        if (isset($_REQUEST[$argname])) {
          if (preg_match($regex, $_REQUEST[$argname], $m)) {
            $safeargs[$argname] = $m[1];
          } else throw new \Exception("Invalid argument for $argname");
        }
      }
    }
    // check for mandatory args
    foreach ($myargs as $argname=>$regex) {
      if (@!$safeargs[$argname] && 
          !@in_array($argname,@array_keys(@$this->cfg['optional_args'][$this->actionMethod]))) {
        throw new \Exception("Missing mandatory argument $argname for method $this->actionMethod");
      }
    }

    // set defaults for missing args
    if (@$this->cfg['optional_args'][$this->actionMethod])
    foreach ($this->cfg['optional_args'][$this->actionMethod] as $argname=>$default) {
      if (@$safeargs[$argname]===0)
        $safeargs[$argname] = 0;
      elseif (@empty($safeargs[$argname]) && $default) 
        $safeargs[$argname] = $default;
    }

    // bring args into order as specified in routerconfig
    foreach ($myargs as $k=>$v) {
      $finalargs[] = @$safeargs[$k];
    }

    $this->args = $finalargs;
  }
  
  /* verify sufficient access perms for action. to be called after getAction() */
  function verifyPermission() {
    if (PHP_SAPI!=='cli' && in_array($this->actionMethod,$this->cfg['action_deny_web']))
      throw new \Exception("Insufficient permissions (only CLI access allowed).");
    if (!$this->isAdmin && in_array($this->actionMethod,$this->cfg['action_requires_admin']))
      throw new \Exception("Insufficient permissions (requires valid-user privileges).");
  }

  /* default action method */
  function usage() {
    return self::createResponse(false,NULL,"Bad usage. See https://github.com/schnoddelbotz/amtc");
  }

  /* create a 'standard' reply */
  static function createResponse($success,$data,$usermsg) {
    return Array(
      'success' => $success ? true : false, 
      'data'    => $data,
      'usermsg' => $usermsg);
  }

}
