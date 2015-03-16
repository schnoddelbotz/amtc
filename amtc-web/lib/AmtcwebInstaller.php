<?php
/*
 * lib/amtcwebInstaller.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * utility class for setup/installation. deserves STRONG cleanup...
 */

class AmtcwebInstaller {

  // write user-posted config (fresh install only)
  static function writeAmtcwebConfig($userData) {
    if (file_exists(AMTC_CFGFILE)) {
      echo 'INSTALLTOOL_LOCKED';
      return;
    }

    $wanted = array(
      'TIMEZONE'   => preg_replace('/[^A-Za-z\/]/', '',  $userData['timezone']),
      'AMTCBIN'    => realpath($userData['amtcbin']),
      'AUTHURL'    => $userData['authurl'], /// FIXME
      'DBTYPE'     => preg_replace('/[^A-Za-z]/', '',    $userData['selectedDB']),
      'DATADIR'    => realpath($userData['datadir'])
    );

    if ($wanted['TIMEZONE'] && $wanted['DATADIR'] && $wanted['DBTYPE'] && $wanted['AMTCBIN']) {
      $x = array("message"=>"Configuration written successfully");
      $cfgTpl = "<?php\n\n".
                "define('AMTC_PDOSTRING', '%s');\n".
                "define('AMTC_DBUSER', '%s');\n".
                "define('AMTC_DBPASS', '%s');\n".
                "define('AMTC_BIN', '%s');\n".
                "define('AMTC_AUTH_URL', '%s');\n".
                "define('AMTC_TZ', '%s');\n".
                "define('AMTC_DATADIR', '%s');\n";

      if ($wanted['DBTYPE'] == 'SQLite') {
        // create db if non-existant
        umask(0);
        @touch($_POST['sqlitePath']); // grr should be called sqlitefile not path
        $wanted['SQLITEPATH'] = realpath($_POST['sqlitePath']);
        $wanted['PDOSTRING'] = sprintf('sqlite:%s', $wanted['SQLITEPATH']);
        $wanted['DBUSER'] = '';
        $wanted['DBPASS'] = '';
        $dbh = new PDO($wanted['PDOSTRING']);
      }
      if ($wanted['DBTYPE'] == 'MySQL') {
        // mysql://user:pass@host[:port]/dbname)
        $wanted['DBUSER'] = $_POST['mysqlUser'];
        $wanted['DBPASS'] = $_POST['mysqlPassword'];
        $mHost = $_POST['mysqlHost'];
        $mDB   = $_POST['mysqlDB'];
        $wanted['PDOSTRING'] = sprintf('mysql:host=%s;dbname=%s', $mHost, $mDB);
        $tmp = new PDO(sprintf('mysql:host=%s', $mHost), $wanted['DBUSER'], $wanted['DBPASS']);
        $tmp->exec('CREATE DATABASE IF NOT EXISTS '.$mDB.';');
        $tmp = NULL;
        $dbh = new PDO($wanted['PDOSTRING'], $wanted['DBUSER'], $wanted['DBPASS']);
      }
      // stuff below will only happen if PDO connect was ok...

      $cfg = sprintf($cfgTpl, $wanted['PDOSTRING'], $wanted['DBUSER'],
                    $wanted['DBPASS'], $wanted['AMTCBIN'], $wanted['AUTHURL'],
                    $wanted['TIMEZONE'], $wanted['DATADIR']);

      if (!is_writable(dirname(AMTC_CFGFILE))) {
        $x = array("errorMsg"=>"Config directory ".AMTC_CFGFILE." not writable!");
      } elseif (false === file_put_contents(AMTC_CFGFILE, $cfg)) {
        $x = array("errorMsg"=>"Could not write config file!");
      } else {
        $selectedDB = strtolower($wanted['DBTYPE']);
        $dbh->exec(file_get_contents('lib/install-db/'.$selectedDB.'.sql'));
        $dbh->exec(file_get_contents('lib/install-db/'.$selectedDB.'-minimal.sql'));

        if ($_POST['importDemo']=='true'/* yes, a string. fixme */)
          $dbh->exec(file_get_contents('lib/install-db/'.$selectedDB.'-exampledata.sql'));

        // fix-me: add some sanitization ... and error checking ... and stuff!!!
      }
    } else {
      $x = $wanted['AMTCBIN'] ? array("errorMsg"=>"Insufficient parameters!") :
                                array("errorMsg"=>"amtc binary not found at path provided");
    }

    return json_encode($x);
  }

  // installation precondition tests
  static function runPhpSetupTests() {
    $v=explode(".",PHP_VERSION);
    $tests = array(
      array('id'=>'php53',     'description'=>'PHP version 5.3+',          'result'=>$v[0]>5||($v[0]==5&&$v[1]>2),       'remedy'=>'upgrade PHP to 5.3+'),
      array('id'=>'freshsetup','description'=>'No config file present yet','result'=>!file_exists(AMTC_CFGFILE),         'remedy'=>'remove config/siteconfig.php'),
      array('id'=>'data',      'description'=>'data/ directory writable',  'result'=>is_writable('data'),                'remedy'=>'run chmod 777 on data/ directory'),
      array('id'=>'config',    'description'=>'config/ directory writable','result'=>is_writable('config'),              'remedy'=>'run chmod 777 on config/ directory'),
      array('id'=>'curl',      'description'=>'PHP cURL support',          'result'=>function_exists('curl_init'),       'remedy'=>'install PHP cURL module'),
      array('id'=>'pdo',       'description'=>'PHP PDO support',           'result'=>phpversion("pdo")?true:false,       'remedy'=>'install PHP PDO module'),
      array('id'=>'pdo_sqlite','description'=>'PDO SQLite support',        'result'=>phpversion("pdo_sqlite")?true:false,'remedy'=>'install PHP PDO sqlite module'),
      array('id'=>'pdo_mysql', 'description'=>'PDO MySQL support',         'result'=>phpversion("pdo_mysql")?true:false, 'remedy'=>'install PHP PDO mysql module'),
      array('id'=>'pdo_oci',   'description'=>'PDO Oracle support',        'result'=>phpversion("pdo_oci8")?true:false,  'remedy'=>'install PHP PDO orcale module'),
      array('id'=>'pdo_pgsql', 'description'=>'PDO Postgres support',      'result'=>phpversion("pdo_pgsql")?true:false, 'remedy'=>'install PHP PDO postgres module'),
      //'dbconnect' => array('',   '', ''),
      //'dbwrite'   => array('',   '', ''), --> /testdb?
    );
    $result = array('phptests'=>$tests,
      'authurl'=>'http://localhost'.dirname($_SERVER['SCRIPT_NAME']).'/basic-auth/');
    return json_encode($result);
  }
}

