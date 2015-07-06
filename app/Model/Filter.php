<?php

App::uses('AppModel', 'Model');

class Filter extends AppModel
{
  public $belongsTo = array('Customer');

}
