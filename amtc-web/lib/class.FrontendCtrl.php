<?php
/*
 * class.FrontendCtrl.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * a frontend controller class that does request sanitizing,
 * parsing and routing to static class methods. quick, dirty.
 * still includes ugly GUI code that should get out of here.
 */

namespace amtcweb;

class FrontendCtrl {

  private $cfg;
  private $sitecfg;
  private $actionClass = 'FrontendCtrl';
  private $actionMethod = 'usage';
  private $args = Array();
  private $content = Array();
  private $outputMode = 'ajax';
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

  /* produce content for config/admin section */
  private function config($mode) {
    $method = "\\amtcweb\\FrontendCtrl::config_$mode";
    // pass $this by ref to let $method influence $this->outputMode (and access sitecfg)
    return call_user_func_array($method, array(&$this));
  }

 /*
  *  functions (only) used for config screens below (fixme: wrong in here).
  */

  /* site local settings screen, holy mess FIXME cleanup */
  static function config_site($that) {
    preg_match('/^([^:]+):(.*)$/',$that->sitecfg['db_dsn'],$dsn);
    $dmeta = Array(
      'db_dsn' => Array('type'=>'text','desc'=>'PHP PDO DB DSN'),
      'db_user' => Array('type'=>'text','desc'=>'Database username'),
      'db_pass' => Array('type'=>'text','desc'=>'Database password'),
      'amtc_exe' => Array('type'=>'text','desc'=>'Path to amtc executeable'),
      'amtc_opts' => Array('type'=>'text','desc'=>'amtc Options'),
      'spool_lock' => Array('type'=>'text','desc'=>'Lock file (dir must be r/w)'),
      'amt_pw_file' => Array('type'=>'text','desc'=>'File containing AMT password'),
    );
    $submitUrl = 'admin/admin.php?action=config&mode=site&do=submit';
    $sitefile = APP_ROOT."/var/siteconfig.php";
    $cfgdir = APP_ROOT."/var";
    $saved = '';
    if (@$_REQUEST['do']=='submit') {
      $res = self::writeSiteConfig($_POST,$sitefile,$that->sitecfg);
      return self::createResponse($res,NULL,$res ? 
        'Config file successfully updated':'Could not update config file.');
    }
    $title = "<h2>Local configuration settings</h2>\n$saved";
    $foot  = "<p>";
    $foot .= "Configuration file used for local settings:<br/>&raquo; <code>$sitefile</code><br/>";
    // first install: try to create APP_ROOT/var if non-existant
    clearstatcache();
    if(file_exists($cfgdir) && is_dir($cfgdir)) {
      $foot .= 'Config directory <code>'.$cfgdir.'</code> <span class="success">exists</span>.<br/>';
    } else {
      if (@mkdir($cfgdir)) {
        $foot .= '<b>Config directory <code>'.$cfgdir.'</code> created <span class="success">successfully</span>.</b><br/>';
      } else {
        $ug=''; // user and group
        if (function_exists(posix_getpwuid)) {
          $U = posix_getpwuid(posix_geteuid());
          $G = posix_getgrgid(posix_getgid()); // i just decided amtc-web doesn's support windows 
          $u = $U['name'];
          $g = $G['name'];
          $ug = " (user $u, group $g)";
        }
        $foot .= '<strong>Can <span class="warning">NOT</span> create config directory '.
              "<code>$cfgdir</code> !</strong><br/>".
              'The directory is <span class="warning"><strong>required</strong></span> to use amtc-web!<br/> '.
              "It must be writable for your webserver's user or group$ug.<br/>".
              'As the directory could not be created by this script, you have to manually ... '.
              '<ul>'.
              "<li>create the directory and make it owned by user $u:<br>".
              " <code>mkdir $cfgdir<br/>chown $u $cfgdir</code> <br/>(to be run as root)  <span class=\"success\"><b>OR</b><span> </li>".
              "<li>create the directory and make it group writable:<br>".
              " <code>mkdir $cfgdir<br/> chgrp $g $cfgdir<br/> chmod g+w $cfgdir</code><br/>(to be run as root, too) <span class=\"success\"><b>OR</b><span> </li>".
              "<li>create a symlink (possibly as regular user):<br/> <code>ln -s /tmp ".APP_ROOT."/var</code><br/>(where <code>/tmp</code> should be replaced with your data directory of choice) </li>".
              '</ul>'
              ;
      }
    }

    // chk sitefile
    if (!file_exists($sitefile))
      @touch($sitefile); // fix me? 
    $foot .= sprintf('The config directory is currently <span class="%s">%swritable'.
                    '</span> and the file is <span class="%s">%swritable</span>.<br/>',
                      is_writable(dirname($sitefile)) ? 'success' : 'warning',
                      is_writable(dirname($sitefile)) ? '' : 'NOT ',
                      is_writable($sitefile) ? 'success' : 'warning',
                      is_writable($sitefile) ? '' : 'NOT ');

    // check DB params
    if ($dsn[1]=='sqlite') {
      $foot .= sprintf('The sqlite DB directory %s is <span class="%s">%swritable</span>.<br/>',
                       '<code>'.$dsn[2].'</code>',
                      is_writable(dirname($dsn[2])) ? 'success' : 'warning',
                      is_writable(dirname($dsn[2])) ? '' : 'NOT ' );
      $foot .= sprintf('The sqlite DB file (created during initial DB setup) does <span class="%s">%sexist</span>.<br/>',
                      file_exists($dsn[2]) ? 'success' : 'warning',
                      file_exists($dsn[2]) ? '' : 'NOT ');
    }
    $foot .= "For successful amtc-web configuration, all warnings must vanish.<br/>";
    $foot .= "For production use, you may make the configuration file read-only.<br/>";
    $foot .= "To refresh this report, reload this page or switch tabs.";
    $foot .= "</p>";
    $that->outputMode = 'raw'; /* tell calling FEcontroller to produce html page */
    return $title.self::renderForm('sitecfg',$that->sitecfg,'Save settings',$dmeta,$submitUrl).$foot;
    // RFE: Remove-siteconfig-button = restore defaults
    //      add button: create /var and chmod 777
  }

