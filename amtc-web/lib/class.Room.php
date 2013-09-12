<?php
/*
 * class.Room.php - part of amtc-web, part of amtc
 * https://github.com/schnoddelbotz/amtc
 *
 * retreive list of rooms, PCs inside rooms, ...
 */

namespace amtcweb;

class Room {

  static function create($newRoomname,$amt_version,$idle_power) {
    $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $qry = $db->prepare('INSERT INTO rooms VALUES (NULL,?,?,?)');
    $qry->execute(array($newRoomname,$amt_version,$idle_power));
  }

  static function modify($roomname,$editRoomname,$amt_version,$idle_power) {
    $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $qry = $db->prepare('UPDATE rooms set roomname=?,has_amt=?,avg_pwr_on=? WHERE roomname=?');
    //$qry->debugDumpParams(); // use cli only
    $qry->execute(array($editRoomname,$amt_version,$idle_power,$roomname));
    return $qry->rowCount();
  }

  static function delete($name) {
    $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $qry = $db->prepare('DELETE FROM rooms WHERE roomname=?');
    $qry->execute(array($name));
    return $qry->rowCount()==1?true:false;
    //fixme: del PCs in room, log?
  }

  static function createPc($roomid,$hostname) {
    $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $qry = $db->prepare('INSERT INTO pcs VALUES (NULL,?,?,?)');
    $qry->execute(array($roomid,$hostname,'unused'));
    return true;
  }

  // should all read... $pc = new Pc()->byName("xxx")->delete();
  static function deletePc($id) {
    $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $qry = $db->prepare('DELETE FROM pcs WHERE id=?');
    $qry->execute(array($id));
    return $qry->rowCount()==1?true:false;
  }


  static function getRoom($roomname,$fullResponse=false,$roomOnly=false) {
    $rooms = self::getRooms(false,$roomOnly);
    if (@$rooms[$roomname]) {
      return $fullResponse ?
              FrontendCtrl::createResponse(true, $rooms[$roomname], 'getRoom OK') :
              $rooms[$roomname];
    } else {
      throw new \Exception("Invalid room $roomname");
    }
  }

  static function getRooms($fullResponse=true,$roomsOnly=false) {
    $db = new \PDO(DB_DSN,DB_USER,DB_PASS);
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $sql = $roomsOnly ?
           'SELECT id as rid,* FROM rooms' :
           'SELECT p.id as pcid,r.id as rid,* '.
           'FROM rooms r,pcs p WHERE r.has_amt>0 AND rid=p.roomid';
    $rooms = Array();
    if ($roomsOnly) {
      foreach ($db->query($sql) as $row) {
        $rooms[$row['roomname']] = $row;
      }
      return $fullResponse ?
            FrontendCtrl::createResponse($success, $rooms, 'getRooms OK') :
            $rooms;
    }
    foreach ($db->query($sql) as $row) {
        $rooms[$row['roomname']]['id'] = $row['rid'];
        $rooms[$row['roomname']]['amt_version'] = $row['has_amt'];
        $rooms[$row['roomname']]['hosts'][$row['pcid']] = $row['ip'];
        $rooms[$row['roomname']]['ip2idmap'][$row['ip']] = $row['pcid'];
    }
    foreach ($rooms as $name=>$roomdata) {
        $rooms[$name]['hostlist'] = implode(' ',$rooms[$name]['hosts']);
    }
    $success = count($rooms)>0 ? true : false;
    return $fullResponse ?
            FrontendCtrl::createResponse($success, $rooms, 'getRooms OK') :
            $rooms;
  }

    
}
