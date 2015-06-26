<?php echo $this->Form->create('BoostCake', array(
	'inputDefaults' => array(
		'div' => 'form-group',
		'wrapInput' => false,
		'class' => 'form-control'
	),
	'class' => 'well'
)); ?>
	<fieldset>
		<legend><?php echo __("Setrow entegrasyonu kurulumu"); ?></legend>
		<?php echo $this->Form->input('text', array(
			'label' => 'Setrow api anahtarı',
			'after' => '<span class="help-block">* Setrow paneli > Gönderim Ayarları > API Key</span>'
		)); ?>
		<?php echo $this->Form->submit('Kaydet', array(
			'div' => 'form-group',
			'class' => 'btn btn-success'
		)); ?>
	</fieldset>
<?php echo $this->Form->end(); ?>
