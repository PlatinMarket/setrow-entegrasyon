<?php

// stylesheets
echo $this->Bower->component('css', '/components/bootstrap/dist/css/bootstrap.min.css');
echo $this->Bower->component('css', '/components/fontawesome/css/font-awesome.min.css');

// scripts
echo $this->Bower->component('js', '/components/jquery/dist/jquery.min.js');
echo $this->Bower->component('js', '/components/bootstrap/dist/js/bootstrap.min.js');

// user_defined scripts
echo $this->fetch('meta');
echo $this->fetch('css');
echo $this->fetch('script');
?>
