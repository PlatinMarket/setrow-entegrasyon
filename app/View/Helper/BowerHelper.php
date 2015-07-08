<?php

App::uses('AppHelper', 'View/Helper');

class BowerHelper extends AppHelper
{
    public $helpers = array('Html');

    public function component($type, $location)
    {
      $location = Router::url($location);
      switch ($type)
      {
        case 'js':
          return '<script src="' . $location . '"></script>';
          break;
        case 'css':
          return '<link rel="stylesheet" type="text/css" href="' . $location . '">';
          break;
      }

    }
}
