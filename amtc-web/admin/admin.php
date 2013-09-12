<?php
/*
 * admin.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * this script can/should be access protected using .htaccess.
 * any method defined as action_requires_admin in router config
 * has to be called thorugh this script (or will fail with
 * insufficient permissions exception).
 * CLI scripts have admin state by default, too. 
 */

require(dirname(__FILE__).'/../amtc-web.php');

