<?php

App::uses('SyncHelper', 'Lib');
App::uses('ReformApi', 'ReformApi.Lib');
App::uses('SetrowApi', 'Setrow.Lib');

class ProductSyncTask extends Shell
{
  // Using Customer Model
  public $uses = array('Customer');

  // Limit for sync count
  private $limit = 10;

  // Reform -> Setrow Field Mapper
  private $mapper = array(
    'Product.pro_ID' => 'oalan40',
    'Product.pro_STOCK_CODE' => 'adres',
    'Product.member_NAME' => 'isim',
    'Product.member_SEX' => 'cinsiyet', // e => erkek, k => kadın
    'Product.member_BIRTH_DATE' => 'dtarihi', // yyyy-aa-gg
    'Product.member_CITY' => 'sehir',
    'Product.member_GSM' => 'ceptel',  // 0XXXXXXXXXX
    'Product.member_PHONE' => 'sabittel', // 0XXXXXXXXXX
    'Product.member_GSM' => 'ceptel',
    'Product.member_COUNTRY' => 'oalan41',
    'Product.member_CITY' => 'oalan42',
    'Product.member_STATE' => 'oalan43',
    'Product.member_TYPE' => 'oalan44', // Product Type 1 => bireysel, 2 => kurumsal
    'Product.member_FIRMA_ISIM' => 'oalan45',
    'Product.member_FIRMA_UNVAN' => 'oalan46',
  );

  // Before setting member_NAME
  private function beforeSetMemberName($member)
  {
    return sprintf("%s %s", Hash::get($member, 'Member.member_NAME'), Hash::get($member, 'Member.member_SURNAME'));
  }

  // Prepare data for save / create to setrow
  private function __prepareMemberData($member)
  {
    $setrowMember = array();
    foreach($this->mapper as $reform_key => $setrow_key)
    {
      $fieldName = Inflector::camelize(strtolower(str_replace('Member.', '', $reform_key)));
      if (method_exists($this, 'beforeSet' . $fieldName))
        $value = $this->{'beforeSet' . $fieldName}($member);
      else
        $value = Hash::get($member, $reform_key);

      if (!$value) continue;
      $setrowMember[$setrow_key] = $value;
    }
    return $setrowMember;
  }

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
      $syncHelper->logWithTitle('ProductSync', 'Senkronizasyon başladı');

      // General Log
      $syncHelper->log('debug', 'ProductSync started for customer `' . $syncHelper->get('Customer.domain') . '`');

      // Check If Member Filter
      if (!($filters = $syncHelper->getFilters('Product')))
      {
        $syncHelper->logWithTitle('ProductSync', 'Senkronizasyon bitti. Filitre bulunamadı.');
        $syncHelper->log('debug', '`' . $syncHelper->get('Customer.domain') . '` has no filter');
        $syncHelper->log('debug', 'ProductSync end for customer `' . $syncHelper->get('Customer.domain') . '`');
        continue;
      }

      // Get Product Filter
      $filter = array_filter($filters, function($f) { return !empty($f['customer_id']); });
      if (empty($filter)) $filter = $filters;
      $filter = array_pop($filters);

      // Set Api Lib
      $s = new SetrowApi($syncHelper->getSetrowApiKey());
      $r = new ReformApi($syncHelper->getReformAccessToken());

      // Counters
      $added_products = 0;
      $added_error_products = 0;
      $updated_products = 0;
      $updated_error_products = 0;

      // Choose Filter last
      $filter = array_pop($filter);

