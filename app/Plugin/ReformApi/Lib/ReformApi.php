<?php

App::uses('CakeTime', 'Utility');
App::uses('HttpSocket', 'Network/Http');
App::uses('ReformApiException', 'ReformApi.Lib');
App::uses('Xml', 'Utility');

class ReformApi
{

  // Connection Properties
  private $_url = array(
    'protocol' => 'http:',
    'host' => 'developer.platinmarket.com',
    'path' => 'reform'
  );

  // Get Connection from '_url'
  private function __connect($path)
  {
    $url = $this->_url['protocol'] . '//' . $this->_url['host'] . '/' . $this->_url['path'];

    if (is_string($path) && !empty($path))
      $url .= '/' . $path . '.json';

    $url .= '?' . 'access_token=' . $this->accessToken;
    return $url;
  }

  // Access Token For General Call
  private $accessToken = null;
  public function setAccessToken($accessToken)
  {
    if (is_string($accessToken)) $this->accessToken = $accessToken;
  }

  public function __construct($accessToken = null)
  {
    if (!is_null($accessToken) && !empty($accessToken) && is_string($accessToken)) $this->setAccessToken($accessToken);
  }

  // General Send Request
  private function __sendRequest($path, $data = array())
  {
    $url = $this->__connect($path);
    $HttpSocket = new HttpSocket();

    if (is_array($data) && !empty($data))
      $response = $HttpSocket->post($url, $data);
    else
      $response = $HttpSocket->get($url, $data);

    $response_arr = array();
    if (strpos($response->getHeader('Content-Type'), 'application/json') !== false)
      $response_arr = json_decode($response->body(), true);
    elseif ($response->getHeader('Content-Type') == 'text/xml')
      $response_arr = Xml::toArray(Xml::build($response->body()));
    else
      $response_arr = array('header' => $response->headers, 'data' => $response->body(), 'error' => array('code' => null, 'message' => null, 'scope' => null));

    if (isset($response_arr['response'])) $response_arr = $response_arr['response'];

    if (isset($response_arr['name']) && isset($response_arr['message']) && isset($response_arr['url']))
      $response_arr = array('header' => $response->headers, 'data' => $response->code . ' ' . $response_arr['name'], 'error' => array('code' => $response->code, 'message' => $response_arr['message'], 'scope' => 'LOCAL'));

    if (!$response->isOk()) throw new ReformApiException(array('url' => $url, 'request' => $data, 'response' => $response_arr), $response->code);

    if (!is_null($response_arr['error']['code'])) throw new ReformApiException(array('url' => $url, 'request' => $data, 'response' => $response_arr), $response_arr['error']['code']);

    return $response_arr;
  }

  public function __call($method, $parameters)
  {
    $method = Inflector::underscore($method);
    if (strpos($method, 'call_') === 0)
    {
      if (substr_count($method, '_') === 1) $method = $method . '_index';

      $method = str_replace('call_', '', $method);
      $method = str_replace('_', '/', $method);

      if (isset($parameters[0]) && is_array($parameters[0]))
        $parameters = $parameters[0];
      else
        $parameters = array();

      return $this->__sendRequest($method, $parameters);
    }

    trigger_error('Call to undefined method '.__CLASS__.'::'.$name.'()', E_USER_ERROR);
  }

}
