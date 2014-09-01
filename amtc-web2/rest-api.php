<?php
// this might to be split into /display/api.php, /admin/api.php ...?
// do this to trigger async REST issues...:
//sleep(2);

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
  $result = array('notifications'=>array());
  foreach (Notification::all() as $record) { $result['notifications'][] = $record->to_array(); }
  echo json_encode( $result );
});



/*
 *
 * ... run, forrest, run!
 */
$app->run();

