<?php
namespace PhpArrayDocument;

abstract class BaseNode {

  /**
   * @var string|null
   *   Ex: 'ts' or 'E::ts' or 'Some\Class\Name::ts'
   */
  public $factory = NULL;

  /**
   * Does this data use deferred construction (`fn() => [...data..]`)?
   *
   * @var bool
   */
  public $deferred = FALSE;

  /**
   * @var string|null
   */
  public $comment = NULL;

  /**
   * @template T of BaseNode
   * @param class-string<T> $type
   * @return Generator<T>
   */
  public function walkNodes(string $type = BaseNode::class) {
    if ($type === NULL || $this instanceof $type) {
      yield $this;
    }
  }

}
