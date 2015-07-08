<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="<?php echo Router::url("/"); ?>">
        <?php echo $this->Html->image("setrow_logo.png", array("alt" => "Brand")); ?>
      </a>
    </div>
    <div class="collapse navbar-collapse">
      <ul class="nav navbar-nav navbar-right">
        <?php if (isset($customer_data["Customer"]["is_installed"]) && $customer_data["Customer"]["is_installed"] == 1) { ?>
          <li><?php echo $this->Html->link('<i class="fa fa-clock-o"></i> Günlük', array('session_id' => $session_id, 'controller' => 'log', 'action' => 'index'), array('escape' => false)) ?></li>
          <li><?php echo $this->Html->link('<i class="fa fa-sliders"></i> Ayarlar', array('session_id' => $session_id, 'controller' => 'config', 'action' => 'settings'), array('escape' => false)) ?></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <script>
    var current_target = '<?php echo Router::url(array('session_id' => $session_id)); ?>';
    $('nav.navbar ul.nav > li > a').each(function(){
      var target_link = $(this).attr("href").toString();
      if (target_link.indexOf(current_target) != -1) $(this).parent().addClass("active");
    });
  </script>
</nav>
