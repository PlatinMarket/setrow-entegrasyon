<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo $this->fetch('title'); ?>
	</title>
	<?php echo $this->element('components'); ?>

	<?php echo $this->Html->css('style'); ?>
</head>
<body>
	<?php echo $this->element('menu'); ?>
	<div class="container-fluid">
		<div class="row">
			<?php echo $this->Session->flash(); ?>

			<?php echo $this->fetch('content'); ?>
		</div>
	</div>
</body>
</html>
