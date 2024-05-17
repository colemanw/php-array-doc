<?php
namespace PhpArrayDocument;

class ScalarValueNode extends ValueNode {

  public $scalar;

  /**
   * @param $scalar
   */
  public function __construct($scalar) {
    $this->scalar = $scalar;
  }

}
