<?php
/*
 * class.AmtcWebSetup.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * auxiliary functions for amtc-web installation + configuration.
 * fixme: clean up the mess.
 */

namespace amtcweb;

class AmtcWebSetup {

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
      return FrontendCtrl::createResponse($res,NULL,$res ? 
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
        return FrontendCtrl::createResponse($res , NULL, $res ? 
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
        return FrontendCtrl::createResponse(true, NULL, 'Room created successfully.');
      }
      if ($editRoomname) {
        $res=Room::modify($roomname,$editRoomname,$amt_version,$idle_power);
        return FrontendCtrl::createResponse(true, $that->args, $res==1?
                     "Room successfully updated.":"Bogus: $res rows updated?!");
      }
      if ($deletePc) 
        return FrontendCtrl::createResponse(Room::deletePc($deletePc), NULL,'No error reported');
      if ($deleteRoom) 
        return FrontendCtrl::createResponse(Room::delete($deleteRoom), NULL,'No error reported');
    }
    
    // return details about all rooms (and room meta and PCs if one was selected)
    $response = Array('rooms','room','roomMeta');
    $response['rooms'] = Room::getRooms(false,true /* rooms only, no join with pc table */);
    if ($selected_room) {
      $response['roomMeta'] = Room::getRoom($selected_room,false,true);

      if ($createPc && $do=='submit') 
        return FrontendCtrl::createResponse(Room::createPc($response['roomMeta']['id'],$createPc), NULL,'No error reported');
      
      try { 
        // this would panic if no PCs exist yet...
        $response['room'] = Room::getRoom($selected_room,false,false); 
      } catch (\Exception $e) {}
    }

    return FrontendCtrl::createResponse(true, $response, "Read room $roomname: success.");
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
