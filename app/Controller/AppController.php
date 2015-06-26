<?php

App::uses('Controller', 'Controller');

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
    // Init Customer Data
    $this->__init_customer_data();

    // Get AccessToken
    $this->__init_access_token();

    // Check Customer Data
    if ($this->params->controller != "install" && (empty($this->customer_data) || $this->customer_data['Customer']['is_installed'] == 0))
      return $this->redirect(array("plugin" => null, "controller" => "install", "action" => "index", "session_id" => null));

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
        // Call Access Token

      }

      // Has AuthCode?
      if ($auth_code = $this->__has_token("RefreshToken", $this->customer_data['Customer']['id']))
      {
        // Call Access Token

      }
    }

    // Send to authorize
    $this->redirect(array('plugin' => null, 'controller' => 'oauth', 'action' => 'authorize', 'session_id' => $this->session_id));

  }

  private function __has_token($type = "AccessToken", $customer_id) {
    $this->loadModel($type);
    $conditions = array('NOW() BETWEEN SUBDATE(created, INTERVAL 1 SECOND) AND ADDDATE(created, INTERVAL lifetime MINUTE) OR lifetime = -1', 'customer_id' => $customer_id);
    $order = 'created DESC';
    if (!empty($token = $this->{$type}->find('first', compact('conditions', 'order')))) return $token;
    return $false;
  }

  // Init Customer Data
  private function __init_customer_data()
  {
    if (empty($this->session_id)) $this->session_id = isset($this->params->data['session_id']) ? $this->params->data['session_id'] : null;
    if (empty($this->session_id)) $this->session_id = isset($this->params->query['session_id']) ? $this->params->query['session_id'] : null;
    if (empty($this->session_id)) $this->session_id = !empty($this->params->session_id) ? $this->params->session_id : null;
    if (empty($this->session_id)) throw new BadRequestException("Session id required");

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

    // Check Already set
    if (!empty($this->customer_data) || $this->params->controller == 'install') return;

    // Check Params Page -> Page
    if (!empty($this->session_id) && !empty($this->Session->read($this->session_id)))
    {
      $this->customer_data = ClassRegistry::init('Customer')->findById($this->session_id);
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
