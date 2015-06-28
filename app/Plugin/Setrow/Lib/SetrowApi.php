<?php

App::uses('CakeTime', 'Utility');
App::uses('HttpSocket', 'Network/Http');

class SetrowApi
{

    // Connection Properties
    private $_url = array(
      'protocol' => 'https:',
      'host' => 'api.setrow.com',
      'path' => 'V1/api.php'
    );
    // Get Connection Url
    private function __connect()
    {
      return $this->_url['protocol'] . '//' . $this->_url['host'] . '/' . $this->_url['path'];
    }

    // Config Set
    private $_apiKey = null;
    public function setApiKey($apiKey)
    {

    }

    // Public Construct
    public function __construct()
    {

    }

    // General Send Request
    private function __sendRequest($queryData = array())
    {
      if (!is_array($queryData)) $queryData = array();
      $result = $HttpSocket->get($this->__connect(), $queryData);
      return $result;
    }
}
