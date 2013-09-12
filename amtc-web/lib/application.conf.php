<?php
/*
 * router.conf.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 * 
 * a simplistic request router setup for amtc-web.
 * defines valid requests, their parameters and processing methods.
 * this file should need no customizations for out-of-the-box-use.
 */

$regex_roomname = '/^([A-Za-z0-9_-]+)$/';

$requestRouterConfig = Array(
  // define actions. first arg on CLI or ?action= for GET
  // static method called will be \amtcweb\<array_value>::<array_key>
  'actions' => Array(
    'getRooms'    => 'Room',
    'getRoom'     => 'Room',
    'config'      => 'FrontendCtrl',
    'processQueue'=> 'SpooledJob',
    'submitJob'   => 'SpooledJob',
    'getState'    => 'StateMonitor',
    'logState'    => 'StateMonitor',
  ),
  // args - arguments per action.
  // any named args are required per default.
  // arg order IS important (i.e. do NOT play with it),
  // as args passed to methods as indexed array
  'args' => Array(
    'usage'       => Array(),
    'getRooms'    => Array(),
    'processQueue'=> Array(),
    'logState'    => Array('roomname'=>$regex_roomname),
    'getRoom'     => Array('roomname'=>$regex_roomname, 
                           'fullResponse'=>'/^(yes|no)$/'),
    'getState'    => Array('roomname'=>$regex_roomname,
                           'mode'    =>'/^(live|log)$/',
                           'date'    =>'/^(\d{4}-\d{2}-\d{2})$/'
    ),
    'submitJob'   => Array('roomname'=>$regex_roomname,
                           'cmd'     =>'/^([IUDCR])$/',
                           'hosts'   =>'/^([a-zA-Z0-9-\.\s]+)$/',
                           'delay'   =>'/^(\d+)$/',
    ),
    'config'      => Array('mode'    =>'/^(site|db|rooms|scheduler)$/',
                           'do'      =>'/^(submit)$/', 
                           'roomname'=>$regex_roomname,
                           'editRoomname'=>$regex_roomname,
                           'newRoomname'=>$regex_roomname,
                           'amtVersion'=>'/^([0-9])$/',
                           'idle_power'=>'/^([0-9]*\.?[0-9]+)$/',
                           'deletePc'=>'/^([0-9]{1,9})$/',
                           'createPc'=>'/^([A-Za-z0-9\.-]{1,64})$/',
                           'deleteRoom'=>$regex_roomname,
    ),
  ),
  // to mark any action argument as optional, name it here...
  // array key is option name, value can be default value
  'optional_args' => Array(
    'getState'  => Array('mode'=>'live', 'date'=>date('Y-m-d')),
    'config'    => Array('do'=>false, 'roomname'=>'', 'newRoomname'=>'',
                         'editRoomname'=>'', 'amtVersion'=>'','idle_power'=>'',
                         'deletePc'=>'','createPc'=>'', 'deleteRoom'=>'' ),
    'getRoom'   => Array('fullResponse'=>'yes'),
  ),
  // restrictions per action
  'action_deny_cli' => Array(),
  'action_deny_web' => Array('logState','processQueue'),
  'action_requires_admin'=> Array('config','submitJob','logState'),
);

/*
 * default site configuration values.
 * you're encouraged to not modify things here, but using
 * $siteconfig['...'] = ...; in ../var/siteconfig.php.
 * ../var/siteconfig.php can be managed using the 'Site settings'
 * tab in amtc-web's configuration frontend.
 */
$siteconfigDefaults = Array(
  'db_dsn'      => 'sqlite:'.APP_ROOT.'/var/amtc-web.db',
  'db_user'     => '',
  'db_pass'     => '',
  'amtc_exe'    => '/usr/bin/amtc',
  'amtc_opts'   => '-m 170 -jsrt 4',
  'amt_pw_file' => '/var/amtc/amtcpasswd',
  'spool_lock'  => '/tmp/amtc-web-spool.lock',
);
