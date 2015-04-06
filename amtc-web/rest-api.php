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
$allowUnauthenticated = Array('authenticate', 'rest-config.js', 'pages',
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
        $x = array("result"=>"success");
        $_SESSION['authenticated'] = true;
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
  // fixme:
  $data['memfree'] = '?? gb';
  $data['lastmonitoring'] = rand(1,32000);
  $data['activejobs'] = rand(1,32000);
  $data['activeprocesses'] = rand(1,32000);
  $data['monitorcount'] = rand(1,32000);
  // add -V to amtc... or improve otherwise
  $av = shell_exec(AMTC_BIN);
  $av = preg_replace("/.*amtc v([^\s]+).*/ms","$1",$av);
  $data['amtcversion'] = $av;
  //
  $logfile = AMTC_DATADIR.'/amtc-web-cli.log';
  $data['logsize'] = 'file does not exist';
  $data['logmodtime'] = 'n/a';
  if (file_exists($logfile)) {
    $fstat = stat($logfile);
    $data['logsize'] = sprintf('%0.3f MB', $fstat[7]/1024/1024);
    $data['logmodtime'] = $fstat[9];
  }

  $result = array('systemhealth'=>$data);
  echo json_encode($result);
});

// DB-Model requests

/**************** Notifications / Short user messages for dashboard **********/

$app->get('/notifications', function () {
  $result = array('notifications'=>array());
  foreach (Notification::limit(15)->order_by_desc('tstamp')->find_many() as $record) { $result['notifications'][] = $record->as_array(); }
  echo json_encode( $result );
});

/**************** OUs / Rooms ************************************************/

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
$app->get('/ous/:id', function ($ouid) {
  if ($ou = OU::find_one($ouid)) {
    echo json_encode( array('ou'=> $ou->as_array()) );
  }
});
$app->put('/ous/:id', function ($id) use ($app) {
  if (($dev = OU::find_one($id)) && ($data = SlimUtil::getSubmit($app,'ou'))) {
    unset($data['ou_path']); // computed/displayed property ... avoid sending
    $dev->set($data);
    $dev->save();
    echo json_encode( array('ou'=> $dev->as_array()) );
  }
});
$app->post('/ous', function () use ($app) {
  if (($dev = OU::create()) && ($data = SlimUtil::getSubmit($app,'ou'))) {
    unset($data['ou_path']); // computed/displayed property ... avoid sending
    $dev->set($data);
    $dev->save();
    echo json_encode( array('ou'=> $dev->as_array()) );
  }
});
$app->delete('/ous/:id', function ($id) {
  if ($dev = OU::find_one($id)) {
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
  if (($dev = Host::create()) && ($data = SlimUtil::getSubmit($app,'host'))) {
    $dev->ou_id = $data['ou_id'];
    $dev->hostname = $data['hostname'];
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

/**************** Users ******************************************************/

$app->get('/users', function () {
  $result = array('users'=>array());
  foreach (User::order_by_asc('name')->find_many() as $record) {
    $r = $record->as_array();
    $result['users'][] = $r;
  }
  echo json_encode( $result );
});
$app->get('/users/:id', function ($uid) {
  if ($user = User::find_one($uid)) {
    echo json_encode( array('user'=> $user->as_array()) );
  } elseif ($user = User::where('name',$uid)->find_one()) {
    echo json_encode( array('user'=> $user->as_array()) );
  }
});
$app->put('/users/:id', function ($id) use ($app) {
  if (($dev = User::find_one($id)) && ($data = SlimUtil::getSubmit($app,'user'))) {
    $dev->set($data);
    $dev->save();
    echo json_encode( array('user'=> $dev->as_array()) );
  }
});
$app->post('/users', function () use ($app) {
  if (($dev = User::create()) && ($data = SlimUtil::getSubmit($app,'user'))) {
    $dev->set($data);
    $dev->save();
    echo json_encode( array('user'=> $dev->as_array()) );
  }
});
$app->delete('/users/:id', function ($id) {
  if ($dev = User::find_one($id)) {
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
$app->get('/optionsets/:id', function ($ouid) {
  if ($os = Optionset::find_one($ouid)) {
    echo json_encode( array('optionset'=> $os->as_array()) );
  }
});
$app->put('/optionsets/:id', function ($id) use ($app) {
  if (($dev = Optionset::find_one($id)) && ($data = SlimUtil::getSubmit($app,'optionset'))) {
    $dev->set($data);
    $dev->save();
    echo json_encode( array('optionset'=> $dev->as_array()) );
  }
});
$app->delete('/optionsets/:id', function ($id) {
  if ($dev = Optionset::find_one($id)) {
    $dev->delete();
    echo '{}';
  }
});
$app->post('/optionsets', function () use ($app) {
  if (($dev = Optionset::create()) && ($data = SlimUtil::getSubmit($app,'optionset'))) {
    $dev->set($data);
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
$app->put('/jobs/:id', function ($id) use ($app) {
  if (($job = Job::find_one($id)) && ($data = SlimUtil::getSubmit($app,'job'))) {
    $job->set($data);
    $job->save();
    echo json_encode( array('job'=> $job->as_array()) );
  }
});
$app->delete('/jobs/:id', function ($id) {
  if ($job = Job::find_one($id)) {
    $job->delete();
    echo '{}';
  }
});
$app->post('/jobs', function () use ($app) {
  if (($job = Job::create()) && ($user = SlimUtil::getSubmit($app,'job'))) {
    if (isset($user['hosts'])) {
      $hosts = $user['hosts'];
      unset($user['hosts']); // rcvd: array "hosts", need: string "amtc_hosts"
    }
    $job->set($user);
    $job->user_id     = 1; // FIXME!!
    if (is_array($hosts))
      $job->amtc_hosts = preg_replace('/[^\d,]/','',implode(',',$hosts));
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
