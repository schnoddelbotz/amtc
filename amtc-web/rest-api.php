<?php
/*
 * rest-api.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * Use http://www.slimframework.com/ and http://j4mie.github.io/idiormandparis/
 * to provide a REST backend for amtc-web (ember-data, installer, amtc ...)
 */

// set up autoloader, include required libs, init ORM & Slim
require 'lib/app_bootstrap.php';

// Initialize session
session_name("amtcweb");
session_start();

// Block access if unauthenticated - only permit some vital routes
$allowUnauthenticated = Array('authenticate', 'rest-config.js',
                              'phptests', 'submit-configuration');
$_route = explode('/', $app->request()->getPathInfo());
$route  = $_route[1];
if (!empty($_SESSION['authenticated']) && $_SESSION['authenticated'] === true ||
    in_array($route, $allowUnauthenticated )) {
    // Authenticated or request that is allowed without
} else {
  echo '{"notifications":[], "laststates":[], "error":"unauthenticated"}';
  // app->stop() will not work here yet ...
  return;
}

/**************** Only SLIM request handling below ***************************/

//
// DB-Model requests
//

// GET all records for given model
$app->get('/:model', function($model) {
  $result = array($model=>array());
  $query = Model::factory(substr(ucfirst($model),0,-1));
  switch ($model) {
    case 'notifications':
      $query->limit(15)->order_by_desc('tstamp');
    break;
    case 'hosts':
      $query->order_by_asc('hostname');
    break;
    case 'jobs':
      $query->order_by_asc('description');
    break;
    // same order by name for these...
    case 'users':
    case 'optionsets':
      $query->order_by_asc('name');
    break;
  }
  foreach ($query->find_many() as $record) {
    $result[$model][] = $record->as_array();
  }
  echo json_encode( $result );
})->conditions(array('model' => '(users|hosts|optionsets|jobs|notifications|laststates|logdays)'));

// GET single record by id
$app->get('/:model/:id', function($model,$id) {
  $singular = substr($model,0,-1);
  $query = Model::factory(ucfirst($singular));
  if ($result = $query->find_one($id)) {
    echo json_encode( array($singular=> $result->as_array()) );
  }
})->conditions(array('model' => '(users|hosts|optionsets|jobs|notifications|ous)'));

// DELETE single record
$app->delete('/:model/:id', function($model,$id) {
  $query = Model::factory(ucfirst(substr($model,0,-1)));
  if ($result = $query->find_one($id)) {
    $result->delete();
    // "Note: Although after destroyRecord or deleteRecord/save the adapter
    // expects an empty object e.g. {} to be returned from the server after
    //  destroying a record."
    // http://emberjs.com/guides/models/the-rest-adapter/
    echo '{}';
  }
})->conditions(array('model' => '(ous|users|optionsets|jobs|hosts)'));

// PUT / update single record
$app->put('/:model/:id', function($model,$id) use ($app) {
  $singular = substr($model,0,-1);
  $query = Model::factory(ucfirst($singular));
  if (($dev = $query->find_one($id)) && ($data = SlimUtil::getSubmit($app,$singular))) {
    if (isset($data['ou_path'])) {
      unset($data['ou_path']); // computed/displayed property ... avoid sending
    }
    $dev->set($data);
    $dev->save();
    echo json_encode( array($singular => $dev->as_array()) );
  }
})->conditions(array('model' => '(ous|users|optionsets|jobs)'));

// POST / create single record
$app->post('/:model', function($model) use ($app) {
  $singular = substr($model,0,-1);
  $query = Model::factory(ucfirst($singular));
  if (($dev = $query->create()) && ($data = SlimUtil::getSubmit($app,$singular))) {
    switch ($model) {
      case 'ous':
        unset($data['ou_path']);
        $dev->set($data);
      break;
      case 'hosts':
        $dev->ou_id = $data['ou_id'];
        $dev->hostname = $data['hostname'];
      break;
      case 'jobs':
        if (isset($data['hosts'])) {
          $hosts = $data['hosts'];
          unset($data['hosts']); // rcvd: array "hosts", need: string "amtc_hosts"
        }
        $dev->set($data);
        $dev->user_id     = 1; // TBD: Put correct userid here!
        if (is_array($hosts)) {
          $dev->amtc_hosts = preg_replace('/[^\d,]/','',implode(',',$hosts));
        }
      break;
      default:
        $dev->set($data);
    }
    $dev->save();
    echo json_encode( array($singular => $dev->as_array()) );
  }
})->conditions(array('model' => '(ous|hosts|users|optionsets|jobs)'));

