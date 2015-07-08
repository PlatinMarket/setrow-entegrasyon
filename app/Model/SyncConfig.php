<?php

App::uses('AppModel', 'Model');

class SyncConfig extends AppModel
{
  public $useTable = 'sync_config';

  public $belongsTo = array('Customer');

  public $validate = array(
    'active' => array(
      'boolean' => array(
        'rule' => array('boolean'),
        'message' => 'Yanlızca Doğru / Yanlış olabilir',
        'allowEmpty' => false
      )
    ),
    'period' => array(
      'numeric' => array(
        'rule' => array('numeric'),
        'message' => 'Period yanlızca sayı olmalı',
        'allowEmpty' => false
      )
    )
  );
}
