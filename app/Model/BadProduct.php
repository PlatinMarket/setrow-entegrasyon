<?php

App::uses('AppModel', 'Model');

class BadProduct extends AppModel
{
  public $belongsTo = array('Customer');

}
