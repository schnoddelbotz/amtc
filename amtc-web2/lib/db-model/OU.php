<?php

/**
* Represents an OU
*/
class OU extends ActiveRecord\Model
{
  static $belongs_to = array(
    array('parent_id', 'class_name' => 'OU', 'foreign_key'=>'parent_id'),
  );

  static $has_many = array(
    array('children', 'class_name' => 'OU', 'foreign_key' => 'parent_id'),
  );


  /*
   * will return a path-style string representation of an OU path
   * e.g. /Customer/Floor/...
   * might be ugly, (not only) in a phpAR sense, sorry ... 
   */
  function getPathString() {
    $p[] = $this->name;
    $has_parent = Ou::exists(array('id'=>$this->parent_id));
    $parent_id = $this->parent_id;
    while ($has_parent) {
      $n = Ou::find($parent_id);
      $parent_id = $n->parent_id;
      array_unshift($p,$n->name);
      $has_parent = Ou::exists(array('id'=>$n->parent_id));
    }
    return implode('/', $p);
  }


  /*
   * returns a top-down nested tree, starting at $startPid.
   * The 'root' level element MUST have id=1 and parent_id=NULL.
   */
  static function getTree($startPid=1) {
    if ($s = OU::find($startPid)) {
      $r = $s->to_array();
    }
    $r['children'] = self::_getTree($startPid);
    return $r;
  }

  static function _getTree($startPid=1) {
    $z = OU::find($startPid);
    $x = 0;
    $n = array();
    foreach ($z->children as $child) {
      $n[$x] = $child->to_array();
      $n[$x]['children'] = self::_getTree($child->id);
      $x++;
    }
    return $n;
  }

  /*
   * static function getByPath() {
   *   ... may be useful ?
   * }
   */

}
