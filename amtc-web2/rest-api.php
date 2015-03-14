<?php
/*
 * rest-api.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * Use http://www.slimframework.com/ and http://j4mie.github.io/idiormandparis/
 * to provide a REST backend for amtc-web (ember-data, installer, amtc ...)
 */

// sleep(2);
// error_reporting(E_ALL); ini_set('display_errors','stdout');
// set up autoloader, include required libs, init ORM & Slim
require 'lib/app_bootstrap.php';

// Initialize session
session_name("amtcweb");
session_start();

// Block access if unauthenticated - only permit some vital routes
$allowUnauthenticated = Array('authenticate', 'rest-config.js', 'pages',
                              'phptests', 'submit-configuration');
$_route = explode('/', $app->request()->getPathInfo());
$route  = $_route[1];
if (!empty($_SESSION['authenticated']) && $_SESSION['authenticated'] == true ||
    in_array($route, $allowUnauthenticated )) {
    // echo "Authenticated or request that is allowed without...";
} else {
  echo '{"notifications":[], "error":"unauthenticated"}';
  exit(0);
}


/*****************************************************************************/
/**************** Only SLIM request handling below ***************************/
/*****************************************************************************/

//  Non-DB-Model requests

// provide URI for ember-data REST adapter, based on this php script's location
$app->get('/rest-config.js', function () use ($app) {
  $app->response->header('Content-Type', 'application/javascript;charset=utf-8');
  $path = substr($_SERVER['SCRIPT_NAME'],1);
  echo "DS.RESTAdapter.reopen({\n";
  echo " namespace: '$path'\n";
  echo "});\n";
  printf("var AMTCWEB_IS_CONFIGURED = %s;\n",
            file_exists(AMTC_CFGFILE) ? 'true' : 'false');
  // what about rootURL ? http://emberjs.com/guides/routing/
  // this response could be done statically if #/setup would write it to config/cfg.js?
});
// Return static markdown help pages, json encoded
$app->get('/pages/:id', function ($id) use ($app) {
  $file = sprintf("pages/%s.md", $id);
  is_readable($file) || $app->notFound();
  $contents = file_get_contents($file);
  echo json_encode( array('page'=>array(
    'id' => $id,
    'page_name' => 'unused',
    'page_title' => 'unused',
    'page_content' => $contents
  )));
});
// Installer
$app->post('/submit-configuration', function () use ($app) {

  if (file_exists(AMTC_CFGFILE)) {
    echo 'INSTALLTOOL_LOCKED';
    exit(1);
  }

  $wanted = array(
    'TIMEZONE'   => preg_replace('/[^A-Za-z\/]/', '',  $_POST['timezone']),
    'AMTCBIN'    => realpath($_POST['amtcbin']),
    'AUTHURL'    => $_POST['authurl'], /// FIXME
    'DBTYPE'     => preg_replace('/[^A-Za-z]/', '',    $_POST['selectedDB']),
    'DATADIR'    => realpath($_POST['datadir'])
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

      // fixme: add _htaccess thing ... and some sanitization ... and error checking ... and stuff.
    }
  } else {
    $x = $wanted['AMTCBIN'] ? array("errorMsg"=>"Insufficient parameters!") :
                              array("errorMsg"=>"amtc binary not found at path provided");
  }

  echo json_encode($x);
});
// installation precondition tests
$app->get('/phptests', function () use ($app) {
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
  echo json_encode( $result );
});
// simple basic auth verification 'proxy'
$app->post('/authenticate', function () use ($app) {
  // done here as browsers do not allow to block basic auth popups... hack? yes.
  $wanted = array(
    'username'   => $_POST['username'],
    'password'   => $_POST['password']
  );
  $x = array("exceptionMessage"=>"failed");
  $_SESSION['authenticated'] = false;
  if ($wanted['username'] && $wanted['password']) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, AMTC_AUTH_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $wanted['username'] . ":" . $wanted['password']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $return = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status === 200) {
        $x = array("result"=>"success");
        $_SESSION['authenticated'] = true;
    } else
      sleep(2);
  }
  echo json_encode($x);
});
$app->get('/logout', function () use ($app) {
  if ($_SESSION['authenticated']) {
    $x=array('message'=>'success');
  } else {
    $x=array('message'=>'no success');
  }
  session_destroy();
  echo json_encode($x);
});

// DB-Model requests

/**************** Notifications / Short user messages for dashboard **********/

$app->get('/notifications', function () {
  $result = array('notifications'=>array());
  // php-activerecord...
  //foreach (Notification::all(array("order" => "tstamp desc", 'limit' => 50)) as $record) { $result['notifications'][] = $record->to_array(); }
  // paris....
  foreach (Notification::limit(20)->order_by_desc('tstamp')->find_many() as $record) { $result['notifications'][] = $record->as_array(); }
  echo json_encode( $result );
});

/**************** OUs / Rooms ************************************************/