  /* database settings screen -- DB installer */
  static function config_db($that) {
    preg_match('/^([^:]+):(.*)$/',$that->sitecfg['db_dsn'],$dsn);
    if (@$_REQUEST['do']=='submit') {
        // submit means 'install db dump' here...
        $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
        $sqlfile = sprintf('%s/admin/dump_%s.txt', APP_ROOT, $dsn[1]);
        $sql = file_get_contents($sqlfile);
        $res=($db->exec($sql)!==false);
        return self::createResponse($res , NULL, $res ? 
                                    'DB Import success' : 'DB Import failed');
    }
    $submitUrl = 'admin/admin.php?action=config&mode=db&do=submit';
    $c = "<h2>Database setup</h2>\n<dl>\n";
    $_fmt  = '<dt>%s</dt><dd>%s</dd>';
    // check PHP db support
    $has_PDO = class_exists('\\PDO');
    $supported = '<span class="warning">No PHP PDO support!</span>';
    if ($has_PDO) {
      $drivers = \PDO::getAvailableDrivers();
      $supported = implode(", ",$drivers);
      
      if (count($drivers)<1)
        $supported = '<span class="warning">No PHP PDO drivers installed!</span>';
    }
    $c .= sprintf($_fmt, 'PHP supported PDO drivers', $supported);
    $c .= sprintf($_fmt, 'Selected PDO driver', $dsn[1]);
    if ($dsn[1] && $has_PDO) {
      $c .= self::testDb();
      $c .= self::renderForm('dbsetup',Array(),
                             'First install: Create required tables',
                             Array(),$submitUrl);
    }
    $c .= '</dl>';
    return $c;
    // RFE: Add DB stats (#rooms, #pcs, #logdata...); add DB refcheck/garbage collect; optimize/compact
    //      Download SQLITE db file via admin (backup)
  }

  static function config_rooms($that) {
    list($mode,$do,$roomname,$editRoomname,$newRoomname,$amt_version,
         $idle_power,$deletePc,$createPc,$deleteRoom)=$that->args;
    $selected_room = empty($roomname) ? false : $roomname;
    $c=''; // content/output

    // try to save changes made
    //return self::createResponse(true, $that->args, "DEBUG");
    if ($do=='submit') {
      if ($newRoomname) {
        Room::create($newRoomname,$amt_version,$idle_power);
        return self::createResponse(true, NULL, 'Room created successfully.');
      }
      if ($editRoomname) {
        $res=Room::modify($roomname,$editRoomname,$amt_version,$idle_power);
        return self::createResponse(true, $that->args, $res==1?
                     "Room successfully updated.":"Bogus: $res rows updated?!");
      }
      if ($deletePc) 
        return self::createResponse(Room::deletePc($deletePc), NULL,'No error reported');
      if ($deleteRoom) 
        return self::createResponse(Room::delete($deleteRoom), NULL,'No error reported');
    }
    
    // return details about all rooms (and room meta and PCs if one was selected)
    $response = Array('rooms','room','roomMeta');
    $response['rooms'] = Room::getRooms(false,true /* rooms only, no join with pc table */);
    if ($selected_room) {
      $response['roomMeta'] = Room::getRoom($selected_room,false,true);

      if ($createPc && $do=='submit') 
        return self::createResponse(Room::createPc($response['roomMeta']['id'],$createPc), NULL,'No error reported');
      
      try { 
        // this would panic if no PCs exist yet...
        $response['room'] = Room::getRoom($selected_room,false,false); 
      } catch (\Exception $e) {}
    }

    return self::createResponse(true, $response, "Read room $roomname: success.");
  }

