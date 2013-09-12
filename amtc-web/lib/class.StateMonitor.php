<?php
/*
 * class.StateMonitor.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * provides monitoring functions: live scan, get log, update log...
 */

namespace amtcweb;

class StateMonitor {
 
  static function getState($roomname,$mode,$date) {
    return ($mode=='live') ?
              amtc::getRoomState($roomname) : 
              self::getDayLog($roomname,$date);
  }

  static function getDayLog($roomname,$date=false) {
    $r = array();
    $date = $date ? $date : date("Y-m-d");
    $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
    $sql = "SELECT p.ip,state_begin,state_amt,open_port ".
           "FROM logdata l,pcs p,rooms r ".
           "WHERE l.pcid=p.id AND p.roomid=r.id AND r.roomname='".$roomname."' AND ".
           "date(l.logdate)='".$date."' ORDER BY p.ip,state_begin ASC";
    foreach ($db->query($sql) as $row) {
      $r[] = $row;
    }
    return $r;
  }

  static function getLastState($roomName) {
    $state = array();
    $r = Room::getRoom($roomName);
    if (!$r)
      return;
    $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
    $sql = sprintf("SELECT * FROM logdata,pcs WHERE date(logdate)='%s' ".
                   "AND pcs.id=logdata.pcid AND logdata.pcid IN (%s)",
                      date('Y-m-d'),implode(',',array_keys($r['hosts'])));
    $portmap = array(3389=>'rdp',22=>'ssh',0=>'none',16992=>'none'/*bkwrd cmpt*/);
    foreach ($db->query($sql) as $row) {
      $state[ $row['ip'] ]['oport'] = $portmap[$row['open_port']];
      $state[ $row['ip'] ]['http'] = $row['state_http'];
      $state[ $row['ip'] ]['amt'] = $row['state_amt'];
    }
    return $state;
  }

  static function logState($roomname) {
    $num_updates = 0;
    $room = Room::getRoom($roomname);
    $rportmap = array('ssh'=>22, 'rdp'=>'3389', 'none'=>'0', 'skipped'=>'0');
    if ($room) {
      $past = self::getLastState($roomname);
      $now = amtc::getRoomState($roomname);
      $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
      $qry = $db->prepare('INSERT INTO logdata VALUES (?,?,?,?,?,?)');
      foreach ($now['data'] as $host=>$state) {
        if ( !(
          (@$past[$host]['amt'] == $state->amt) &&
          (@$past[$host]['http'] == $state->http) &&
          ($rportmap[@$past[$host]['oport']] == $rportmap[$state->oport])
        ) ) {
          $qry->execute(array($room['ip2idmap'][$host], date("Y-m-d H:i:s"),
                              $rportmap[$state->oport], date('G')*60+date('i'),
                              $state->amt, $state->http));
          $num_updates++;
        }
      }
    }
    return FrontendCtrl::createResponse(true,Array(),"Log updated, $num_updates updates.");
  }
          
}
