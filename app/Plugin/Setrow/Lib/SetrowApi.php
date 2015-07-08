<?php

App::uses('CakeTime', 'Utility');
App::uses('HttpSocket', 'Network/Http');
App::uses('SetrowApiException', 'Setrow.Lib');
App::uses('Xml', 'Utility');

class SetrowApi
{

    // Connection Properties
    private $_url = array(
      'default' => array(
        'protocol' => 'http:',
        'host' => 'api.setrow.com',
        'path' => 'V1/api.php'
      ),
      'product_api' => array(
        'path' => 'V1/PRODUCT_API.php'
      ),
      'shopping_cart' => array(
        'path' => 'V1/SHOPPING_CARD_API_V2.php'
      )
    );

    // Get Connection Url
    private function __connect($_alias = 'default')
    {
      if (!isset($this->_url[$_alias]))
        throw new SetrowApiException('Setrow alias \'' .  $_alias . '\' not known');

      $_url = $this->_url['default'];

      if ($_alias != 'default')
          $_url = array_merge($_url, $this->_url[$_alias]);

      return $_url['protocol'] . '//' . $_url['host'] . '/' . $_url['path'];
    }

    // Config Set
    private $_apiKey = null;
    public function setApiKey($apiKey)
    {
      if (!empty($apiKey) && is_string($apiKey)) $this->_apiKey = $apiKey;
    }

    // Public Construct
    public function __construct($apiKey = null)
    {
      if (!is_null($apiKey)) $this->setApiKey($apiKey);
    }

    // General Send Request
    private function __sendRequest($alias = 'default', $queryData = array())
    {
      if (is_array($alias)) $queryData = $alias;
      if (!is_array($queryData)) $queryData = array();
      if (!is_string($alias)) $alias = 'default';

      $url = $this->__connect($alias);
      $HttpSocket = new HttpSocket();
      $response = $HttpSocket->get($url, $queryData);

      if (!$response->isOk()) throw new SetrowApiException(array('url' => $url, 'response_body' => $response->body(), 'response_headers' => $response->headers, 'query' => $queryData), $response->code);

      if ($response->getHeader('Content-Type') != 'text/xml')
      {
          throw new SetrowApiException(array('url' => $url, 'response_body' => $response->body(), 'response_headers' => $response->headers, 'query' => $queryData));
      }

      return array('result' => 'success', 'data' => Xml::toArray(Xml::build($response->body())), 'headers' => $response->headers);
    }

    public function grup_listesi()
    {
      $response = $this->__sendRequest('default', array('i' => 'grup_listesi', 't' => 3, 'k' => $this->_apiKey));
      $response['data'] = $response['data']['grup_listesi']['grup_listesi_node'];
      return $response;
    }

    public function adres_ekle($extra_params = array())
    {
      $queryData = array('i' => 'adres_ekle', 't' => 3, 'k' => $this->_apiKey);
      $response = $this->__sendRequest('default', array_merge($queryData, $extra_params));
      $response['data'] = Hash::get($response, 'data.adres_ekle.adres_ekle_node');
      $result_map = array(
        1 => 'Adres eklendi.',
        2 => 'Adres bilgileri güncellendi. / Eğer adres sistemde kayıtlı değilse bu bilgiler ile eklendi.',
        3 => 'Adres grupta kayıtlı, eklenmedi.',
        4 => 'Hatalı bir mail adresi olduğu için eklenmedi.',
        5 => 'Bülten almak istemediğini belirttiği için eklenmedi.',
        6 => 'Junk adres listesinde kayıtlı olduğu için eklenmedi.',
        7 => 'Adres yazım kurallarına uygun olmadığı için eklenmedi.'
      );
      return $this->__parseErrorMessage($response, $result_map);
    }

    public function adres_guncelle($extra_params = array())
    {
      $queryData = array('i' => 'adres_guncelle', 't' => 3, 'k' => $this->_apiKey);
      $response = $this->__sendRequest('default', array_merge($queryData, $extra_params));
      $response['data'] = $response['data']['adres_guncelle']['adres_guncelle_node'];
      $result_map = array(
        1 => 'Adres eklendi.',
        2 => 'Adres bilgileri güncellendi. / Eğer adres sistemde kayıtlı değilse bu bilgiler ile eklendi.',
        3 => 'Adres grupta kayıtlı, eklenmedi.',
        4 => 'Hatalı bir mail adresi olduğu için eklenmedi.',
        5 => 'Bülten almak istemediğini belirttiği için eklenmedi.',
        6 => 'Junk adres listesinde kayıtlı olduğu için eklenmedi.',
        7 => 'Adres yazım kurallarına uygun olmadığı için eklenmedi.'
      );

      return $this->__parseErrorMessage($response, $result_map);
    }

    public function adres_sil($extra_params = array())
    {
      $queryData = array('i' => 'adres_sil', 't' => 3, 'k' => $this->_apiKey);
      $response = $this->__sendRequest('default', array_merge($queryData, $extra_params));
      $response['data'] = $response['data']['adres_sil']['adres_sil_node'];
      $result_map = array(
        'S' => 'Adres silindi.'
      );
      return $this->__parseErrorMessage($response, $result_map);
    }

    // Error Parse from map
    private function __parseErrorMessage($response = array(), $result_map)
    {
      $response['result'] = 'error';
      if (Hash::get($response, 'data.Sonuç'))
      {
        $resultCode = Hash::get($response, 'data.Sonuç');
        if (is_numeric($resultCode)) $resultCode = intval(Hash::get($response, 'data.Sonuç'));
        $response['result'] = ($resultCode === 1 ? 'success' : 'error');
        $response['data'] = array(
          'msg' => isset($result_map[$resultCode]) ? $result_map[$resultCode] : 'Bilinmeyen hata. Sonuç: ' . $resultCode,
          'code' => $resultCode
        );
      }
      else
      {
        $response['data'] = array(
          'msg' => 'Bilinmeyen hata',
          'code' => -1
        );
      }
      return $response;
    }

}
