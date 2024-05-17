<?php
namespace PhpArrayDocument;

class ArrayNode extends BaseNode implements \ArrayAccess, \IteratorAggregate, \Countable {

  /**
   * @var ArrayItemNode[]
   */
  public $items = [];

  public function __construct($items = []) {
    $this->items = $items;
  }

  public function walkNodes(string $type = BaseNode::class) {
    yield from parent::walkNodes($type);
    foreach ($this->items as $arrayItem) {
      yield from $arrayItem->walkNodes($type);
    }
  }

  public function getIterator() {
    return new \ArrayIterator($this->items);
  }

  public function count() {
    return count($this->items);
  }

  public function offsetExists($offset) {
    return isset($this->items[$offset]);
  }

  public function offsetGet($offset) {
    if (!isset($this->items[$offset])) {
      return NULL;
    }
    return $this->items[$offset]->value;
  }

  public function offsetSet($offset, $value) {
    if (!($value instanceof BaseNode)) {
      $type = gettype($value);
      if ($type === 'object') {
        $type = get_class($value);
      }
      throw new \RuntimeException(sprintf("Cannot add object (%s) to ArrayValueNode", $type));
    }
    $this->items[$offset]->value = $value;
  }

  public function offsetUnset($offset) {
    unset($this->items[$offset]);
  }

}
