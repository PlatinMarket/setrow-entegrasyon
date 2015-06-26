<?php

App::uses('AppModel', 'Model');

class Setrow extends AppModel
{
  public $useTable = 'setrow';

  public $belongsTo = array("Customer");
}
