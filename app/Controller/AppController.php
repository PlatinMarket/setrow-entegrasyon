<?php

App::uses('Controller', 'Controller');
App::uses('CakeTime', 'Utility');
App::uses('HttpSocket', 'Network/Http');

class AppController extends Controller
{

  public $customer_data = null;
  public $session_id = null;

  public $helpers = array(
		'Bower',
    'Session',
		'Html' => array('className' => 'BoostCake.BoostCakeHtml'),
		'Form' => array('className' => 'BoostCake.BoostCakeForm'),
		'Paginator' => array('className' => 'BoostCake.BoostCakePaginator')
	);

  // Before Render Page
  public function beforeRender()
  {
      if ($this->name == 'CakeError') $this->layout = 'error'; // Error Layout
      $this->set('customer_data', $this->customer_data); // Set Customer Data
      $this->set('session_id', $this->session_id); // Set Session Id
  }

  // Before Filter
  public function beforeFilter()
  {
    //Check If Session Start
    if ($this->request->url == "session_start")
    {
      $this->Session->write('session_map.' . $this->session_id, "");
      return $this->redirect($this->request->data['redirect_uri']);
    }

    // Check If Error
    if ($this->name == 'CakeError') return;

    // Init Config Data
    $this->__init_config();

    // Init Session Data
    $this->__init_session();

    // Init Customer Data
    $this->__init_customer_data();

    // Init Log File
    $this->__init_log_files();

    // Get AccessToken
    $this->__init_access_token();

    // Check Customer Data
    if ($this->params->controller != "install" && (empty($this->customer_data) || $this->customer_data['Customer']['is_installed'] == 0))
      return $this->redirect(array("plugin" => null, "controller" => "install", "action" => "index", "session_id" => $this->session_id));

  }

  private function __init_log_files()
  {
    if (!empty($this->customer_data))
    {
      $this->customer_id = $this->customer_data['Customer']['id'];
      // Sync Log Config
      CakeLog::config('sync', array(
        'engine' => 'File',
        'types' => array('sync'),
        'file' => 'sync_' . $this->customer_id,
      ));
      CakeLog::config('sync_debug', array(
        'engine' => 'File',
        'types' => array('sync_debug'),
        'file' => 'sync_debug_' . $this->customer_id,
      ));
      CakeLog::config('sync_error', array(
        'engine' => 'File',
        'types' => array('sync_error'),
        'file' => 'sync_error_ ' . $this->customer_id,
      ));
    }
  }

  private function __init_access_token()
  {
    if (!empty($this->customer_data))
    {
      // Has AccessToken?
      if ($access_token = $this->__has_token("AccessToken", $this->customer_data['Customer']['id']))
        return $access_token;

      // Has RefreshToken?
      if ($refresh_token = $this->__has_token("RefreshToken", $this->customer_data['Customer']['id']))
      {
        $this->loadModel('Customer');

        // Get AccessToken from Refresh Token
        // Set Action Url
        $action = Configure::read('PlatinMarket.OAuth.protocol') . '://' . Configure::read('PlatinMarket.OAuth.host') . Configure::read('PlatinMarket.OAuth.path') . '/access_token.json';

        // Prepare Data
        $data = array(
          'platform_uuid' => Configure::read('PlatinMarket.PlatformUUID'),
          'customer_uuid' => $this->customer_data['Customer']['uuid'],
          'token' => $refresh_token['RefreshToken']['token'],
          'grant_type' => 'refresh_token'
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
          if ($this->Customer->RefreshToken->delete($refresh_token['RefreshToken']['id'])) $this->redirect('/');
          throw new CakeException("Request for 'access_token' from 'refresh_token' failed. " . $response_msg['message'], $response->code);
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
          throw new CakeException("Parse failed get 'access_token' from 'refresh_token' failed", $response->code);
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
          throw new CakeException("AccessToken create failed");

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

        return $access_token['AccessToken']['token'];
      }

    }

    // Send to authorize
    $this->redirect(array('plugin' => null, 'controller' => 'oauth', 'action' => 'authorize', 'session_id' => $this->session_id));

  }

  private function __has_token($type = "AccessToken", $customer_id) {
    $this->loadModel($type);
    $conditions = array('NOW() BETWEEN SUBDATE(' . $type . '.created, INTERVAL 1 SECOND) AND ADDDATE(' . $type . '.created, INTERVAL ' . $type . '.lifetime MINUTE) OR lifetime = -1', 'customer_id' => $customer_id);
    $order = $type . '.created DESC';
    if (!empty($token = $this->{$type}->find('first', compact('conditions', 'order')))) return $token;
    return false;
  }

  // Check Config Data
  protected function __init_config()
  {
    if (
        empty(Configure::read("PlatinMarket.ClientID")) ||
        empty(Configure::read("PlatinMarket.ClientSecret")) ||
        empty(Configure::read("PlatinMarket.ApplicationUUID")) ||
        empty(Configure::read("PlatinMarket.PlatformUUID")) ||
        empty(Configure::read("PlatinMarket.Scope")) ||
        empty(Configure::read("PlatinMarket.Api")) ||
        empty(Configure::read("PlatinMarket.OAuth"))
      )
        throw new Exception("Config error");
  }

  // Init Session Data
  protected function __init_session()
  {
    if (empty($this->session_id)) $this->session_id = isset($this->params->data['session_id']) ? $this->params->data['session_id'] : null;
    if (empty($this->session_id)) $this->session_id = isset($this->params->query['session_id']) ? $this->params->query['session_id'] : null;
    if (empty($this->session_id)) $this->session_id = !empty($this->params->session_id) ? $this->params->session_id : null;
    if (empty($this->session_id)) throw new BadRequestException("Session id required");
  }

  // Init Customer Data
  private function __init_customer_data()
  {
    // Return if already set or at during install
    if (!empty($this->customer_data)) return;

    // Check Params Page -> Page
    if (!empty($this->session_id) && !empty($this->Session->read('session_map.' . $this->session_id)))
    {
      $this->customer_data = ClassRegistry::init('Customer')->findByUuid($this->Session->read('session_map.' . $this->session_id));
      return;
    }
    elseif (!empty($this->params->customer_id)) // Check customer_id already set probably 404
    {
      throw new NotFoundException("Customer id not found");
    }

    // Check Request
    if (
        !isset($this->request->data['command']) ||
        !isset($this->request->data['customer_uuid']) ||
        !isset($this->request->data['platform_uuid']) ||
        !isset($this->request->data['success_url']) ||
        !isset($this->request->data['fail_url']) ||
        !isset($this->request->data['hash']) ||
        !isset($this->request->data['hash-map']) ||
        !isset($this->request->data['time'])
      )
        throw new UnauthorizedException("Missing post parameters");
    else
      extract($this->request->data);

    // Write Session -> CustomerUUID Map
    $this->Session->write('session_map.' . $this->session_id, $customer_uuid);

    // Check Hash
    if ($hash != $this->__hash(Configure::read("PlatinMarket.ClientID"), Configure::read("PlatinMarket.ClientSecret"), array($command, $customer_uuid, $platform_uuid, $success_url, $fail_url)))
      throw new BadRequestException("Invalid Hash");

    // Get Data
    $this->customer_data = ClassRegistry::init('Customer')->findByUuid($customer_uuid);

  }

  protected function __hash($clientId, $clientSecret, $data, $enc = 'sha256') {
    $SecurityData = strtoupper(hash($enc, $clientSecret . $clientId, false));
    return strtoupper(hash($enc, implode('', array_values($data)) . $SecurityData, false));
  }
}
