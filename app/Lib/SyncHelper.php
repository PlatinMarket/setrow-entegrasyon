<?php

App::uses('SyncHelperException', 'ReformApi.Lib');
App::uses('ReformApi', 'ReformApi.Lib');
App::uses('HttpSocket', 'Network/Http');
App::uses('CakeTime', 'Utility');

class SyncHelper
{
  public $customer_data = array();
  public $sync_track = array();
  public $customer_id = null;
  private $models = array();

  public function __construct($customer)
  {
    // Prepare Sync Before Setting Values
    $this->__prepareLogFiles();

    // Set Values
    $this->customer_data = $customer;
    $this->customer_id = $this->customer_data['Customer']['id'];
    $this->sync_track = ((isset($this->customer_data['SyncTrack']) && !empty($this->customer_data['SyncTrack']) && is_array($this->customer_data['SyncTrack'])) ? $this->customer_data['SyncTrack'] : array());

    // Check Customer
    if (empty($this->customer_data)) throw new Exception('Cannot access customer data from `SyncHelper`');

    // Prepare Sync
    $this->__prepareLogFiles();

    // Validation
    if (!$this->getReformAccessToken()) throw new Exception('Cannot get `ReformAccessToken` from customer data');
    if (!$this->getSetrowApiKey()) throw new Exception('Cannot get `SetrowApiKey` from customer data');
  }

  private function __model($modelName)
  {
    if (!isset($this->model[$modelName])) $this->model[$modelName] = ClassRegistry::init($modelName);
    return $this->model[$modelName];
  }

  public static function getCustomerOptions()
  {
    $isDevelop = (Configure::read('debug') > 0 ? 1 : 0);
    return array('conditions' => array('Customer.is_installed' => 1, 'Customer.is_develop' => $isDevelop));
  }

  public function logWithTitle($title, $msg)
  {
    // If Message not string convert to string
    if (!is_string($msg)) $msg = print_r($msg, true);

    $msg = '[' . $title . '] ' . $msg;
    $this->log('info', $msg);
  }

  public function log($alias = 'info', $msg)
  {
    // If Message not string convert to string
    if (!is_string($msg)) $msg = print_r($msg, true);

    switch ($alias) {
      case 'info':
        CakeLog::write('sync', $msg);
        break;
      case 'error':
        CakeLog::write('sync_error', $msg);
        break;
      case 'debug':
        if (Configure::read('debug') > 0) CakeLog::write('sync_debug', $msg);
        break;
      default:
        CakeLog::write('sync', $msg);
        break;
    }
  }

  private function __prepareLogFiles()
  {
    // Sync Log Config
    CakeLog::config('sync', array(
      'engine' => 'File',
      'types' => array('sync'),
      'file' => 'sync' . ($this->customer_id ? '_' . $this->customer_id : ''),
    ));
    CakeLog::config('sync_debug', array(
      'engine' => 'File',
      'types' => array('sync_debug'),
      'file' => 'sync_debug' . ($this->customer_id ? '_' . $this->customer_id : ''),
    ));
    CakeLog::config('sync_error', array(
      'engine' => 'File',
      'types' => array('sync_error'),
      'file' => 'sync_error' . ($this->customer_id ? '_' . $this->customer_id : ''),
    ));
  }

  // Reform Token validated flag
  private $reformAccessTokenValidated = false;

  // Return Reform Access Token from Customer Data
  public function getReformAccessToken()
  {
    $accessToken = Hash::get($this->customer_data, 'AccessToken.0.token');
    if (!$this->reformAccessTokenValidated)
    {
      if ($this->__validateReformToken('access_token', $accessToken) === false)
      {
        if ($refreshToken = $this->getReformRefreshToken())
        {
          if ($this->__refreshAccessToken($refreshToken, $accessToken))
          {
            $this->customer_data['AccessToken'][0]['token'] = $accessToken;
            $this->customer_data['RefreshToken'][0]['token'] = $refreshToken;
            $this->reformAccessTokenValidated = true;
            $this->reformRefreshTokenValidated = true;
            return $accessToken;
          }
        }
        else
        {
          throw new Exception('`ReformRefreshToken` cannot be validated');
        }
        $accessToken = false;
      }
      $this->reformAccessTokenValidated = true;
    }
    return ($accessToken ? $accessToken : false);
  }

