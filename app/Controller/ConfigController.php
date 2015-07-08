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
      if ($allSaved = $this->Setrow->Customer->SyncConfig->save($this->request->data))
      {
        if ($this->customer_data['SyncConfig']['active'] != $this->request->data['SyncConfig']['active'])
        {
          $logMessage = 'Sistem kapatıldı';
          if ($this->request->data['SyncConfig']['active']) $logMessage = 'Sistem açıldı';
          CakeLog::write('sync', $logMessage);
        }
        if ($this->customer_data['SyncConfig']['period'] != $this->request->data['SyncConfig']['period'])
        {
          $logMessage = 'Periyot ' . $this->request->data['SyncConfig']['period'] . ' dk olarak değiştirildi';
          CakeLog::write('sync', $logMessage);
        }
      }

      if ($allSaved)
        $this->Session->setFlash('<strong>Ayarlar kaydedildi</strong>', 'alert', array('plugin' => 'BoostCake', 'class' => 'alert-success'));
      else
      {
        $errorStr = "";

        if (isset($this->Setrow->Customer->MemberMapper->validationErrors[1])) $this->Setrow->Customer->MemberMapper->validationErrors = $this->Setrow->Customer->MemberMapper->validationErrors[1];
        foreach ($this->Setrow->Customer->MemberMapper->validationErrors as $key => $errors) $errorStr .= "<br />" . implode(',', $errors);

        if (isset($this->Setrow->Customer->SyncConfig->validationErrors[1])) $this->Setrow->Customer->SyncConfig->validationErrors = $this->Setrow->Customer->SyncConfig->validationErrors[1];
        foreach ($this->Setrow->Customer->SyncConfig->validationErrors as $key => $errors) $errorStr .= "<br />" . implode(',', $errors);

        $this->Session->setFlash('<strong>Ayarlar kaydedilemedi</strong>' . $errorStr, 'alert', array('plugin' => 'BoostCake', 'class' => 'alert-danger'));
      }

    }
    else
    {
      $this->request->data = $this->customer_data;
    }
  }
}
