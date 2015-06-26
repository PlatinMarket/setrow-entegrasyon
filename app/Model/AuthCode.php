<?php

App::uses('AppModel', 'Model');

class AuthCode extends AppModel
{
  public $belongsTo = array('Customer');
}
