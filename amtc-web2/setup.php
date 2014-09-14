<?php

// this thing should provide useful test results to the #setup page 
// (tests: supported PDO drivers, writable directory, chk cfg provided...).
// it currently, quite blindly, only accepts submitted config data.
// YEAH!!! neeeds cleanup/fix!! ... nope ... TRASH. soon.

// 
if (file_exists("data/siteconfig.php")) {
  echo 'INSTALLTOOL_LOCKED';
  exit(1);
}

error_reporting(E_NONE);

$cfgFile = "data/siteconfig.php";
if ($_POST && !file_exists($cfgFile)) {
  header('Content-Type: application/json;charset=utf-8');
  $x = array("message"=>"Configuration written successfully", "data"=>$_POST);
  $cfgTpl = '<?php define("AMTC_PDOSTRING", \'%s\'); ';
  $pdoStr = addslashes($_POST['pdoString']);
  $sqlitePath = $_POST['sqlitePath'];
  $selectedDB = strtolower($_POST['selectedDB']);

  if ($selectedDB == 'sqlite') {
    // create db if non-existant
    @touch($sqlitePath);
  }
  $sqliteRealPath = $sqlitePath;
  if (substr($sqlitePath,0,1)!=='/') {
    $sqliteRealPath = realpath($sqlitePath);
  }
  $phpArPdoString = $pdoStr;
  $phpArPdoString = preg_replace('@^sqlite:(.*)@', "sqlite://unix(".$sqliteRealPath.")", $phpArPdoString);

  $cfg = sprintf($cfgTpl, $phpArPdoString);
  if (!is_writable(dirname($cfgFile))) {
    $x = array("errorMsg"=>"Data directory not writable!");
  } elseif (false === file_put_contents($cfgFile, $cfg)) {
    $x = array("errorMsg"=>"Could not!");
  } else {
    $dbh = new PDO($pdoStr);
    $dbh->exec(file_get_contents('lib/db-model/install-db/'.$selectedDB.'.sql'));
    $dbh->exec(file_get_contents('lib/db-model/install-db/'.$selectedDB.'-minimal.sql')); 
    
    if ($_POST['importDemo']=='true'/* yes, a string. fixme */)
      $dbh->exec(file_get_contents('lib/db-model/install-db/'.$selectedDB.'-exampledata.sql')); 
      
    // fixme: add _htaccess thing ... and some sanitization ... and error checking ... and stuff.
  }
  echo json_encode($x);
  exit(0);  
}