$app->get('/ous', function () {
  $result = array('ous'=>array());
  foreach (OU::find_many() as $record) {
    $r = $record->as_array();
    //print_r($r);
    $children = OU::where('parent_id', $r['id'])->find_many() ;//'all', array('conditions' => array('parent_id = ?', $r['id'])));
    //$children = OU::find_many() ;
    $kids = array();
    foreach ($children as $childOu) {
      $kids[] = $childOu->id;
    }
    $r['children'] = $kids;
    $r['ou_path'] = 'fixme';// fixme $record->getPathString(); // should/could be done clientside, too
    $result['ous'][] = $r;
  }
  # relations? Book::all(array('include'=>array('author'));
  echo json_encode( $result );
});
$app->get('/ous/:id', function ($ouid) use ($app) {
  if ($ou = OU::find_one($ouid)) {
    echo json_encode( array('ou'=> $ou->as_array()) );
  }
});
$app->put('/ous/:id', function ($id) {
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $put['ou'];
  if ($dev = OU::find_one($id)) {
    $dev->name = $udev->name;
    $dev->description = $udev->description;
    $dev->parent_id = $udev->parent_id;
    $dev->optionset_id = $udev->optionset_id;
    $dev->idle_power = $udev->idle_power;
    $dev->logging = $udev->logging;
    $dev->save();
    echo json_encode( array('ou'=> $dev->as_array()) );
  }
});
$app->post('/ous', function () use ($app) {
  $post = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $ndev = $post['ou'];
  if ($dev = OU::create()) {
    $dev->name = $ndev->name;
    $dev->description = $ndev->description;
    $dev->parent_id = $ndev->parent_id;
    $dev->optionset_id = $ndev->optionset_id;
    $dev->idle_power = $ndev->idle_power;
    $dev->logging = $ndev->logging;
    $dev->save();
    echo json_encode( array('ou'=> $dev->as_array()) );
  }
});
$app->delete('/ous/:id', function ($id) {
  if ($dev = OU::find_one($id)) {
    OU::query('PRAGMA foreign_keys = ON;');
    $dev->delete();
    // "Note: Although after destroyRecord or deleteRecord/save the adapter
    // expects an empty object e.g. {} to be returned from the server after
    //  destroying a record."
    // http://emberjs.com/guides/models/the-rest-adapter/
    echo '{}';
  }
});

/**************** Hosts ******************************************************/

$app->get('/hosts', function () {
  $result = array('hosts'=>array());
  foreach (Host::order_by_asc('hostname')->find_many() as $record) {
    $r = $record->as_array();
    $result['hosts'][] = $r;
  }
  echo json_encode( $result );
});
$app->post('/hosts', function () use ($app) {
  $post = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $ndev = $post['host'];
  if ($dev = Host::create()) {
    $dev->hostname = $ndev->hostname;
    $dev->ou_id = $ndev->ou_id;
    $dev->save();
    echo json_encode( array('host'=> $dev->as_array()) );
  }
});
// should better be side-loaded with hosts...?
$app->get('/laststates', function () {
  $result = array('laststates'=>array());
  foreach (Laststate::find_many() as $record) {
    $result['laststates'][] = $record->as_array();
  }
  echo json_encode( $result );
});

// TBD:
// $app->get('/livestates', $ouid) -> AMTC_BIN + optionset ...
// or... include it into '/laststates' if flag given: run amtc, update db, fetch db


/**************** Users ******************************************************/

$app->get('/users', function () {
  $result = array('users'=>array());
  foreach (User::order_by_asc('name')->find_many() as $record) {
    $r = $record->as_array();
    $result['users'][] = $r;
  }
  echo json_encode( $result );
});
$app->get('/users/:id', function ($uid) use ($app) {
  if ($user = User::find_one($uid)) {
    echo json_encode( array('user'=> $user->as_array()) );
  } elseif ($user = User::where('name',$uid)->find_one()) {
    echo json_encode( array('user'=> $user->as_array()) );
  }
});
$app->put('/users/:id', function ($id) {
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $put['user'];
  if ($dev = User::find_one($id)) {
    $dev->name = $udev->name;
    $dev->fullname = $udev->fullname;
    $dev->ou_id = $udev->ou_id;
    // this gets boring. improve.
    $dev->is_enabled = $udev->is_enabled;
    $dev->is_admin = $udev->is_admin;
    $dev->can_control = $udev->can_control;
    $dev->save();
    echo json_encode( array('user'=> $dev->as_array()) );
  }
});
$app->post('/users', function () {
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $put['user'];
  if ($dev = User::create()) {
    $dev->name = $udev->name;
    $dev->fullname = $udev->fullname;
    $dev->ou_id = $udev->ou_id;
    // this gets boring. improve.
    $dev->is_enabled = $udev->is_enabled;
    $dev->is_admin = $udev->is_admin;
    $dev->can_control = $udev->can_control;
    $dev->save();
    echo json_encode( array('user'=> $dev->as_array()) );
  }
});
$app->delete('/users/:id', function ($id) {
  if ($dev = User::find_one($id)) {
    Optionset::query('PRAGMA foreign_keys = ON;');
    $dev->delete();
    echo '{}';
  }
});

