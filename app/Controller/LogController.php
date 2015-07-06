<?php

App::uses('AppController', 'Controller');
App::uses('SetrowApi', 'Setrow.Lib');
App::uses('ReformApi', 'ReformApi.Lib');

class LogController extends AppController
{

  public $uses = null;

  public function index()
  {
    $r = new ReformApi($this->customer_data['AccessToken'][0]['token']);
    $products = $r->callProducts();

    $s = new SetrowApi($this->customer_data['Setrow']['api_key']);
    $grup_listesi = $s->grup_listesi();

    $this->set(compact('grup_listesi', 'products'));
  }

}
