<?php
/*
 * rest-api.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * Use http://www.slimframework.com/ and http://www.phpactiverecord.org/
 * to provide a REST backend for amtc-web (ember-data, installer, amtc ...)
 */

// sleep(2);

define('AMTC_CFGFILE', 'config/siteconfig.php');
@include AMTC_CFGFILE; // to let static ember help pages work even if unconfigured
date_default_timezone_set( defined('AMTC_TZ') ? AMTC_TZ : 'Europe/Berlin');

require 'lib/php-activerecord/ActiveRecord.php';
require 'lib/Slim/Slim.php';

// Initialize SLIM
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app->config('debug', false); // ... and enables custom $app->error() handler
$app->response()->header('Content-Type', 'application/json;charset=utf-8');
$app->notFound(function () use ($app) {
  echo json_encode(Array('error'=>'Not found')); 
});
$app->error(function (\Exception $e) use ($app) {
  echo json_encode( array('exceptionMessage'=> substr($e->getMessage(),0,128).'...') ); // to much / insecure?
});

// Initialize http://www.phpactiverecord.org/
ActiveRecord\Config::initialize(function($cfg){
  $cfg->set_model_directory('lib/db-model');
  $cfg->set_connections(
     array(
      'production' => AMTC_PDOSTRING
     )
  );
  $cfg->set_default_connection('production');
});

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
    'DBTYPE'     => preg_replace('/[^A-Za-z]/', '',    $_POST['selectedDB']),
    'DATADIR'    => realpath($_POST['datadir'])
  );

  if ($wanted['TIMEZONE'] && $wanted['DATADIR'] && $wanted['DBTYPE'] && $wanted['AMTCBIN']) {
    $x = array("message"=>"Configuration written successfully");
    $cfgTpl = "<?php\n\n".
              "define('AMTC_PDOSTRING', '%s');\n".
              "define('AMTC_BIN', '%s');\n".
              "define('AMTC_TZ', '%s');\n".
              "define('AMTC_DATADIR', '%s');\n";

    if ($wanted['DBTYPE'] == 'SQLite') {
      // create db if non-existant
      @touch($_POST['sqlitePath']); // grr should be called sqlitefile not path
      $wanted['SQLITEPATH'] = realpath($_POST['sqlitePath']);
      $wanted['PDOSTRING'] = sprintf('sqlite:%s', $wanted['SQLITEPATH']);
      $wanted['PHPARSTRING'] = sprintf('sqlite://unix(%s)', $wanted['SQLITEPATH']);
    }

    $cfg = sprintf($cfgTpl, $wanted['PHPARSTRING'], $wanted['AMTCBIN'], 
                            $wanted['TIMEZONE'],    $wanted['DATADIR']);

    if (!is_writable(dirname(AMTC_CFGFILE))) {
      $x = array("errorMsg"=>"Config directory ".AMTC_CFGFILE." not writable!");
    } elseif (false === file_put_contents(AMTC_CFGFILE, $cfg)) {
      $x = array("errorMsg"=>"Could not write config file!");
    } else {
      $dbh = new PDO($wanted['PDOSTRING']);
      $selectedDB = strtolower($wanted['DBTYPE']);
      $dbh->exec(file_get_contents('lib/db-model/install-db/'.$selectedDB.'.sql'));
      $dbh->exec(file_get_contents('lib/db-model/install-db/'.$selectedDB.'-minimal.sql')); 
      
      if ($_POST['importDemo']=='true'/* yes, a string. fixme */)
        $dbh->exec(file_get_contents('lib/db-model/install-db/'.$selectedDB.'-exampledata.sql')); 
        
      // fixme: add _htaccess thing ... and some sanitization ... and error checking ... and stuff.
    }
  } else {
    $x = $wanted['AMTCBIN'] ? array("errorMsg"=>"Insufficient parameters!") :
                              array("errorMsg"=>"amtc binary not found at path provided");
  }

  echo json_encode($x);
});