// special case OUs: include child OUs in reply
$app->get('/ous', function () {
  $result = array('ous'=>array());
  foreach (OU::find_many() as $record) {
    $r = $record->as_array();
    $children = OU::where('parent_id', $r['id'])->find_many() ;
    $kids = array();
    foreach ($children as $childOu) {
      $kids[] = $childOu->id;
    }
    $r['children'] = $kids;
    $result['ous'][] = $r;
  }
  echo json_encode( $result );
});

// single-day statelogs for hosts of a single OU
$app->get('/statelogs/:ou/:startUnixTime', function ($ouid,$ctime) {
  $result = Array();
  $ou = OU::find_one($ouid);
  foreach ($ou->hosts()->order_by_asc('hostname')->find_many() as $host) {
    $hosts[] = $host->id;
  }
  // find 'previous-day[s]-state' for each host
  foreach ($hosts as $hostid) {
    $record = Statelog::where('host_id',$hostid)->
                        where_lt('state_begin',$ctime)->
                        order_by_desc('state_begin')->
                        limit(1)->find_one();
    if ($record)
      $result[] = $record->as_array();
  }
  foreach (Statelog::where_gt('state_begin',$ctime)
            ->where_lt('state_begin',$ctime+86400)
            ->where_in('host_id',$hosts)
            ->find_many() as $record) {
    $result[] = $record->as_array();
  }
  echo json_encode( $result );
});

//
//  Non-DB-Model requests
//

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
// Installer
$app->post('/submit-configuration', function () {
  echo AmtcwebInstaller::writeAmtcwebConfig($_POST);
});
// installation precondition tests
$app->get('/phptests', function () {
  echo AmtcwebInstaller::runPhpSetupTests();
});
// simple basic auth verification 'proxy'
$app->post('/authenticate', function () {
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
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status === 200) {
      if ($user = User::where('name',$wanted['username'])->find_one()) {
        $x = array("result"=>"success", "fullname"=>$user->fullname);
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $user->name;
        $_SESSION['userid']   = $user->id;
      } else {
        $x = array("exceptionMessage"=>"no-local-account");
      }
    } else
      sleep(2);
  }
  echo json_encode($x);
});
$app->get('/logout', function () {
  if ($_SESSION['authenticated']) {
    $x=array('message'=>'success');
  } else {
    $x=array('message'=>'no success');
  }
  session_destroy();
  echo json_encode($x);
});
// #/systemhealth status page
$app->get('/systemhealth', function () {
  $data['phpversion'] = PHP_VERSION;
  $data['uptime'] = trim(`uptime`);
  $data['datetime'] = strftime('%c');
  $data['diskfree'] = sprintf('%0.3f GB', disk_free_space(AMTC_DATADIR)/1024/1024/1024);
  $monitorJob = Job::find_one(1);
  $data['lastmonitoringstarted'] = $monitorJob->last_started;
  $data['lastmonitoringdone'] = $monitorJob->last_done;
  $data['activejobs'] = ORM::for_table('job')->where('job_status',Job::STATUS_RUNNING)->count();
  $data['activeprocesses'] = rand(1,32000); // tbd ...
  $data['monitorcount'] = ORM::for_table('statelog')->count();
  // add -V to amtc... or improve otherwise
  $av = shell_exec(AMTC_BIN);
  $av = preg_replace("/.*amtc v([^\s]+).*/ms","$1",$av);
  $data['amtcversion'] = $av;
  //
  $logfile = AMTC_DATADIR.'/amtc-web-cli.log';
  $data['logsize'] = 'file does not exist';
  $data['logmodtime'] = false;
  if (file_exists($logfile)) {
    $fstat = stat($logfile);
    $data['logsize'] = sprintf('%0.3f MB', $fstat[7]/1024/1024);
    $data['logmodtime'] = $fstat[9];
  }

  $result = array('systemhealth'=>$data);
  echo json_encode($result);
});
// #/systemhealth actions
$app->get('/flushStatelog', function () {
  // tbd admin only...
  if (ORM::for_table('statelog')->delete_many()) {
    echo json_encode(Array('success'=>'success'));
  }
});
$app->get('/resetMonitoringJob', function () {
  // tbd admin only and id 1 :/
  if ($monitoringJob = ORM::for_table('job')->find_one(1)) {
    $monitoringJob->job_status = Job::STATUS_PENDING;
    $monitoringJob->save();
    echo json_encode(Array('success'=>'success'));
  }
});


/*****************************************************************************/
/*
 *
 * ... run, forrest, run!
 */

$app->run();