  static function config_scheduler($that) {
    $jobs = SpooledJob::getQueue(false,false);
    
    // mutex locked?
    // pending jobs (x) delete
    // past jobs
  }

  /* renders simple html form including js submit button */
  static function renderForm($formId, $data, $submitButtonText, $dmeta, $submitUrl) {
    $c = sprintf('<form id="%s">%s <dl>%s', $formId, "\n", "\n");
    $submitVals = array();
    foreach ($data as $skey=>$sval) {
      $c .= sprintf('<dt>%s</dt>', $dmeta[$skey]['desc']);
      $c .= sprintf('<dd><input type="text" size="50" name="%s" value="%s"></dd>%s', 
                    $skey, $sval, "\n");
      $submitVals[] = sprintf("\t\t'%s': \$(\"form input[name='%s']\").val()", $skey, $skey);
    }
    $c .= " </dl>\n";
    $c .= sprintf('<input type="button" id="%s_submit" value="%s">', $formId,$submitButtonText);
    $c .= "</form>\n";
    $c .= sprintf('<p id="%s_msg" class="umsg"></p>', $formId);

    $js_code = <<<JAVASCRIPT
    <script>
    $('#%s_submit').click(function() {
      $.post("%s", { 
         %s 
      }, function(data) {
        console.log(data);
        if (data.success==true)
          msg = 'Data submitted <span class="success">successfully</span>: '+data.usermsg;
        else
          msg = 'Data submission <span class="warning">FAILED</span>: '+data.usermsg;
        $("#%s_msg").html(msg);
      }).error(function() { 
        $("#%s_msg").html('Data submission <span class="warning">badly FAILED</span>.');
        $("#wheel").hide(); 
      }); 
    });
    </script>
JAVASCRIPT;
    $js = sprintf($js_code, $formId, $submitUrl, implode(",\n", $submitVals), $formId, $formId);
    
    return $c.$js;
  }

  /* dumb db test, used by config_db */
  static function testDb() {
    $c='';
    $failed  = '<span class="warning">FAILED</span>';
    $success = '<span class="success">SUCCESS</span>';
    $tests = Array(
      'Can connect?' => '',
      'Can CREATE TABLE ?' => 'CREATE TABLE IF NOT EXISTS TESTTEST (id int)',
      'Can DROP TABLE ?' => 'DROP TABLE TESTTEST',
      'Check table existance: pcs' => 'SELECT * FROM pcs',
      'Check table existance: rooms' => 'SELECT * FROM rooms',
      'Check table existance: jobs' => 'SELECT * FROM jobs',
      'Check table existance: logdata' => 'SELECT * FROM logdata',
      'Test INSERT INTO rooms' => 'INSERT INTO rooms VALUES (NULL, "TESTTEST", 0,0)',
      'Test DELETE FROM rooms table?' => 'DELETE FROM rooms WHERE roomname="TESTTEST"',
    );
    $_fmt  = '<dt>%s</dt><dd>%s</dd>';
    foreach ($tests as $t=>$sql) {
      $res=$failed;
      try {
        $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
        $res=$success;
        if ($sql && !$db->query($sql)) {
          $res=$failed;
        }
      } catch (\PDOException $e) {
        $res=$failed;
      }
      $c.=sprintf($_fmt, $t, $res);
    }
    return $c;
  }

  /* will write siteconfig.php with new values */
  static function writeSiteConfig($newcfg,$file,$sitecfg) {
    $config = sprintf('<?php %s// Config auto-generated by amtc-web on %s %s', "\n",
                       date("Y-m-d H:i:s"), "\n");

    foreach ($sitecfg as $k=>$v) 
      $new[] = sprintf('$siteconfig["%s"] = "%s";', $k, $newcfg[$k]);
     
    $config .= join("\n",$new)."\n";
    if ($fh = fopen($file, 'w')) {
      fputs($fh, $config);
      fclose($fh);
      return true;
    } 
    return false;
  }


}
