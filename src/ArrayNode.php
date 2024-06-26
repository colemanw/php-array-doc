<?php
namespace PhpArrayDocument;

/**
 * A PHP array. There are a few ways to access its children:
 *
 * - $array[$key]: Short-hand to read/write the value of an item.
 * - $array->getItem($key): Returns the metadata (ArrayItem),
 *     which includes the key, docblocks, and value.
 * - $array->walkNodes(): Recursively the subtree
 *
 */
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

  public function getItem($key): ?ArrayItemNode {
    foreach ($this->items as $arrayItem) {
      if ($arrayItem->key == $key) {
        return $arrayItem;
      }
    }
    return NULL;
  }

  public function getItemPosition($key) {
    foreach ($this->items as $arrayItem) {
      if ($arrayItem->key == $key) {
        return $arrayItem;
      }
    }
    return NULL;
  }

  public function getIterator(): \Traversable {
    return new \ArrayIterator($this->items);
  }

  public function count(): int {
    return count($this->items);
  }

  public function offsetExists($offset): bool {
    return $this->getItem($offset) !== NULL;
  }

  #[\ReturnTypeWillChange]
  public function offsetGet($offset) {
    $item = $this->getItem($offset);
    return $item ? $item->value : NULL;
  }

  public function offsetSet($offset, $value): void {
    if (!($value instanceof ScalarNode || $value instanceof ArrayNode)) {
      $type = gettype($value);
      if ($type === 'object') {
        $type = get_class($value);
      }
      throw new \RuntimeException(sprintf("Cannot add object (%s) to ArrayValueNode", $type));
    }
    if ($item = $this->getItem($offset)) {
      $item->value = $value;
    }
    else {
      $this->items[] = new ArrayItemNode($offset, $value);
    }
  }

  public function offsetUnset($offset): void {
    $pos = $this->getItemPosition($offset);
    if ($pos !== NULL) {
      unset($this->items[$pos]);
    }
  }

}