      try
      {
        // Track Alias
        $trackAlias = 'Product:Filter:'. $filter['id'];
        // Checking Created Members
        $last_member_created = $syncHelper->getTrackDate($trackAlias, 'created');
        $badProducts = array_filter(Hash::get($customer, 'BadProduct'), function($b) use ($filter) { return $b['filter_id'] == $filter['id']; });
        $new_products = Hash::get($r->callMembers($this->__prepareOptions('track_insert', $filter['query'], $last_member_created, $badProducts)), 'data');
        foreach($new_products as $new_product)
        {
          try
          {
            $response = $s->adres_ekle(array_merge(array('grupid' => $grupid), $this->__prepareMemberData($new_member)));
            $resultCode = Hash::get($response, 'data.code');
            if (Hash::get($response, 'result') == 'error' && $resultCode >= 4)
            {
              $errorMsg = Hash::get($response, 'data.msg');
              $syncHelper->log('error', array('message' => $errorMsg, 'attributes' => $response));
              $syncHelper->logWithTitle('ProductSync', '`' . $filter['label'] . '` senkronize olurken hata oluştu. ' . $errorMsg);
              $syncHelper->setTrackDate($trackAlias, 'error', (new DateTime('NOW'))->format('Y-m-d H:i:s'), $errorMsg);
              $this->__addBadMember($new_member, $syncHelper->customer_id, $memberMapper['id'], $errorMsg);
              $error_members++;
            }
            elseif (Hash::get($response, 'result') == 'success' && $resultCode < 4)
            {
              $added_members++;
              $syncHelper->setTrackDate($trackAlias, 'created', (new DateTime(Hash::get($new_member, 'Member.track_insert')))->format('Y-m-d H:i:s'));
              $syncHelper->setTrackDate($trackAlias, 'success', (new DateTime('NOW'))->format('Y-m-d H:i:s'));
            }
          }
          catch (SetrowApiException $err)
          {
            $error_members++;
            $errorMsg = $err->getMessage();
            $attr = $err->getAttributes();
            if (is_string(Hash::get($attr, 'response_body'))) $errorMsg .= ". " . Hash::get($attr, 'response_body');
            $syncHelper->log('error', array('message' => $errorMsg, 'attributes' => $attr));
            $syncHelper->logWithTitle('ProductSync', '`' . $filter['label'] . '` senkronize olurken hata oluştu. ' . $errorMsg);
            $syncHelper->setTrackDate($trackAlias, 'error', (new DateTime('NOW'))->format('Y-m-d H:i:s'), $errorMsg);
            continue;
          }
          finally
          {
            $syncHelper->setTrackDate($trackAlias, 'try', (new DateTime('NOW'))->format('Y-m-d H:i:s'));
          } // try catch member add
        } // foreach member
      } // general catch
      catch (ReformApiException $err)
      {
        $errorMsg = $err->getMessage();
        $attr = $err->getAttributes();
        if (is_string(Hash::get($attr, 'response.data'))) $errorMsg .= ". " . Hash::get($attr, 'response.data') . ".";
        if (is_string(Hash::get($attr, 'response.error.message'))) $errorMsg .= ". " . Hash::get($attr, 'response.error.message') . ".";
        $syncHelper->log('error', array('message' => $errorMsg, 'attributes' => $attr));
        $syncHelper->logWithTitle('ProductSync', '`' . $filter['label'] . '` senkronize olurken hata oluştu. ' . $errorMsg);
        $syncHelper->setTrackDate($trackAlias, 'error', (new DateTime('NOW'))->format('Y-m-d H:i:s'), $errorMsg);
      }
      catch (Exception $err)
      {
        $errorMsg = $err->getMessage();
        $syncHelper->log('error', $errorMsg . "\r\n" . $err->getTraceAsString());
      }
      finally
      {
        // General Log
        $syncHelper->log('debug', '`' . $filter['label'] . '` ended');
      }

      // Log
      $syncHelper->logWithTitle('ProductSync', 'Senkronizasyon bitti. Eklenen: ' . $added_members . ' / Hata Alınan: ' . $error_members );
      $syncHelper->log('debug', 'Sync end for customer `' . $syncHelper->get('Customer.domain') . '`');
    } // foreach customer
  }

  // Prepare Find Options
  private function __prepareOptions($alias = 'track_insert', $filter, $_datetime, $badMembers)
  {
    if ($_datetime instanceof DateTime) $_datetime = $_datetime->format('Y-m-d H:i:s');
    $conditions = array('Member.' . $alias . ' >' => $_datetime, 'Member.member_DURUM' => 1);
    $conditions = array_merge($conditions, $filter);
    if ($badMemberIds = Hash::extract($badMembers, '{n}.member_ID'))
    {
      $conditions = array('and' => array($conditions, array('Member.member_ID !=' => $badMemberIds)));
    }
    return array('limit' => $this->limit, 'conditions' => $conditions, 'offset' => 0, 'order' => 'Member.track_insert ASC');
  }

  // Save Bad Member
  private function __addBadMember($member, $customer_id, $member_mapper_id, $reason = '')
  {
    if (empty($this->Customer->BadMember->findByMemberId(Hash::get($member, 'Member.member_ID'))))
    {
      $badMember = array('BadMember' => array(
        'customer_id' => $customer_id,
        'member_mapper_id' => $member_mapper_id,
        'member_ID' => Hash::get($member, 'Member.member_ID'),
        'member_EMAIL' => Hash::get($member, 'Member.member_EMAIL'),
        'reason' => $reason
      ));
      $this->Customer->BadMember->create();
      $this->Customer->BadMember->save($badMember);
    }
  }

}
