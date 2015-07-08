<?php

App::uses('SyncHelper', 'Lib');
App::uses('ReformApi', 'ReformApi.Lib');
App::uses('SetrowApi', 'Setrow.Lib');

class MemberSyncTask extends Shell
{
  // Using Customer Model
  public $uses = array('Customer');

  // Limit for sync count
  private $limit = 10;

  // Execute Task
  public function execute()
  {
    // Get Customers
    $customers = $this->Customer->find('all', SyncHelper::getCustomerOptions());

    // For all customer do sync
    foreach ($customers as $customer) {

      // Init SyncHelper
      $syncHelper = new SyncHelper($customer);

      // Customer Log
      $syncHelper->logWithTitle('MemberSync', 'Senkronizasyon başladı');

      // General Log
      $syncHelper->log('debug', 'Sync started for customer `' . $syncHelper->get('Customer.domain') . '`');

      // Check If Member Filter
      if (!($filters = $syncHelper->getFilters('Member')))
      {
        $syncHelper->logWithTitle('MemberSync', 'Senkronizasyon bitti. Filitre bulunamadı.');
        $syncHelper->log('debug', '`' . $syncHelper->get('Customer.domain') . '` has no filter');
        $syncHelper->log('debug', 'Sync end for customer `' . $syncHelper->get('Customer.domain') . '`');
        continue;
      }

      // Get If MemberMappers
      if (!($memberMappers = $syncHelper->get('MemberMapper')))
      {
        $syncHelper->logWithTitle('MemberSync', 'Senkronizasyon bitti. Üye eşleşme ayarı bulunamadı.');
        $syncHelper->log('debug', '`' . $syncHelper->get('Customer.domain') . '` has no member filter');
        $syncHelper->log('debug', 'Sync end for customer `' . $syncHelper->get('Customer.domain') . '`');
        continue;
      }

      // Set Api Lib
      $s = new SetrowApi($syncHelper->getSetrowApiKey());
      $r = new ReformApi($syncHelper->getReformAccessToken());

      // For customer's filters do sync
      foreach ($memberMappers as $memberMapper)
      {
        if (empty($filter = array_filter($filters, function($f) use ($memberMapper) { return ($memberMapper['filter_id'] === $f['id']); })))
        {
          $errorMsg = 'MemberMapper::' . $memberMapper['id'] . ' not associated with any filter';
          $syncHelper->log('error', $errorMsg);
          $syncHelper->logWithTitle('MemberSync', '`MemberMapper::' . $memberMapper['id'] . '` hiç bir filitre ile ilişkilendirilmemiş');
          continue;
        }

        // Choose Filter last
        $filter = array_pop($filter);

        // General Log
        $syncHelper->log('debug', '`' . $filter['label'] . '` started');

        $sync_summary = array('created' => 0, 'modified' => 0);
        try
        {
          // Checking Created Members
          $last_member_created = $syncHelper->getTrackDate('Member:Filter:'. $filter['id'], 'created');
          $new_members = Hash::get($r->callMembers($this->__prepareOptions('track_insert', $filter['query'], $last_member_created)), 'data');
          print_r($new_members);

          //$last_member_modified = $syncHelper->getTrackDate('Member:Filter:'. $filter['id'], 'modified');
          //$updated_members = Hash::get($r->callMembers($this->__prepareOptions('track_update', $filter['query'], $last_member_modified)), 'data');
          //print_r($updated_members);
        }
        catch (ReformApiException $err)
        {
          $errorMsg = $err->getMessage();
          $attr = $err->getAttributes();
          if (is_string(Hash::get($attr, 'response.data'))) $errorMsg .= ". " . Hash::get($attr, 'response.data');
          $syncHelper->log('error', array('message' => $errorMsg, 'attributes' => $attr));
          $syncHelper->logWithTitle('MemberSync', '`' . $filter['label'] . '` senkronize olurken hata oluştu. ' . $errorMsg);
          $syncHelper->TrackDate('Member:Filter:'. $filter['id'], 'created');
        }
        catch (Exception $err)
        {
          $errorMsg = $err->getMessage();
          $syncHelper->log('error', $errorMsg);
        }
        finally
        {
          // General Log
          $syncHelper->log('debug', '`' . $filter['label'] . '` ended');
        }
      }

      // Log Success
      $syncHelper->logWithTitle('MemberSync', 'Senkronizasyon bitti.');
      $syncHelper->log('debug', 'Sync end for customer `' . $syncHelper->get('Customer.domain') . '`');
    }
  }

  private function __prepareOptions($alias = 'track_insert', $filter, $_datetime)
  {
    if ($_datetime instanceof DateTime) $_datetime = $_datetime->format('Y-m-d H:i:s');
    $conditions = array('Member.' . $alias . ' >' => $_datetime, 'Member.member_DURUM' => 1);
    $conditions = array_merge($conditions, $filter);
    return array('limit' => $this->limit, 'conditions' => $conditions, 'offset' => 0);
  }

}
