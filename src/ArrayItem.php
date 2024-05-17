<?php
namespace PhpArrayDocument;

class ArrayItem {

  /**
   * @var string|null
   */
  public $comment = NULL;

  public $key;

  /**
   * @var \PhpArrayDocument\ValueNode
   */
  public $value;

  public function __construct($key, ValueNode $value) {
    $this->key = $key;
    $this->value = $value;
  }

}
