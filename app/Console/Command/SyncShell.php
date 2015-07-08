<?php

App::uses('AppShell', 'Console/Command');

class SyncShell extends AppShell {

  public $tasks = array('MemberSync');

  public function main(){
    $this->out('Sync Tools');
  }

  public function members()
  {
    $this->MemberSync->execute();
  }

}
