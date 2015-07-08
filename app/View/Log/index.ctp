<div class="col col-xs-12">
  <?php foreach($log as $key => $l) { ?>
  	<h3><?php echo date("d/m/Y", strtotime($key)); ?></h3>
  	<table width="100%" class="table table-striped">
  		<thead>
  			<tr>
  				<th width="100">Tarih</th>
  				<th width="100">Zaman</th>
  				<th>Giriş</th>
  			</tr>
  		</thead>
  		<tbody>
  	<?php foreach($log[$key] as $item) { ?>
  		<tr>
  			<td>
  				<?php echo date("d/m/Y", strtotime($key)); ?>
  			</td>
  			<td>
  				<?php echo $item['timestamp']; ?>
  			</td>
  			<td>
  				<?php echo $item['message']; ?>
  			</td>
  		</tr>
  	<?php } ?>
  		</tbody>
  	</table>
  <?php } ?>
</div>
