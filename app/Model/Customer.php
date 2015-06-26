<?php

App::uses('AppModel', 'Model');

class Customer extends AppModel
{

  public $hasOne = array("Setrow");

  // HasMany Relation
  public $hasMany = array(
    'AccessToken' => array('dependent' => true),
    'RefreshToken' => array('dependent' => true)
  );

}