// installation precondition tests
$app->get('/phptests', function () use ($app, $phptests) {
  $v=explode(".",PHP_VERSION);
  $tests = array(
    array('id'=>'php53',     'description'=>'PHP version 5.3+',          'result'=>$v[0]>5||($v[0]==5&&$v[1]>2),       'remedy'=>'upgrade PHP to 5.3+'),
    array('id'=>'freshsetup','description'=>'No config file present yet','result'=>!file_exists(AMTC_CFGFILE),         'remedy'=>'remove config/siteconfig.php'),
    array('id'=>'data',      'description'=>'data/ directory writable',  'result'=>is_writable('data'),                'remedy'=>'run chmod 777 on data/ directory'),
    array('id'=>'config',    'description'=>'config/ directory writable','result'=>is_writable('config'),              'remedy'=>'run chmod 777 on config/ directory'),
    array('id'=>'pdo',       'description'=>'PHP PDO support',           'result'=>phpversion("pdo")?true:false,       'remedy'=>'install PHP PDO module'),
    array('id'=>'pdo_sqlite','description'=>'PDO SQLite support',        'result'=>phpversion("pdo_sqlite")?true:false,'remedy'=>'install PHP PDO sqlite module'),
    array('id'=>'pdo_mysql', 'description'=>'PDO MySQL support',         'result'=>phpversion("pdo_mysql")?true:false, 'remedy'=>'install PHP PDO mysql module'),
    array('id'=>'pdo_oci',   'description'=>'PDO Oracle support',        'result'=>phpversion("pdo_oci8")?true:false,  'remedy'=>'install PHP PDO orcale module'),
    array('id'=>'pdo_pgsql', 'description'=>'PDO Postgres support',      'result'=>phpversion("pdo_pgsql")?true:false, 'remedy'=>'install PHP PDO postgres module'),
    //'dbconnect' => array('',   '', ''),
    //'dbwrite'   => array('',   '', ''), --> /testdb?
  );
  $result = array('phptests'=>$tests);
  echo json_encode( $result );
});

// DB-Model requests 

/**************** Notifications / Short user messages for dashboard **********/

$app->get('/notifications', function () {
  $result = array('notifications'=>array());
  foreach (Notification::all(array("order" => "tstamp desc", 'limit' => 50)) as $record) { $result['notifications'][] = $record->to_array(); }
  echo json_encode( $result );
});

/**************** OUs / Rooms ************************************************/

