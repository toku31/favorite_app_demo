<?php
require_once(__DIR__ . '/../library.php');

abstract class BaseModel {
  protected $db;

  public function __construct(){
    $this->db = dbConnect();
  }

}

?>