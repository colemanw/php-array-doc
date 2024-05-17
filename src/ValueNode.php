<?php
namespace PhpArrayDocument;

abstract class ValueNode {

  public ?string $factory = NULL;

  public bool $deferred = FALSE;

  /**
   * @template T of ValueNode
   * @param class-string<T> $type
   * @return Generator<T>
   */
  public function walkNodes(string $type = ValueNode::class) {
    if ($type === NULL || $this instanceof $type) {
      yield $this;
    }
  }

}