$app->get('/ous', function () {
  $result = array('ous'=>array());
  foreach (OU::all() as $record) {
    $r = $record->to_array();
    $children = OU::find('all', array('conditions' => array('parent_id = ?', $r['id'])));
    $kids = array();
    foreach ($children as $childOu) {
      $kids[] = $childOu->id;
    }
    $r['children'] = $kids;
    $r['ou_path'] = $record->getPathString(); // should/could be done clientside, too
    $result['ous'][] = $r;
  }
  # relations? Book::all(array('include'=>array('author'));
  echo json_encode( $result );
});
$app->get('/ous/:id', function ($ouid) use ($app) {
  if ($ou = OU::find($ouid)) {
    echo json_encode( array('ou'=> $ou->to_array()) );
  }
});
$app->put('/ous/:id', function ($id) {
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $put['ou'];
  if ($dev = OU::find_by_id($id)) {
    $dev->name = $udev->name;
    $dev->description = $udev->description;
    $dev->parent_id = $udev->parent_id;
    $dev->optionset_id = $udev->optionset_id;
    $dev->idle_power = $udev->idle_power;
    $dev->logging = $udev->logging;
    $dev->save();
    echo json_encode( array('ou'=> $dev->to_array()) );
  }
});
$app->post('/ous', function () use ($app) {
  $post = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $ndev = $post['ou'];
  if ($dev = new OU) {
    $dev->name = $ndev->name;
    $dev->description = $ndev->description;
    $dev->parent_id = $ndev->parent_id;
    $dev->optionset_id = $ndev->optionset_id;
    $dev->idle_power = $udev->idle_power;
    $dev->logging = $udev->logging;
    $dev->save();
    echo json_encode( array('ou'=> $dev->to_array()) );
  }
});
$app->delete('/ous/:id', function ($id) {
  if ($dev = OU::find_by_id($id)) {
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
  foreach (Host::all(array("order" => "hostname asc")) as $record) {
    $r = $record->to_array();
    $result['hosts'][] = $r;
  }
  echo json_encode( $result );
});

/**************** Users ******************************************************/
// ACL control: undone. intension is to do auth via apache / external source...

$app->get('/users', function () {
  $result = array('users'=>array());
  foreach (User::all(array("order" => "name asc")) as $record) {
    $r = $record->to_array();
    $result['users'][] = $r;
  }
  echo json_encode( $result );
});
$app->get('/users/:id', function ($uid) use ($app) {
  if ($user = User::find($uid)) {
    echo json_encode( array('user'=> $user->to_array()) );
  }
});
$app->put('/users/:id', function ($id) {
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $put['user'];
  if ($dev = User::find_by_id($id)) {
    $dev->name = $udev->name;
    $dev->fullname = $udev->fullname;
    $dev->ou_id = $udev->ou_id;
    // this gets boring. improve.
    $dev->is_enabled = $udev->is_enabled;
    $dev->is_admin = $udev->is_admin;
    $dev->can_control = $udev->can_control;
    $dev->save();
    echo json_encode( array('user'=> $dev->to_array()) );
  }
});
$app->post('/users', function () {
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $put['user'];
  if ($dev = new User) {
    $dev->name = $udev->name;
    $dev->fullname = $udev->fullname;
    $dev->ou_id = $udev->ou_id;
    // this gets boring. improve.
    $dev->is_enabled = $udev->is_enabled;
    $dev->is_admin = $udev->is_admin;
    $dev->can_control = $udev->can_control;
    $dev->save();
    echo json_encode( array('user'=> $dev->to_array()) );
  }
});
$app->delete('/users/:id', function ($id) {
  if ($dev = User::find_by_id($id)) {
    Optionset::query('PRAGMA foreign_keys = ON;');
    $dev->delete();
    echo '{}';
  }
});

/**************** AMT Optionsets *********************************************/

$app->get('/optionsets', function () {
  $result = array('optionsets'=>array());
  foreach (Optionset::all(array("order" => "name asc")) as $record) {
    $r = $record->to_array();
    $result['optionsets'][] = $r;
  }
  echo json_encode( $result );
});
$app->get('/optionsets/:id', function ($ouid) use ($app) {
  if ($os = Optionset::find($ouid)) {
    echo json_encode( array('optionset'=> $os->to_array()) );
  }
});
$app->put('/optionsets/:id', function ($id) {
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $put['optionset'];
  if ($dev = Optionset::find_by_id($id)) {
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
    $dev->opt_maxthreads = $udev->opt_maxthreads;
    $dev->opt_passfile = $udev->opt_passfile;
    $dev->opt_timeout = $udev->opt_timeout;
    $dev->save();
    echo json_encode( array('optionset'=> $dev->to_array()) );
  }
});
$app->delete('/optionsets/:id', function ($id) {
  if ($dev = Optionset::find_by_id($id)) {
    Optionset::query('PRAGMA foreign_keys = ON;');
    $dev->delete();
    echo '{}';
  }
});
$app->post('/optionsets', function () use ($app) {
  $post = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $post['optionset'];
  if ($dev = new Optionset) {
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
    $dev->opt_maxthreads = $udev->opt_maxthreads;
    $dev->opt_passfile = $udev->opt_passfile;
    $dev->opt_timeout = $udev->opt_timeout;
    $dev->save();
    echo json_encode( array('optionset'=> $dev->to_array()) );
  }
});

/*****************************************************************************/
/*
 *
 * ... run, forrest, run!
 */

$app->run();

