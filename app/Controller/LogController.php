<?php

App::uses('AppController', 'Controller');
App::uses('SetrowApi', 'Setrow.Lib');

class LogController extends AppController
{

  public $uses = null;

  public function index()
  {
    $s = new SetrowApi($this->customer_data['Setrow']['api_key']);
    $grup_listesi = $s->grup_listesi();
    $this->set(compact('grup_listesi'));
  }

}
