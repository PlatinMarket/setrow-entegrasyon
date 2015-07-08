<?php

App::uses('AppController', 'Controller');

class InstallController extends AppController
{
  public $uses = array('Setrow');

  // BeforeFilter
  public function beforeFilter()
  {
    parent::beforeFilter();
  }

  // Index
  public function index()
  {
    if (!empty($this->request->data))
    {
      if ($this->Setrow->save($this->request->data))
      {
        $this->Setrow->Customer->save(array('Customer' => array('id' => $this->customer_data['Customer']['id'], 'is_installed' => 1)));
        $this->redirect(array('session_id' => $this->session_id, 'controller' => 'config', 'action' => 'index'));
      }
    }
    else
    {
      $this->request->data = $this->customer_data;
    }
  }

}
