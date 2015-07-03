<?php

App::uses('AppController', 'Controller');

class ConfigController extends AppController
{
  public $uses = array('Setrow');

  public function index()
  {
    $this->redirect(array('session_id' => $this->session_id, 'controller' => 'log', 'action' => 'index'));
  }

  public function settings()
  {
    if (!empty($this->request->data))
    {
      if ($this->Setrow->save($this->request->data))
      {
        $this->redirect(array('session_id' => $this->session_id, 'controller' => 'config', 'action' => 'index'));
      }
    }
    else
    {
      $this->request->data = $this->customer_data;
    }
  }
}
