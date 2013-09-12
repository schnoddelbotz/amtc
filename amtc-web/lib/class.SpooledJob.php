<?php
/*
 * class.SpooledJob.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * provides functions to process the job spool queue and and jobs
 * todo: add notBefore optional arg for delayed jobs
 * todo: add repeatEvery [01234567]
 * todo: add lastResult in table in json
 */

namespace amtcweb;

class SpooledJob {

  static function processQueue() {
    $queue = Array();
    if (file_exists(SPOOL_LOCK))
      return;
    touch(SPOOL_LOCK);
    $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
    $sql = "SELECT * FROM jobs WHERE cmd_state=0 AND date(createdat)='".date('Y-m-d')."'";
      // select * from jobs where createdat=today and cmd_state=0
    foreach ($db->query($sql) as $row) {
      $queue[] = $row;
    }
    if (count($queue)>0)
    foreach ($queue as $r=>$row) {
      if (!($rinfo = Room::getRoom($row['room']))) {
        $db->exec('UPDATE jobs SET cmd_state=99,startedat="'.date("Y-m-d H:i:s").'" WHERE id='.$row['id']);
        continue;
      }
      $amtc_opts = $rinfo['amt_version'] > 8 ? '-dw ' : '-w ';
      $db->exec('UPDATE jobs SET cmd_state=1,startedat="'.date("Y-m-d H:i:s").'" WHERE id='.$row['id']);
      amtc::run($row['amtc_cmd'], $amtc_opts.$row['amtc_delay'], $row['amtc_hosts']);
      $db->exec('UPDATE jobs SET cmd_state=2,doneat="'.date("Y-m-d H:i:s").'" WHERE id='.$row['id']);
    }
    unlink(SPOOL_LOCK);
    return Array('');
  }

  static function submitJob($room,$cmd,$hosts,$delay) {
    if (!Room::getRoom($room))
      throw new \Exception("No such room");
    $user = (@$_SERVER['USER'])?$_SERVER['USER']:'NULLAUTH';
    $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
    $qry = $db->prepare('INSERT INTO jobs VALUES (?,?,?,?,?,?,?,?,?,?)');
    $qry->execute(array(NULL,0,date("Y-m-d H:i:s"),$user,$cmd,$hosts,NULL,NULL,$delay,$room));
    return Array('room'=>$room,'cmd'=>$cmd,'hosts'=>$hosts,'delay'=>$delay);
  }

}
