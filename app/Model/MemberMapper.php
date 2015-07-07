<?php

App::uses('AppModel', 'Model');

class MemberMapper extends AppModel
{

  public $belongsTo = array('Customer', 'Filter');

  public $validate = array(
    'customer_id' => array(
      'notempty' => array(
        'rule' => array('notempty'),
        'message' => 'Müşteri boş bırakılmaz',
        'allowEmpty' => false
      )
    ),
    'filter_id' => array(
      'notempty' => array(
        'rule' => array('notempty'),
        'message' => 'E-Store boş bırakılmaz',
        'allowEmpty' => false
      )
    ),
    'grupid' => array(
      'notempty' => array(
        'rule' => array('notempty'),
        'message' => 'Setrow grup boş bırakılmaz',
        'allowEmpty' => false
      )
    )
  );

  public function saveAllModified($data = array())
  {
    $allSaved = true;
    if (isset($data['MemberMapper']) && is_array($data['MemberMapper']) && !empty(isset($data['MemberMapper'])))
    {
      $memberMapper = array('MemberMapper' => array_pop($data['MemberMapper']));
      foreach ($data['MemberMapper'] as $key => $value)
      {
        if ($value['delete'] && ($allSaved = $this->delete($value['id'])))
        {
          unset($data['MemberMapper'][$key]);
        }
      }
      $data['MemberMapper'] = array_values($data['MemberMapper']);
      $allSaved = $this->saveMany($data['MemberMapper']);
      if (!empty(Hash::get($memberMapper, 'MemberMapper.filter_id')) || !empty(Hash::get($memberMapper, 'MemberMapper.grupid')))
      {
        $this->create();
        if ($allSaved = $this->save($memberMapper))
        {
          $memberMapper['MemberMapper']['id'] = $this->id;
        }
        $data['MemberMapper'][] = $memberMapper['MemberMapper'];
      }
    }
    return $allSaved;
  }

}
