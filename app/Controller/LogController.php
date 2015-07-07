<?php

App::uses('AppController', 'Controller');

class LogController extends AppController
{

  public $uses = array('Log');

  public function index()
  {
    $log = $this->Log->parse('sync.log', 10);
    $this->set('log', $log);
		$this->set('log_name', 'sync');
  }

}
