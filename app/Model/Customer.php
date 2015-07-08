<?php

App::uses('AppModel', 'Model');

class Customer extends AppModel
{

  public $hasOne = array(
    'Setrow' => array('dependent' => true),
    'SyncConfig' => array('dependent' => true)
  );

  // HasMany Relation
  public $hasMany = array(
    'AccessToken' => array('dependent' => true),
    'RefreshToken' => array('dependent' => true),
    'Filter' => array('dependent' => true),
    'MemberMapper' => array('dependent' => true),
    'SyncTrack' => array('dependent' => true),
    'BadMember' => array('dependent' => true)
  );

  public function afterFind($results, $primary = false)
  {
    if ($primary)
    {
      $globalFilters = $this->Filter->find('all', array('recursive' => -1, 'conditions' => array('Filter.customer_id IS NULL'), 'callbacks' => false));
      foreach($results as $key => $value)
      {
        foreach ($results[$key]['Filter'] as $filter_key => $filter)
        {
          $results[$key]['Filter'][$filter_key]['query'] = unserialize($filter['query']);
          $results[$key]['Filter'][$filter_key]['remote_controller'] = Inflector::pluralize($filter['remote']);
        }
        foreach ($globalFilters as $filter)
        {
            $filter['Filter']['query'] = unserialize($filter['Filter']['query']);
            $filter['Filter']['remote_controller'] = Inflector::pluralize($filter['Filter']['remote']);
            $results[$key]['Filter'][] = $filter['Filter'];
        }
        if (isset($results[$key]['SyncConfig']) && empty($results[$key]['SyncConfig']['id']))
        {
          $syncConfig = array('SyncConfig' => array('customer_id' => $results[$key]['Customer']['id']));
          $this->SyncConfig->save($syncConfig);
          $results[$key]['SyncConfig'] = Hash::get($this->SyncConfig->findByCustomerId($results[$key]['Customer']['id']), 'SyncConfig');
        }
      }
    }
    return $results;
  }

}
