<?php
namespace PhpArrayDocument;

class ArrayItemNode extends BaseNode {

  public $key;

  /**
   * @var \PhpArrayDocument\ScalarNode|\PhpArrayDocument\ArrayNode|null
   */
  public $value;

  public function __construct($key, BaseNode $value) {
    $this->key = $key;
    $this->value = $value;
  }

  public function walkNodes(string $type = BaseNode::class) {
    if ($type === NULL || $this instanceof $type) {
      yield $this;
    }
    if ($this->value) {
      $this->value->walkNodes($type);
    }
  }

}
