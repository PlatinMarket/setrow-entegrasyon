<?php

App::uses('AppModel', 'Model');

class Setrow extends AppModel
{
  public $useTable = 'setrow';

  public $belongsTo = array("Customer");

  public $validate = array(
    'customer_id' => array(
      'notempty' => array(
        'rule' => array('notempty'),
        'message' => 'Boş Bırakılmaz',
        'allowEmpty' => false
      )
    ),
    'api_key' => array(
      'notempty' => array(
        'rule' => array('notempty'),
        'message' => 'Boş Bırakılmaz',
        'allowEmpty' => false
      )
    )
  );

}
