<?php
include_once("storage.php");

class CarStorage extends Storage {
  public function __construct() {
    parent::__construct(new JsonIO('storage/carStorage.json'));
  }
}