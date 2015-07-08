<?php

App::uses('AppController', 'Controller');
App::uses('CakeTime', 'Utility');
App::uses('HttpSocket', 'Network/Http');

class OauthController extends AppController
{

  public $uses = array('Customer');

  public $customer_uuid = null;

  // Before Filter
  public function beforeFilter()
  {
    // Init Config Data
    $this->__init_config();

    // Init Session Data
    $this->__init_session();

    // Check If Unauthorized Request
    if (empty($this->Session->read('session_map.' . $this->session_id))) throw new UnauthorizedException("Invalid session id");
    $this->customer_uuid = $this->Session->read('session_map.' . $this->session_id);
  }

  // Authorize Callback Method
  public function callback()
  {
    // Check Request
    if (
        !isset($this->request->data['customer_uuid']) ||
        !isset($this->request->data['platform_uuid']) ||
        !isset($this->request->data['auth_code']) ||
        !isset($this->request->data['lifetime']) ||
        !isset($this->request->data['hash']) ||
        !isset($this->request->data['hash-map']) ||
        !isset($this->request->data['time'])
      )
        throw new UnauthorizedException("Missing callback post parameters");
    else
      extract($this->request->data);

    // Check Hash
    if ($hash != $this->__hash(Configure::read("PlatinMarket.ClientID"), Configure::read("PlatinMarket.ClientSecret"), array($customer_uuid, $platform_uuid, $auth_code, $lifetime)))
      throw new BadRequestException("Invalid Hash");

    // Get AccessToken from auth code
    // Set Action Url
    $action = Configure::read('PlatinMarket.OAuth.protocol') . '://' . Configure::read('PlatinMarket.OAuth.host') . Configure::read('PlatinMarket.OAuth.path') . '/access_token.json';

    // Prepare Data
    $data = array(
      'platform_uuid' => Configure::read('PlatinMarket.PlatformUUID'),
      'customer_uuid' => $this->customer_uuid,
      'token' => $auth_code,
      'grant_type' => 'auth_code'
    );

    $hash_map = implode(',', array_keys($data));
    $data['hash'] = $this->__hash(Configure::read('PlatinMarket.ClientID'), Configure::read('PlatinMarket.ClientSecret'), $data);
    $data['hash-map'] = $hash_map;
    $data['application_uuid'] = Configure::read('PlatinMarket.ApplicationUUID');
    $data['time'] = CakeTime::toRSS(new DateTime(), 'Europe/Istanbul');

    // Make Request
    $HttpSocket = new HttpSocket();
    $response = $HttpSocket->get($action, $data);

    // Check Response
    if ($response->code != 200)
    {
      try { $response_msg = json_decode($response->body, true); } catch (Exception $err) { $response_msg = $response->body; }
      throw new Exception("Request for 'access_token' from 'auth_code' failed. " . $response_msg['message'], $response->code);
    }

    // Parse Response
    try
    {
      $response_data = json_decode($response->body, true);
      $response_data = $response_data['result'];
      extract($response_data);
    }
    catch (Exception $err)
    {
      throw new Exception("Parse failed get 'access_token' from 'auth_code' failed", $response->code);
    }

    // Get Customer Info From Db
    $this->customer_data = $this->Customer->findByUuid($this->customer_uuid);
    if (empty($this->customer_data))
    {
      // Get User Info From Remote
      // Set Action Url
      $action = Configure::read('PlatinMarket.OAuth.protocol') . '://' . Configure::read('PlatinMarket.OAuth.host') . Configure::read('PlatinMarket.OAuth.path') . '/customer_info.json';

      // Prepare Data
      $data = array(
        'platform_uuid' => Configure::read('PlatinMarket.PlatformUUID'),
        'customer_uuid' => $this->customer_uuid,
        'application_uuid' => Configure::read('PlatinMarket.ApplicationUUID')
      );

      $hash_map = implode(',', array_keys($data));
      $data['hash'] = $this->__hash(Configure::read('PlatinMarket.ClientID'), Configure::read('PlatinMarket.ClientSecret'), $data);
      $data['hash-map'] = $hash_map;
      $data['access_token'] = $access_token;
      $data['time'] = CakeTime::toRSS(new DateTime(), 'Europe/Istanbul');

      // Make Request
      $HttpSocket = new HttpSocket();
      $response = $HttpSocket->get($action, $data);

      // Check Response
      if ($response->code != 200)
      {
        try { $response_msg = json_decode($response->body); } catch (Exception $err) { $response_msg = $response->body; }
        throw new Exception("Request for 'customer_info' failed. " . $response_msg, $response->code);
      }

      // Parse Response
      try
      {
        $response_data = json_decode($response->body, true);
        $response_data = $response_data['customer'];
        extract($response_data);
      }
      catch (Exception $err)
      {
        throw new Exception("Parse failed for 'customer_info' failed", $response->code);
      }

      // Save Customer
      // Prepare Data
      $customer = array('Customer' => array(
        'domain' => $domain,
        'uuid' => $this->customer_uuid,
        'name' => $name,
        'mail' => $mail
      ));

      // Save
      $this->Customer->create();
      if (!$this->Customer->save($customer))
        throw new Exception("Customer create failed");

      // Get Customer Data
      $this->customer_data = $this->Customer->findByUuid($this->customer_uuid);
    }

    // Save Tokens
    // Prepare Data
    $access_token = array('AccessToken' => array(
      'token' => $access_token,
      'lifetime' => $access_token_lifetime,
      'customer_id' => $this->customer_data['Customer']['id']
    ));

    // Delete Old Access Token
    $this->Customer->AccessToken->deleteAll(array('AccessToken.customer_id' => $this->customer_data['Customer']['id']));

    // Save Access Token
    $this->Customer->AccessToken->create();
    if (!$this->Customer->AccessToken->save($access_token))
      throw new Exception("AccessToken create failed");

    // Prepare Data
    $refresh_token = array('RefreshToken' => array(
      'token' => $refresh_token,
      'lifetime' => $refresh_token_lifetime,
      'customer_id' => $this->customer_data['Customer']['id']
    ));

    // Delete Old Refresh Token
    $this->Customer->RefreshToken->deleteAll(array('RefreshToken.customer_id' => $this->customer_data['Customer']['id']));

    // Save Refresh Token
    $this->Customer->RefreshToken->create();
    if (!$this->Customer->RefreshToken->save($refresh_token))
      throw new Exception("RefreshToken create failed");

    $this->redirect(array('plugin' => null, 'controller' => 'config', 'action' => 'index', 'session_id' => $this->session_id));
  }

