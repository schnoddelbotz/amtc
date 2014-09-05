<?php
// this might to be split into /display/api.php, /admin/api.php ...?
// do this to trigger async REST issues...:
// sleep(2);

date_default_timezone_set('Europe/Berlin');

require 'lib/Slim/Slim.php';
require 'lib/php-activerecord/ActiveRecord.php';

\Slim\Slim::registerAutoloader();

ActiveRecord\Config::initialize(function($cfg)
{
   $cfg->set_model_directory('lib/db-model');
   $cfg->set_connections(
     array(
       //'development' => 'mysql://username:password@localhost/production_database_name'
      'development' => 'sqlite://unix(/scratch/me.db)'
     )
   );
   //$cfg->set_default_connection('production');
});

$app = new \Slim\Slim();
$app->response()->header('Content-Type', 'application/json;charset=utf-8');

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

// Notifications / Short user messages for dashboard
$app->get('/notifications', function () {
  sleep(2); // just to test the spinner ...
  $result = array('notifications'=>array());
  foreach (Notification::all() as $record) { $result['notifications'][] = $record->to_array(); }
  echo json_encode( $result );
});

// OUs / Rooms
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
  //$post = json_decode(file_get_contents("php://$input"));
  $put = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $udev = $put['ou'];
  if ($dev = OU::find_by_id($id)) {
    // this should be a foreach...? but ember currently submits ou_path, which is computed :-/
    $dev->name = $udev->name;
    $dev->description = $udev->description;
    $dev->parent = $udev->parent;
    // is there something like $dev->isDirty? if so...:
    $dev->save();
    echo json_encode( array('ou'=> $dev->to_array()) );
  }
});
// this should live in admin/index.php or such?
$app->post('/ous', function () use ($app) {
  $post = get_object_vars(json_decode(\Slim\Slim::getInstance()->request()->getBody()));
  $ndev = $post['ou'];
  if ($dev = new OU) {
    // this should be a foreach...? but ember currently submits ou_path, which is computed :-/
    $dev->name = $ndev->name;
    $dev->description = $ndev->description;
    $dev->parent = $ndev->parent;
    //$dev->ou_id = 1; // FIXME
    // is there something like $dev->isDirty? if so...:
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



/*
 *
 * ... run, forrest, run!
 */
$app->run();
