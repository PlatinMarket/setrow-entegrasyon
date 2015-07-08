<?php

App::uses('AppModel', 'Model');

class SyncTrack extends AppModel
{
  public $useTable = 'sync_track';

  public $belongsTo = array('Customer');

  public $validate = array(
    'customer_id' => array(
      'notempty' => array(
        'rule' => array('notempty'),
        'message' => '`customer_id` boş bırakılmaz',
        'allowEmpty' => false
      )
    ),
    'alias' => array(
      'notempty' => array(
        'rule' => array('notempty'),
        'message' => '`alias` boş bırakılmaz',
        'allowEmpty' => false
      )
    )
  );
}