  // Authorize Request Method
  public function authorize()
  {
    // Setting Layout to 'form'
    $this->layout = "form";

    // Set Method
    $method = 'POST';

    // Set Action Url
    $action = Configure::read('PlatinMarket.OAuth.protocol') . '://' . Configure::read('PlatinMarket.OAuth.host') . Configure::read('PlatinMarket.OAuth.path') . '/authorize';

    // Prepare Hash Data
    $data = array(
      'customer_uuid' => $this->customer_uuid,
      'application_uuid' => Configure::read('PlatinMarket.ApplicationUUID'),
      'platform_uuid' => Configure::read('PlatinMarket.PlatformUUID'),
      'scope' => Configure::read('PlatinMarket.Scope'),
      'redirect_uri' => Router::url(array('plugin' => null, 'session_id' => null, 'controller' => 'oauth', 'action' => 'callback'), true)
    );

    $hash_map = implode(',', array_keys($data));
    $data['hash'] = $this->__hash(Configure::read('PlatinMarket.ClientID'), Configure::read('PlatinMarket.ClientSecret'), $data);
    $data['hash-map'] = $hash_map;
    $data['session_id'] = $this->session_id;
    $data['time'] = CakeTime::toRSS(new DateTime(), 'Europe/Istanbul');

    $this->set(compact('data', 'method', 'action'));
  }
}
