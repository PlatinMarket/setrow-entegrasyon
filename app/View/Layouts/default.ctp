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
				<h3>Welcome</h3>
			</div>
		</div>
		<div class="row">
			<?php echo $this->Session->flash(); ?>

			<?php echo $this->fetch('content'); ?>
		</div>
	</div>
</body>
</html>
