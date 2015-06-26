<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo $this->fetch('title'); ?>
	</title>
	<?php echo $this->element('components'); ?>
</head>
<body>
	<div class="container-fluid">
		<div class="row">
			<div class="col col-xs-12">
				<?php echo $this->Session->flash(); ?>
			</div>
			<div class="col col-xs-12">
				<?php echo $this->fetch('content'); ?>
			</div>
		</div>
	</div>
</body>
</html>
