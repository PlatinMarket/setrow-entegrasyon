<div class="col col-xs-12">
  <?php echo $this->Form->create('Setrow', array(
  	'inputDefaults' => array(
  		'div' => 'form-group',
  		'wrapInput' => false,
  		'class' => 'form-control'
  	)
  )); ?>
  	<fieldset>
  		<legend><?php echo __("Setrow Api Anahtarı"); ?></legend>
  		<?php echo $this->Form->input('Setrow.id', array('type' => 'hidden')); ?>
  		<?php echo $this->Form->input('Setrow.customer_id', array('value' => $customer_data['Customer']['id'], 'type' => 'hidden')); ?>
  		<?php echo $this->Form->input('Setrow.api_key', array(
  			'label' => 'Setrow api anahtarı',
  			'after' => '<span class="help-block">* Setrow paneli > Gönderim Ayarları > API Key</span>'
  		)); ?>
  		<?php echo $this->Form->submit('Kaydet', array(
  			'div' => 'form-group',
  			'class' => 'btn btn-success'
  		)); ?>
  	</fieldset>
  <?php echo $this->Form->end(); ?>
</div>
