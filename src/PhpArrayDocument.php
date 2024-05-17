<?php
namespace PhpArrayDocument;

class PhpArrayDocument {

  public array $use = [];

  public array $dataComments = [];

  public ?ValueNode $data = NULL;

  /**
   * Find expressions like "E::ts()" and turn them into "CRM_Foo_ExtensionInfo::ts()".
   *
   * @return $this
   */
  public function dereferenceClassAliases() {
    foreach ($this->data->walkNodes(ScalarValueNode::class) as $node) {
      if (!empty($node->factory)) {
        $parts = explode('::', $node->factory, 2);
        if (count($parts) === 2 && isset($this->use[$parts[0]])) {
          $node->factory = $this->use[$parts[0]] . '::' . $parts[1];
        }
      }
    }
    return $this;
  }

  /**
   * Find expressions like "CRM_Foo_ExtensionInfo::ts()" and turn them into "E::ts()" .
   *
   * @return $this
   */
  public function useClassAliases() {
    foreach ($this->data->walkNodes(ScalarValueNode::class) as $node) {
      if (!empty($node->factory)) {
        $parts = explode('::', $node->factory, 2);
        if (count($parts) === 2) {
          $alias = array_search($parts[0], $this->use);
          if ($alias) {
            $node->factory = $alias . '::' . $parts[1];
          }
        }
      }
    }
    return $this;
  }

}
