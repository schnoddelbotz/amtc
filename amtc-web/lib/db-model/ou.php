<?php

/**
* Represents an OU
*/
class OU extends Model
{

/* php-activerecord ... replace by:
   http://paris.readthedocs.org/en/latest/associations.html

  static $belongs_to = array(
    array('parent_id', 'class_name' => 'OU', 'foreign_key'=>'parent_id'),
  );

  static $has_many = array(
    array('children', 'class_name' => 'OU', 'foreign_key' => 'parent_id'),
  );
*/

  public function hosts() {
      return $this->has_many('Host'); // Note we use the model name literally - not a pluralised version
  }

}
