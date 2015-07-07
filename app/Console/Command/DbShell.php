<?php

App::uses('AppShell', 'Console/Command');

class DbShell extends AppShell {

  public $uses = array('AccessToken');

  public function main(){
    $this->out('Db Tools');
  }

  public function dump(){

    $model = $this->uses[0];
    $tables = Hash::extract($this->{$model}->query('SHOW TABLES'), "{n}.TABLE_NAMES.Tables_in_setrow");
    $return = "";

    foreach($tables as $table) {

      $colTypes = Hash::combine($this->{$model}->query('SHOW FIELDS FROM ' . $table), "{n}.COLUMNS.Field", "{n}.COLUMNS.Type");

      $return .= 'DROP TABLE IF EXISTS `' . $table . '`;';
      $return .= "\n\n" . Hash::get($this->{$model}->query('SHOW CREATE TABLE ' . $table), "0.0.Create Table") . ";\n\n";

      $results = Hash::extract($this->{$model}->query('SELECT * FROM ' . $table), "{n}." . $table);
      foreach ($results as $key => $row) {
        $data = array();
        foreach ($row as $column => $value) {
          //$value = iconv('ASCII', 'UTF-8', $value);
          $value = addslashes($value);
          $value = ereg_replace("\n","\\n",$value);
          if ($this->contains($colTypes[$column], "int")) {

            if ((is_null($value) || empty($value)) && !$this->contains($colTypes[$column], "tinyint")) {
              $value = "NULL";
            } elseif ($this->contains($colTypes[$column], "tinyint")) {
              $value = $value == 1 ? "1" : "0";
            } else {
              $value = $value;
            }

          } elseif ($this->contains($colTypes[$column], "blob")) {
            $value = "X'" . bin2hex($value) . "'";
          } elseif (is_null($value)) {
            $value = "NULL";
          } elseif (empty($value)) {
            if ($this->contains($colTypes[$column], "date"))
              $value = "NULL";
            else
              $value = "''";
          } else {
            $value = "'" . $value . "'";
          }

          $data[] = $value;
        }
        $return .= 'INSERT INTO ' . $table . ' VALUES (' . implode(",", $data) . ');';
        $return .= "\n";
      }

      $return .="\n\n\n";
    }

    $handle = fopen(APP . 'Config' . DS . 'Schema' . DS . 'schema.sql','w+');
    fwrite($handle,$return);
    fclose($handle);
  }

  private function contains($str, $target) {
    $pos = strrpos($str, $target);
    if ($pos === false) {
        return false;
    } else {
        return true;
    }
  }

}
