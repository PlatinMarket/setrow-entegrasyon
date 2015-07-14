<?php

App::uses('AppController', 'Controller');

class InstallController extends AppController
{
  public $uses = array('Setrow');

  // BeforeFilter
  public function beforeFilter()
  {
    parent::beforeFilter();
    if (Hash::get($this->customer_data, 'Customer.is_installed'))
    {
      $this->redirect(array('session_id' => $this->session_id, 'controller' => 'config', 'action' => 'index'));
    }
  }

  // Index
  public function index()
  {
    if (!empty($this->request->data) && isset($this->request->data['Setrow']) && !empty($this->request->data['Setrow']))
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
