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
   * @var array
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

  /**
   * Get a clean version of the comment, without any comment-markers.
   *
   * @return string|null
   */
  public function getCleanComment(): ?string {
    if ($this->comment === NULL || $this->comment === []) {
      return NULL;
    }

    $buf = '';
    foreach ($this->comment as $comment) {
      $comment = trim($comment);
      if (substr($comment, 0, 2) === '//') {
        $buf .= trim(substr($comment, 2)) . "\n";
      }
      elseif (substr($comment, 0, 3) === '/**') {
        $lines = explode("\n", trim(substr($comment, 3, -2)));
        $lines = preg_replace('/^\s*\* ?/', '', $lines);
        $buf .= implode("\n", $lines) . "\n";
      }
      elseif (substr($comment, 0, 2) === '/*') {
        $buf .= trim(substr($comment, 2, -2)) . "\n";
      }
      elseif ($comment === '') {
        // ignore
      }
      else {
        throw new \LogicException("Malformed comment");
      }
    }

    return $buf;
  }

  /**
   * Set the clean version of the comment. (Comment markers will be added automatically.)
   *
   * @param string|null $comment
   */
  public function setCleanComment(?string $comment): void {
    if ($comment === NULL) {
      $this->comment = [];
      return;
    }

    $buf = "/**\n";
    $lines = explode("\n", rtrim($comment, "\n"));
    foreach ($lines as $line) {
      $buf .= ' * ' . $line . "\n";
    }
    $buf .= " */\n";
    $this->comment = [$buf];
  }

  /**
   * Get the raw comment code, including the comment markers.
   *
   * @param string $prefix
   * @return string|null
   */
  public function getRawComment(string $prefix = ''): ?string {
    if ($this->comment === NULL || $this->comment === []) {
      return NULL;
    }

    $buf = '';
    foreach ($this->comment as $comment) {
      if (substr($comment, 0, 2) === '//') {
        $buf .= $prefix . $comment;
      }
      elseif (substr($comment, 0, 3) === '/**') {
        $buf .= $prefix . rtrim(str_replace("\n", "\n{$prefix}", $comment), " ");
      }
      elseif (substr($comment, 0, 2) === '/*') {
        $buf .= $prefix . $comment;
      }
      elseif ($comment === '') {
        // ignore
      }
      else {
        throw new \LogicException("Malformed comment");
      }
    }

    return $buf;
  }

}