  // Reform Refresh Token validated flag
  private $reformRefreshTokenValidated = false;

  // Return Reform Refresh Token from Customer Data
  public function getReformRefreshToken()
  {
    $refreshToken = Hash::get($this->customer_data, 'RefreshToken.0.token');
    if (!$this->reformRefreshTokenValidated)
    {
      if ($this->__validateReformToken('refresh_token', $refreshToken) === false)
      {
        $refreshToken = false;
      }
      $this->reformRefreshTokenValidated = true;
    }
    return ($refreshToken ? $refreshToken : false);
  }

  // Get Access Token from Refresh Token
  private function __refreshAccessToken(&$refreshToken, &$accessToken)
  {

    // Get AccessToken from Refresh Token
    // Set Action Url
    $action = Configure::read('PlatinMarket.OAuth.protocol') . '://' . Configure::read('PlatinMarket.OAuth.host') . Configure::read('PlatinMarket.OAuth.path') . '/access_token.json';

    // Prepare Data
    $data = array(
      'platform_uuid' => Configure::read('PlatinMarket.PlatformUUID'),
      'customer_uuid' => $this->customer_data['Customer']['uuid'],
      'token' => $refreshToken,
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
      throw new Exception("Request for 'access_token' from 'refresh_token' failed. " . $response_msg['message'], $response->code);
      return;
    }

    // Parse Response
    try
    {
      $response_data = json_decode($response->body, true);
      $response_data = $response_data['result'];
      extract($response_data, EXTR_OVERWRITE);
    }
    catch (Exception $err)
    {
      throw new Exception("Parse failed get 'access_token' from 'refresh_token' failed");
    }

    // Save Tokens
    // Prepare Data
    $accessToken = $access_token;
    $access_token = array('AccessToken' => array(
      'token' => $access_token,
      'lifetime' => $access_token_lifetime,
      'customer_id' => $this->customer_data['Customer']['id']
    ));

    // Delete Old Access Token
    $this->__model('AccessToken')->deleteAll(array('AccessToken.customer_id' => $this->customer_data['Customer']['id']));

    // Save Access Token
    $this->__model('AccessToken')->create();
    if (!$this->__model('AccessToken')->save($access_token))
      throw new Exception("AccessToken create failed");

    // Prepare Data
    $refreshToken = $refresh_token;
    $refresh_token = array('RefreshToken' => array(
      'token' => $refresh_token,
      'lifetime' => $refresh_token_lifetime,
      'customer_id' => $this->customer_data['Customer']['id']
    ));

    // Delete Old Refresh Token
    $this->__model('RefreshToken')->deleteAll(array('RefreshToken.customer_id' => $this->customer_data['Customer']['id']));

    // Save Refresh Token
    $this->__model('RefreshToken')->create();
    if (!$this->__model('RefreshToken')->save($refresh_token))
      throw new Exception("RefreshToken create failed");

    return true;
  }

  // Validate Token
  private function __validateReformToken($grant_type = 'access_token', $token)
  {
    // Get AccessToken from Refresh Token
    // Set Action Url
    $action = Configure::read('PlatinMarket.OAuth.protocol') . '://' . Configure::read('PlatinMarket.OAuth.host') . Configure::read('PlatinMarket.OAuth.path') . '/validate_token.json';

    // Prepare Data
    $data = array(
      'platform_uuid' => Configure::read('PlatinMarket.PlatformUUID'),
      'customer_uuid' => $this->customer_data['Customer']['uuid'],
      'token' => $token,
      'grant_type' => $grant_type
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
      throw new Exception("Request for '" . $grant_type . "' validation failed. " . $response_msg['message'], $response->code);
      return;
    }

    // Parse Response
    try
    {
      $response_data = json_decode($response->body, true);
      return (Hash::get($response_data, 'result.valid') === true);
    }
    catch (Exception $err)
    {
      throw new Exception("Parse failed get 'access_token' from 'refresh_token' failed");
    }
  }

