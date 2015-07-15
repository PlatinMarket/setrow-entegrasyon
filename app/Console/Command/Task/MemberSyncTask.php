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

  // Reform -> Setrow Field Mapper
  private $mapper = array(
    'Member.member_ID' => 'oalan40',
    'Member.member_EMAIL' => 'adres',
    'Member.member_NAME' => 'isim',
    'Member.member_SEX' => 'cinsiyet', // e => erkek, k => kadın
    'Member.member_BIRTH_DATE' => 'dtarihi', // yyyy-aa-gg
    'Member.member_CITY' => 'sehir',
    'Member.member_GSM' => 'ceptel',  // 0XXXXXXXXXX
    'Member.member_PHONE' => 'sabittel', // 0XXXXXXXXXX
    'Member.member_GSM' => 'ceptel',
    'Member.member_COUNTRY' => 'oalan41',
    'Member.member_CITY' => 'oalan42',
    'Member.member_STATE' => 'oalan43',
    'Member.member_TYPE' => 'oalan44', // Member Type 1 => bireysel, 2 => kurumsal
    'Member.member_FIRMA_ISIM' => 'oalan45',
    'Member.member_FIRMA_UNVAN' => 'oalan46',
  );

  // Before setting member_NAME
  private function beforeSetMemberName($member)
  {
    return sprintf("%s %s", Hash::get($member, 'Member.member_NAME'), Hash::get($member, 'Member.member_SURNAME'));
  }

  // Before setting member_SEX
  private function beforeSetMemberSex($member)
  {
    return (Hash::get($member, 'Member.member_SEX') == 1 ? 'e' : 'k');
  }

  // Before setting member_GSM
  private function beforeSetMemberGsm($member)
  {
    return str_replace(array('(',')',' ', '-'), array('0', '', '', ''), Hash::get($member, 'Member.member_GSM'));
  }

  // Before setting member_PHONE
  private function beforeSetMemberPhone($member)
  {
    return str_replace(array('(',')',' ', '-'), array('0', '', '', ''), Hash::get($member, 'Member.member_PHONE'));
  }

  // Before setting member_TYPE
  private function beforeSetMemberType($member)
  {
    return (Hash::get($member, 'Member.member_TYPE') == 1 ? 'bireysel' : 'kurumsal');
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
      $syncHelper->logWithTitle('MemberSync', 'Senkronizasyon başladı');

      // General Log
      $syncHelper->log('debug', 'MemberSync started for customer `' . $syncHelper->get('Customer.domain') . '`');

      // Check If Member Filter
      if (!($filters = $syncHelper->getFilters('Member')))
      {
        $syncHelper->logWithTitle('MemberSync', 'Senkronizasyon bitti. Filitre bulunamadı.');
        $syncHelper->log('debug', '`' . $syncHelper->get('Customer.domain') . '` has no filter');
        $syncHelper->log('debug', 'MemberSync end for customer `' . $syncHelper->get('Customer.domain') . '`');
        continue;
      }

      // Get If MemberMappers
      if (!($memberMappers = $syncHelper->get('MemberMapper')))
      {
        $syncHelper->logWithTitle('MemberSync', 'Senkronizasyon bitti. Üye eşleşme ayarı bulunamadı.');
        $syncHelper->log('debug', '`' . $syncHelper->get('Customer.domain') . '` has no member filter');
        $syncHelper->log('debug', 'MemberSync end for customer `' . $syncHelper->get('Customer.domain') . '`');
        continue;
      }

      // Set Api Lib
      $s = new SetrowApi($syncHelper->getSetrowApiKey());
      $r = new ReformApi($syncHelper->getReformAccessToken());

      // Counters
      $added_members = 0;
      $error_members = 0;

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

        try
        {
          // Track Alias
          $trackAlias = 'Member:Filter:'. $filter['id'] . ':Mapper:' . $memberMapper['id'];
          // Checking Created Members
          $last_member_created = $syncHelper->getTrackDate($trackAlias, 'created');
          $badMembers = array_filter(Hash::get($customer, 'BadMember'), function($b) use ($memberMapper) { return $b['member_mapper_id'] == $memberMapper['id']; });
          $new_members = Hash::get($r->callMembers($this->__prepareOptions('track_insert', $filter['query'], $last_member_created, $badMembers)), 'data');
          foreach($new_members as $new_member)
          {
            try
            {
              $grupid = Hash::get($memberMapper, 'grupid');
              $response = $s->adres_ekle(array_merge(array('grupid' => $grupid), $this->__prepareMemberData($new_member)));
              $resultCode = Hash::get($response, 'data.code');
              if (Hash::get($response, 'result') == 'error' && $resultCode >= 4)
              {
                $errorMsg = Hash::get($response, 'data.msg');
                $syncHelper->log('error', array('message' => $errorMsg, 'attributes' => $response));
                $syncHelper->logWithTitle('MemberSync', '`' . $filter['label'] . '` senkronize olurken hata oluştu. ' . $errorMsg);
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
              $syncHelper->logWithTitle('MemberSync', '`' . $filter['label'] . '` senkronize olurken hata oluştu. ' . $errorMsg);
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
          $syncHelper->logWithTitle('MemberSync', '`' . $filter['label'] . '` senkronize olurken hata oluştu. ' . $errorMsg);
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
      } // foreach mamber mapper

      // Log
      $syncHelper->logWithTitle('MemberSync', 'Senkronizasyon bitti. Eklenen: ' . $added_members . ' / Hata Alınan: ' . $error_members );
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
