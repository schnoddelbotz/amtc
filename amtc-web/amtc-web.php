<?php
/*
 * amtc-web.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * this script serves as application entry point for web and CLI.
 * to use it via CLI and gain admin privs, run it via admin/amtc-web.phpsh.
 * any web actions that require admin privs (defined in application.conf.php) 
 * have to come in via admin/admin.php. to authenticate 'admins' via web,
 * place a .htaccess file in admin/ that will serve to restrict access.
 */

namespace amtcweb;

define('APP_ROOT', dirname(__FILE__));
@include('var/siteconfig.php');
date_default_timezone_set(@$siteconfig['timezone'] ?
                           $siteconfig['timezone'] : 'Europe/Berlin');

require('lib/application.conf.php');
require('lib/class.FrontendCtrl.php');
require('lib/class.StateMonitor.php');
require('lib/class.SpooledJob.php');
require('lib/class.Room.php');
require('lib/class.amtc.php');

$ctrl = new FrontendCtrl($requestRouterConfig,
                        @$siteconfig,$siteconfigDefaults);

try {
  $ctrl->getAction();
  $ctrl->getArgs();
  $ctrl->verifyPermission();
  $result = $ctrl->processRequest();
  echo $ctrl->renderContent($result);
} catch (\Exception $e) {
  echo $ctrl->renderContent(FrontendCtrl::createResponse(
                            false,NULL,$e->getMessage()));
}