  // Hash secret data
  protected function __hash($clientId, $clientSecret, $data, $enc = 'sha256') {
    $SecurityData = strtoupper(hash($enc, $clientSecret . $clientId, false));
    return strtoupper(hash($enc, implode('', array_values($data)) . $SecurityData, false));
  }

  // Return Setrow Api Key from Customer Data
  public function getSetrowApiKey()
  {
    $apiKey = Hash::get($this->customer_data, 'Setrow.api_key');
    return ($apiKey ? $apiKey : false);
  }

  // Return Alias Filters
  public function getFilters($alias)
  {
    $filters = array_filter(Hash::get($this->customer_data, 'Filter'), function ($filter) use ($alias) {
      return $filter['remote'] === $alias;
    });
    return ($filters ? $filters : false);
  }

  // Get Customer Data
  public function get($path)
  {
    return Hash::get($this->customer_data, $path);
  }

  public function getTrackDate($alias, $stat = 'success')
  {
    // Create if has not
    if (!($track = array_filter($this->sync_track, function ($t) use ($alias) { return $t['alias'] === $alias; })))
    {
      $nullDate = (new DateTime('1970-01-01 00:00:01'))->format('Y-m-d H:i:s');
      $track = $this->setTrackDate($alias, $stat, $nullDate);
    }
    else
    {
      $track = array_pop($track);
    }

    // Date Time
    return new DateTime(Hash::get($track, 'last_' . $stat));
  }

  public function setTrackDate($alias, $stat = 'success', $_datetime = 'NOW', $msg = null)
  {
    // If Message not string convert to string
    if (!is_string($msg)) $msg = print_r($msg, true);

    // Create if has not
    if (!($track = array_filter($this->sync_track, function ($t) use ($alias) { return $t['alias'] === $alias; })))
    {
      $nullDate = (new DateTime('1970-01-01 00:00:01'))->format('Y-m-d H:i:s');
      $track = array(
        'customer_id' => $this->customer_id,
        'alias' => $alias,
        'last_created' => $nullDate,
        'last_modified' => $nullDate,
        'last_try' => $nullDate,
        'last_success' => $nullDate,
        'last_error' => $nullDate,
        'last_message' => $msg
      );
      if (!$this->__model('SyncTrack')->save(array('SyncTrack' => $track)))
      {
        throw new SyncHelperException(array('message' => 'New `SyncTrack` cannot be save', 'data' => array('SyncTrack' => $track), 'validation_errors' => $this->__model('SyncTrack')->validationErrors));
      }
      $track['id'] = $this->__model('SyncTrack')->id;
      $this->sync_track[] = $track;
    }
    else
    {
      $track = array_pop($track);
    }

    // Save track info
    $dateStr = (new DateTime($_datetime))->format('Y-m-d H:i:s');
    $saveData = array('SyncTrack' => array('id' => $track['id'], 'last_' . $stat => $dateStr));
    if (!empty($msg)) $saveData['SyncTrack']['last_message'] = $msg;
    if (!$this->__model('SyncTrack')->save($saveData))
    {
      throw new SyncHelperException(array('message' => '`SyncTrack` update failed', 'data' => $saveData, 'validation_errors' => $this->__model('SyncTrack')->validationErrors));
    }

    // Change private registers
    $track['last_' . $stat] = $_datetime;
    if (!empty($msg)) $track['last_message'] = $msg;
    foreach($this->sync_track as $key => $oldTrack) if ($oldTrack['id'] === $track['id']) $this->sync_track[$key] = $track;

    return $track;
  }

}
