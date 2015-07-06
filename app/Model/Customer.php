<?php

App::uses('AppModel', 'Model');

class Customer extends AppModel
{

  public $hasOne = array("Setrow");

  // HasMany Relation
  public $hasMany = array(
    'AccessToken' => array('dependent' => true),
    'RefreshToken' => array('dependent' => true),
    'Filter' => array('dependent' => true)
  );

  public function afterFind($results, $primary = false)
  {
    if ($primary)
    {
      $globalFilters = $this->Filter->find('all', array('recursive' => -1, 'conditions' => array('Filter.customer_id IS NULL'), 'callbacks' => false));
      foreach($results as $key => $value)
      {
        foreach ($globalFilters as $filter) {
            $results[$key]['Filter'][] = $filter['Filter'];
        }
      }
    }
    return $results;
  }

}
