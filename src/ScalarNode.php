<?php
namespace PhpArrayDocument;

class ScalarNode extends BaseNode {

  /**
   * @var scalar
   */
  public $scalar;

  /**
   * @param scalar $scalar
   */
  public function __construct($scalar) {
    $this->scalar = $scalar;
  }

}
