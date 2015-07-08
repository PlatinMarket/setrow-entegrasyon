<?php

App::uses('AppModel', 'Model');

class BadMember extends AppModel
{
  public $belongsTo = array('Customer');

}
