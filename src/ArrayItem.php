<?php
namespace PhpArrayDocument;

class ArrayItem {

  public ?string $comment = NULL;

  public $key;

  public ValueNode $value;

  public function __construct($key, ValueNode $value) {
    $this->key = $key;
    $this->value = $value;
  }

}
