<?php

App::uses('SyncHelperException', 'ReformApi.Lib');
App::uses('ReformApi', 'ReformApi.Lib');

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

  // Return Reform Access Token from Customer Data
  public function getReformAccessToken()
  {
    $accessToken = Hash::get($this->customer_data, 'AccessToken.0.token');
    return ($accessToken ? $accessToken : false);
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
