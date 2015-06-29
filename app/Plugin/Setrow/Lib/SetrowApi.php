<?php

App::uses('CakeTime', 'Utility');
App::uses('HttpSocket', 'Network/Http');

class SetrowApi
{

    // Connection Properties
    private $_url = array(
      'api' => array(
        'protocol' => 'http:',
        'host' => 'api.setrow.com',
        'path' => 'V1/api.php'
      ),
      'product_api' => array(
        'protocol' => 'http:',
        'host' => 'api.setrow.com',
        'path' => 'V1/PRODUCT_API.php'
      ),
      'shopping_cart' => array(
        'protocol' => 'http:',
        'host' => 'api.setrow.com',
        'path' => 'V1/SHOPPING_CARD_API_V2.php'
      )
    );
    // Get Connection Url
    private function __connect($_alias = 'api')
    {
      $_url = $this->_url[$alias];
      return $_url['protocol'] . '//' . $_url['host'] . '/' . $_url['path'];
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

    public function checkApiKey()
    {
        $response = $this->__sendRequest(array(''))
    }
}
