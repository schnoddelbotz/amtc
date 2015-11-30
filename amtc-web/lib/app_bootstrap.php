<?php
/*
 * lib/app_bootstrap.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * Try to load config, require libs, initialzie ORM and Slim
 */

define('AMTC_WEBROOT', dirname(__FILE__).'/..');
define('AMTC_CFGFILE', AMTC_WEBROOT.'/config/siteconfig.php');

if (file_exists(AMTC_CFGFILE))
  include AMTC_CFGFILE; // to let static ember help pages work even if unconfigured

date_default_timezone_set( defined('AMTC_TZ') ? AMTC_TZ : 'Europe/Berlin');

set_include_path(get_include_path().PATH_SEPARATOR.
  AMTC_WEBROOT.'/lib'.PATH_SEPARATOR.AMTC_WEBROOT.'/lib/db-model');
spl_autoload_extensions('.php');
spl_autoload_register();

require 'idiorm.php';
require 'paris.php';
require 'Slim/Slim.php';

// Initialize http://j4mie.github.io/idiormandparis/
if (defined("AMTC_PDOSTRING")) {
  ORM::configure(AMTC_PDOSTRING);
  ORM::configure('username', AMTC_DBUSER);
  ORM::configure('password', AMTC_DBPASS);
  // small hack to enforce constraint checking for sqlite; requires 3.6+
  if (substr(AMTC_PDOSTRING,0,6)=='sqlite') {
    ORM::raw_execute('PRAGMA foreign_keys = ON');
  }
}

// Initialize http://www.slimframework.com/
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app->config('debug', false); // false enables custom error handler below
$app->response()->header('Content-Type', 'application/json;charset=utf-8');
$app->notFound(function () {
  echo json_encode(Array('error'=>'Not found'));
});
$app->error(function (\Exception $e) {
  echo strlen($e->getMessage()) >  128 ? substr($e->getMessage(),0,128).'...' : $e->getMessage();
});

class SlimUtil {
  function getSubmit(Slim\Slim $_app,$key) {
    $ret = null;
    $data = get_object_vars(json_decode($_app->request()->getBody()));
    if (isset($data[$key]))
      $ret = get_object_vars($data[$key]);
    return $ret;
  }
}