/**************** AMT Optionsets *********************************************/

$app->get('/optionsets', function () {
  $result = array('optionsets'=>array());
  foreach (Optionset::order_by_asc('name')->find_many() as $record) {
    $r = $record->as_array();
    $result['optionsets'][] = $r;
  }
  echo json_encode( $result );
});
$app->get('/optionsets/:id', function ($ouid) use ($app) {
  if ($os = Optionset::find_one($ouid)) {
    echo json_encode( array('optionset'=> $os->as_array()) );
  }
});
$app->put('/optionsets/:id', function ($id) {
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $put['optionset'];
  if ($dev = Optionset::find_one($id)) {
    $dev->name = $udev->name;
    $dev->description = $udev->description;
    $dev->sw_v5 = $udev->sw_v5;
    // this gets boring. improve.
    $dev->sw_dash = $udev->sw_dash;
    $dev->sw_scan22 = $udev->sw_scan22;
    $dev->sw_scan3389 = $udev->sw_scan3389;
    $dev->sw_skipcertchk = $udev->sw_skipcertchk;
    $dev->sw_usetls = $udev->sw_usetls;
    $dev->opt_cacertfile = $udev->opt_cacertfile;
    $dev->opt_passfile = $udev->opt_passfile;
    $dev->opt_timeout = $udev->opt_timeout;
    $dev->save();
    echo json_encode( array('optionset'=> $dev->as_array()) );
  }
});
$app->delete('/optionsets/:id', function ($id) {
  if ($dev = Optionset::find_one($id)) {
    Optionset::query('PRAGMA foreign_keys = ON;');
    $dev->delete();
    echo '{}';
  }
});
$app->post('/optionsets', function () use ($app) {
  $post = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $post['optionset'];
  if ($dev = Optionset::create()) {
    $dev->name = $udev->name;
    $dev->description = $udev->description;
    $dev->sw_v5 = $udev->sw_v5;
    // this gets boring. improve.
    $dev->sw_dash = $udev->sw_dash;
    $dev->sw_scan22 = $udev->sw_scan22;
    $dev->sw_scan3389 = $udev->sw_scan3389;
    $dev->sw_skipcertchk = $udev->sw_skipcertchk;
    $dev->sw_usetls = $udev->sw_usetls;
    $dev->opt_cacertfile = $udev->opt_cacertfile;
    $dev->opt_passfile = $udev->opt_passfile;
    $dev->opt_timeout = $udev->opt_timeout;
    $dev->save();
    echo json_encode( array('optionset'=> $dev->as_array()) );
  }
});

/**************** Scheduler items *********************************************/

$app->get('/jobs', function () {
  $result = array('jobs'=>array());
  foreach (Job::order_by_asc('description')->find_many() as $record) {
    $r = $record->as_array();
    $result['jobs'][] = $r;
  }
  echo json_encode( $result );
});
$app->get('/jobs/:id', function ($jid) {
  if ($job = Job::find_one($jid)) {
    echo json_encode( array('job'=> $job->as_array()) );
  }
});
$app->put('/jobs/:id', function ($id) {
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $user = $put['job'];
  if ($job = Job::find_one($id)) {
    $job->repeat_days = $user->repeat_days;
    $job->description = $user->description;
    $job->start_time  = $user->start_time;
    $job->ou_id       = $user->ou_id;
    $job->amtc_cmd    = $user->amtc_cmd;
    $job->amtc_delay  = $user->amtc_delay;
    $job->save();
    echo json_encode( array('job'=> $job->as_array()) );
  }
});
$app->delete('/jobs/:id', function ($id) {
  if ($job = Job::find_one($id)) {
    //Job::query('PRAGMA foreign_keys = ON;'); // YIKES! FIXME!-no-idiorm...ORM::configure?
    $job->delete();
    echo '{}';
  }
});
$app->post('/jobs', function () use ($app) {
  $post = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $user = $post['job'];
  if ($job = Job::create()) {
    $job->repeat_days = $user->repeat_days;
    $job->description = $user->description;
    $job->start_time  = $user->start_time;
    $job->ou_id       = $user->ou_id;
    $job->job_type    = $user->job_type; //amtcwebSpooler::JOB_INTERACTIVE=1,SCHED=2,MON=3
    $job->user_id     = 1; // FIXME!!
    $job->amtc_cmd    = $user->amtc_cmd;
    $job->amtc_delay  = $user->amtc_delay;
    isset($user->hosts) && $job->amtc_hosts  = implode(',',$user->hosts); // fixme, at least allow int only...
    $job->save();
    echo json_encode( array('job'=> $job->as_array()) );
    // if this is a interactive/type-1 job with cmd != info,
    // add scan jobs to monitor job progress w/o manual page reloads
  }
});

/*****************************************************************************/
/*
 *
 * ... run, forrest, run!
 */

$app->run();

