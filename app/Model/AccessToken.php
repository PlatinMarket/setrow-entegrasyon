<?php

App::uses('AppModel', 'Model');

class AccessToken extends AppModel
{
  public $belongsTo = array('Customer');
}
