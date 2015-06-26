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
          <li><a href="#">Link</a></li>
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a>
            <ul class="dropdown-menu">
              <li><a href="#">Action</a></li>
              <li><a href="#">Another action</a></li>
              <li><a href="#">Something else here</a></li>
              <li role="separator" class="divider"></li>
              <li><a href="#">Separated link</a></li>
            </ul>
          </li>
        <?php } else { ?>
          
        <?php } ?>
      </ul>
    </div>
  </div>
</nav>
