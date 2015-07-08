<div class="col col-xs-12">
  <?php echo $this->Form->create('Setrow', array(
    'inputDefaults' => array(
  		'div' => 'form-group',
  		'label' => false,
  		'wrapInput' => false,
  		'class' => 'form-control'
  	),
  	'class' => 'form-inline'
  )); ?>

    <fieldset style="margin-bottom:40px;">
  		<legend><?php echo __("Api Anahtarı"); ?></legend>
    		<?php echo $this->Form->input('Setrow.id', array('type' => 'hidden')); ?>
    		<?php echo $this->Form->input('Setrow.customer_id', array('value' => $customer_data['Customer']['id'], 'type' => 'hidden')); ?>
    		<?php echo $this->Form->input('Setrow.api_key', array(
    			'placeholder' => 'Setrow api anahtarı',
          'style' => 'width:100%',
          'div' => array('style' => 'width:100%'),
    			'after' => '<span class="help-block">* Setrow paneli > Gönderim Ayarları > API Key</span>'
    		)); ?>
  	</fieldset>

    <fieldset style="margin-bottom:40px;">
  		<legend><?php echo __("Üye Ayarları"); ?></legend>
      <?php
        $syncRecordCursor = 0;
        foreach ($this->request->data['MemberMapper'] as $key => $syncRecord)
        {
          if (!isset($syncRecord['id']) || empty($syncRecord['id'])) continue;
      ?>
          <div class="form-group" style="width:100%;margin-bottom:10px;">
        		<?php echo $this->Form->input('MemberMapper.' . $syncRecordCursor . '.id', array('type' => 'hidden')); ?>
        		<?php echo $this->Form->input('MemberMapper.' . $syncRecordCursor . '.customer_id', array('value' => $customer_data['Customer']['id'], 'type' => 'hidden')); ?>
        		<?php echo $this->Form->input('MemberMapper.' . $syncRecordCursor . '.filter_id', array('label' => array('text' => 'E-Store: ', 'style' => 'margin-right:10px;'), 'style' => 'min-width:100px;')); ?>
        		<?php echo $this->Form->input('MemberMapper.' . $syncRecordCursor . '.grupid', array('label' => array('text' => 'Setrow grup: ', 'style' => 'margin-left:10px;margin-right:10px;'), 'style' => 'min-width:100px;', 'options' => $grup_listesi, 'escape' => false)); ?>
        		<?php echo $this->Form->input('MemberMapper.' . $syncRecordCursor . '.delete', array('label' => array('text' => 'Sil', 'style' => 'margin-left:10px;margin-right:10px;'), 'type' => 'checkbox', 'class' => false, 'div' => 'checkbox')); ?>
          </div>
      <?php
          $syncRecordCursor++;
        }
      ?>
      <div class="form-group" style="width:100%;">
    		<?php echo $this->Form->input('MemberMapper.' . $syncRecordCursor . '.customer_id', array('value' => $customer_data['Customer']['id'], 'type' => 'hidden')); ?>
    		<?php echo $this->Form->input('MemberMapper.' . $syncRecordCursor . '.filter_id', array('label' => array('text' => 'E-Store: ', 'style' => 'margin-right:10px;'), 'style' => 'min-width:100px;', 'empty' => 'Seçiniz', 'required' => false)); ?>
    		<?php echo $this->Form->input('MemberMapper.' . $syncRecordCursor . '.grupid', array('label' => array('text' => 'Setrow grup: ', 'style' => 'margin-left:10px;margin-right:10px;'), 'style' => 'min-width:100px;', 'options' => $grup_listesi, 'escape' => false, 'empty' => 'Seçiniz', 'required' => false)); ?>
        <span class="help-block">* E-Store üzerinden Setrow tarafına güncellenecek/eklenecek kişi gruplarını eşleştiriniz</span>
      </div>
  	</fieldset>

    <fieldset style="margin-bottom:40px;">
  		<legend><?php echo __("Senkronizasyon Ayarları"); ?></legend>
      <?php echo $this->Form->input('SyncConfig.id', array('type' => 'hidden')); ?>
      <?php echo $this->Form->input('SyncConfig.period', array(
        'label' => array('text' => 'Senkronizasyon periyodu <small class="text-muted">(Varsayılan değer 1 dk)</small>: ', 'style' => 'margin-right:10px;'),
        'style' => 'width:100px;text-align:right;',
        'type' => 'text',
        'beforeInput' => '<div class="input-group">',
    		'afterInput' => '<span class="input-group-addon">dk</span></div>',
        'error' => false
      )); ?>
      <?php echo $this->Form->input('SyncConfig.active', array('label' => array('text' => 'Sistem Aktif', 'style' => 'margin-left:10px;margin-right:10px;'), 'class' => false, 'div' => 'checkbox')); ?>
  	</fieldset>

    <div class="form-group" style="width:100%;">
      <?php echo $this->Form->submit('Kaydet', array(
        'div' => false,
        'class' => 'btn btn-success',
        'data-loading-text' => 'Lütfen Bekleyin...'
      )); ?>
      <span style="margin-left:5px;margin-right:5px;">veya</span>
      <?php echo $this->Html->link('İptal et', array('session_id' => $session_id, 'plugin' => null, 'controller' => 'log', 'action' => 'index')); ?>
    </div>
  <?php echo $this->Form->end(); ?>
</div>
<script>
  $("input.btn.btn-success").on('click', function(){
    $(this).button('loading')
  });
</script>
