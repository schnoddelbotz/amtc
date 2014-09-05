<?php

/*
 * amtc-web index.php
 *
 * This file is OPTIONAL (and could be dropped if in doubt...).
 * On webservers that are configured to consider index.php
 * before index.html (order in DirectoryIndex directive for apache),
 * this script will only ensure to redirect freshmen users to
 * setup / DB installation if no configuration file is found.
 * Without this file, users have to call setup.php manually.
 *
 * ...Couldn't this be done by using a RewriteRule...? :-/
 * ...fixme: http/https
 */

if (!file_exists('data/siteconfig.php'))
 header( 'Location: http://' . $_SERVER['SERVER_NAME'].
          $_SERVER['REQUEST_URI'] . 'setup.php' );
else
 header( 'Location: http://' . $_SERVER['SERVER_NAME'].
          $_SERVER['REQUEST_URI'] . 'index.html' );

