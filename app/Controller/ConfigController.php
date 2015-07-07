<?php

App::uses('AppController', 'Controller');
App::uses('SetrowApi', 'Setrow.Lib');

class ConfigController extends AppController
{
  public $uses = array('Setrow');

  public function index()
  {
    $this->redirect(array('session_id' => $this->session_id, 'controller' => 'log', 'action' => 'index'));
  }

  public function settings()
  {
    $s = new SetrowApi($this->customer_data['Setrow']['api_key']);
    $grup_listesi = $s->grup_listesi();
    $grup_listesi = Hash::combine($grup_listesi['data'], '{n}.grupid', array('[%s] %s', '{n}.grupid', '{n}.grupadi'));
    $this->set(compact('grup_listesi'));

    $filters = Hash::combine($this->customer_data['Filter'], '{n}.id', '{n}.label');
    $this->set(compact('filters'));

    if (!empty($this->request->data))
    {
      $allSaved = $this->Setrow->save($this->request->data);
      $allSaved = $this->Setrow->Customer->MemberMapper->saveAllModified($this->request->data);

      if ($allSaved)
        $this->Session->setFlash('<strong>Ayarlar kaydedildi</strong>', 'alert', array('plugin' => 'BoostCake', 'class' => 'alert-success'));
      else
      {
        $errorStr = "";
        foreach ($this->Setrow->Customer->MemberMapper->validationErrors as $key => $errors) $errorStr .= "<br />" . implode(',', $errors);
        $this->Session->setFlash('<strong>Ayarlar kaydedilemedi</strong>' . $errorStr, 'alert', array('plugin' => 'BoostCake', 'class' => 'alert-danger'));
      }

    }
    else
    {
      $this->request->data = $this->customer_data;
    }
  }
}
