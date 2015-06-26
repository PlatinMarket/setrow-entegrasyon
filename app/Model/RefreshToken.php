<?php

App::uses('AppModel', 'Model');

class RefreshToken extends AppModel
{
  public $belongsTo = array('Customer');
}
