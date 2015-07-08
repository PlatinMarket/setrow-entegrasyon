<?php

App::uses('AppController', 'Controller');

class LogController extends AppController
{

  public $uses = array('Log');

  public function index()
  {
    $log = $this->Log->parse('sync_' . $this->customer_data['Customer']['id'] . '.log', 10);
    if (empty($log)) $this->Session->setFlash('Henüz bir sistem kaydı oluşmadı.<br/>Ayarları gözden geçirmek için <a href="' . Router::url(array('session_id' => $this->session_id, 'controller' => 'config', 'action' => 'settings', 'plugin' => null)) . '"><strong>tıklayın</strong></a>.', 'alert', array('plugin' => 'BoostCake', 'class' => 'alert-info'));
    $this->set('log', $log);
		$this->set('log_name', 'sync');
  }

}
