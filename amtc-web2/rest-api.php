<?php
// this might to be split into /display/api.php, /admin/api.php ...?
// do this to trigger async REST issues...:
// sleep(2);

@include 'data/siteconfig.php'; // to let static ember help pages work event without DB
// FIXME date_default_timezone_set( @isset(AMTC_TZ) ? AMTC_TZ : 'Europe/Berlin');

require 'lib/php-activerecord/ActiveRecord.php';
require 'lib/Slim/Slim.php';

// Initialize http://www.slimframework.com/
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app->response()->header('Content-Type', 'application/json;charset=utf-8');

/*
  FIXME to get rid of index.php:
  if (!defined(AMTC_PDOSTRING) && not a request to /pages) {
    $result = array('error'=>'Not configured yet.');
    echo json_encode( $result ); // <- to be catched by ember index.html app -> redir 2 setup.php
  }
  // index.html already does this (redir to setup.php) now on failure in ou-tree route
*/

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

/* 
 *  Non-DB-Model requests 
 */
 
// provide URI for ember-data REST adapter, based on this php script's location
$app->get('/rest-config.js', function () use ($app) {    
    $app->response->header('Content-Type', 'application/javascript;charset=utf-8');
    $path = substr($_SERVER['SCRIPT_NAME'],1);
    echo "DS.RESTAdapter.reopen({\n";
    echo " namespace: '$path'\n";
    echo "});\n";
});

// Return static markdown help pages, json encoded
$app->get('/pages/:id', function ($id) {
    if ($page = sprintf("%d", $id)) {     
      $file = sprintf("pages/%d.md", $page);
      $contents = 'Not found';
      is_readable($file) && $contents = file_get_contents($file);
      echo json_encode( array('page'=>array(
        'id' => $page,
        'page_name' => 'unused',
        'page_title' => 'unused',
        'page_content' => $contents
      )));
    }
});

/* 
 *  DB-Model requests 
 */

/**************** Notifications / Short user messages for dashboard **********/

$app->get('/notifications', function () {
  sleep(2); // just to test the spinner ...
  $result = array('notifications'=>array());
  foreach (Notification::all(array("order" => "tstamp desc", 'limit' => 50)) as $record) { $result['notifications'][] = $record->to_array(); }
  echo json_encode( $result );
});

/**************** OUs / Rooms ************************************************/

$app->get('/ous', function () {
  $result = array('ous'=>array());
  foreach (OU::all() as $record) {
    $r = $record->to_array();
    $r['ou_path'] = $record->getPathString();
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
$app->get('/ou-tree', function () use ($app) {
  echo json_encode( array('ous'=>OU::getTree(
      /* arg 1 is start PID 1 and defaults to one (/). */
      /* should be set to current user's 'home ou' */
  )));
});
$app->put('/ous/:id', function ($id) {
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $put['ou'];
  if ($dev = OU::find_by_id($id)) {
    $dev->name = $udev->name;
    $dev->description = $udev->description;
    $dev->parent = $udev->parent;
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
    $dev->parent = $ndev->parent;
    $dev->save();
    echo json_encode( array('device'=> $dev->to_array()) );
  }
});
$app->delete('/ous/:id', function ($id) {
    if ($dev = OU::find_by_id($id)) {
      echo json_encode( array('ou'=> $dev->to_array()) );
      $dev->delete();
    }
});

/**************** AMT Optionsets *********************************************/

$app->get('/optionsets', function () {
  $result = array('optionsets'=>array());
  foreach (Optionset::all() as $record) {
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
    $dev->save();
    echo json_encode( array('optionset'=> $dev->to_array()) );
  }
});
$app->delete('/optionsets/:id', function ($id) {
    if ($dev = Optionset::find_by_id($id)) {
      echo json_encode( array('optionset'=> $dev->to_array()) );
      $dev->delete();
    }
});

/*****************************************************************************/
/*
 *
 * ... run, forrest, run!
 */

$app->run();


