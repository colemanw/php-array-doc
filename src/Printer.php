<?php

namespace PhpArrayDocument;

class Printer {

  private $useFn;

  public function __construct() {
    $this->useFn = version_compare(PHP_VERSION, '7.4.0', '<');
    // $this->useFn = TRUE;
  }

  public function print(PhpArrayDocument $document): string {
    $buf[] = '<' . "?php";
    foreach ($document->use as $alias => $class) {
      $defaultAlias = array_reverse(explode("\\", $class))[0];
      if ($alias === $defaultAlias) {
        $buf[] = sprintf('use %s;', $class);
      }
      else {
        $buf[] = sprintf('use %s as %s;', $class, $alias);
      }
    }
    if ($document->dataComments) {
      $buf[] = '';
      $buf[] = rtrim(implode("", $document->dataComments), "\n");
    }
    $buf[] = 'return ' . $this->printNode($document->root) . ";\n";
    return implode("\n", $buf);
  }

  private function printNode(BaseNode $node, int $indent = 0): string {
    $prefix = $suffix = '';
    if ($node->factory) {
      $prefix .= $node->factory . '(';
      $suffix = "$suffix)";
    }
    if ($node->deferred) {
      if ($this->useFn) {
        $prefix .= 'function() { return ';
        $suffix = "; }" . $suffix;
      }
      else {
        $prefix .= 'fn() => ';
      }
    }

    if ($node instanceof ScalarNode) {
      $constants = [FALSE => 'FALSE', TRUE => 'TRUE', NULL => 'NULL'];
      $value = $constants[$node->scalar] ?? var_export($node->scalar, TRUE);
      return $prefix . $value . $suffix;
    }
    elseif ($node instanceof ArrayNode) {
      $isSeq = array_column($node->items, 'key') === range(0, count($node->items) - 1);
      $isShort = array_reduce($node->items, function ($carry, $item) {
        return $carry && ($item->value instanceof ScalarNode) && empty($item->value->comment) && strlen($item->value->scalar) < 15;
      }, count($node->items) < 5);

      $parts = [];
      $parentIndent = str_repeat(' ', $indent * 2);
      $childIndent = str_repeat(' ', (1 + $indent) * 2);
      foreach ($node->items as $item) {
        $part = '';
        if ($item->value->comment) {
          $part .= $childIndent;
          $part .= rtrim(str_replace("\n", "\n$childIndent", $item->value->comment), " ");
        }
        if (!($isSeq && $isShort)) {
          $part .= $childIndent;
        }
        if (!$isSeq) {
          $part .= (var_export($item->key, TRUE) . ' => ');
        }
        $part .= $this->printNode($item->value, 1 + $indent);
        $parts[] = $part;
      }

      if ($isSeq && $isShort) {
        return $prefix . '[' . implode(', ', $parts) . ']' . $suffix;
      }
      else {
        return $prefix . sprintf("[\n%s,\n%s]", implode(",\n", $parts), $parentIndent) . $suffix;
      }
    }
    else {
      throw new \Exception("Unrecognized node type: " . get_class($node));
    }
  }

}
