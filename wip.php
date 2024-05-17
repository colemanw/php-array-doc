<?php
namespace PhpArrayDocument;

if (!defined('T_FN')) {
  define('T_FN', '_polyfill_tn');
}

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

class ScalarValueNode extends ValueNode {

  public $scalar;

  /**
   * @param $scalar
   */
  public function __construct($scalar) {
    $this->scalar = $scalar;
  }

}

class ArrayValueNode extends ValueNode implements \ArrayAccess, \IteratorAggregate, \Countable {

  /** @var ArrayItem[] */
  public $items = [];

  public function __construct($items = []) {
    $this->items = $items;
  }

  public function walkNodes(string $type = ValueNode::class) {
    yield from parent::walkNodes($type);
    foreach ($this->items as $arrayItem) {
      yield from $arrayItem->value->walkNodes($type);
    }
  }

  public function getIterator() {
    return new ArrayIterator($this->items);
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
    if (!($value instanceof ValueNode)) {
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

class ArrayItem {

  public ?string $comment = NULL;

  public $key;

  public ValueNode $value;

  public function __construct($key, ValueNode $value) {
    $this->key = $key;
    $this->value = $value;
  }

}

class Parser {

  private $tokens;

  private $pos = 0;

  private $currentToken;

  // When using xdebug, it's handy to see token with symbolic ID (instead of version-dependent #s).
  private $currentTokenId;

  public function parse($code) {
    $this->tokens = token_get_all($code);
    $this->tokens = array_map(function ($token) {
      if (!is_array($token)) {
        // ok
      }
      elseif ($token[0] === T_STRING && $token[1] === 'fn') {
        $token[0] = T_FN;
      }
      return $token;
    }, $this->tokens);
    $this->pos = 0;
    $this->nextToken();
    return $this->parseDocument();
  }

  private function parseDocument() {
    $document = new PhpArrayDocument();

    $this->expect(T_OPEN_TAG)->skipWhitespace();

    while ($this->currentToken[0] == T_USE) {
      foreach ($this->parseUse() as $alias => $class) {
        $document->use[$alias] = $class;
        $this->skipWhitespace();
      }
    }

    while ($this->currentToken[0] == T_COMMENT) {
      $document->dataComments[] = $this->currentToken[1];
      $this->nextToken()->skipWhitespace();
    }

    $this->expect(T_RETURN)->skipWhitespace();

    $document->data = $this->parseValue();
    $this->skipWhitespace();

    $this->expect(';')->skipWhitespace();

    return $document;
  }

  private function parseUse() {
    $this->expect(T_USE)->skipWhitespace();

    $className = $this->parseClassName();
    $this->skipWhitespace();

    if ($this->currentToken[0] == T_AS) {
      $this->nextToken()->skipWhitespace();
      if ($this->isToken(T_STRING)) {
        $alias = $this->currentToken[1];
        $this->nextToken()->skipWhitespace();
      }
      else {
        $this->unexpectedToken();
      }
    }
    else {
      $parts = explode('\\', $className);
      $alias = array_pop($parts);
    }

    $this->expect(';')->skipWhitespace();

    return [$alias => $className];
  }

  private function parseValue() {
    if ($this->isScalar($this->currentToken)) {
      return new ScalarValueNode($this->parseScalar());
    }
    elseif ($this->isArray($this->currentToken)) {
      return new ArrayValueNode($this->parseArrayItems());
    }
    elseif ($this->isToken(T_FN)) {
      $this->expectSequence([T_FN, "(", ")", T_DOUBLE_ARROW]);
      $result = $this->parseValue();
      $result->deferred = TRUE;
      return $result;
    }
    elseif ($this->isToken(T_FUNCTION)) {
      $this->expectSequence([T_FUNCTION, "(", ")", "{", T_RETURN]);
      $result = $this->parseValue();
      $result->deferred = TRUE;
      $this->expectSequence([';', '}']);
      return $result;
    }
    elseif ($this->isToken(T_STRING)) {
      $factory = $this->parseFactory();
      $this->expect('(')->skipWhitespace();
      $result = $this->parseValue();
      $this->expect(')')->skipWhitespace();
      if ($result->factory !== NULL) {
        throw new \Exception('Cannot use multiple factories: ' . json_encode([$result->factory, $factory]));
      }
      $result->factory = $factory;
      return $result;
    }

    $this->unexpectedToken();
  }

  private function parseArrayItems() {
    $result = [];
    $num = 0;

    if ($this->isToken(T_ARRAY)) {
      $this->nextToken()->skipWhitespace();
      $openClose = ['(', ')'];
    }
    else {
      $openClose = ['[', ']'];
    }

    $this->expect($openClose[0]);
    while (!$this->isToken($openClose[1])) {
      $arrayItem = $this->parseArrayItem();
      if ($arrayItem->key === NULL) {
        $arrayItem->key = $num++;
      }
      $result[$arrayItem->key] = $arrayItem;
      $this->skipWhitespace();

      if ($this->isToken(',')) {
      //
      // }
      // if ($this->currentToken == ',') {
        $this->nextToken()->skipWhitespace();
      }
      elseif (!$this->isToken($openClose[1])) {
        $this->unexpectedToken();
      }
    }
    $this->expect($openClose[1]);

    return $result;
  }

  private function parseArrayItem() {
    $this->skipWhitespace();

    $comments = [];
    while ($this->isToken([T_COMMENT, T_DOC_COMMENT, T_WHITESPACE])) {
      $comments[] = ltrim($this->currentToken[1], ' \t');
      $this->nextToken();
    }

    if ($this->isScalar($this->currentToken)) {
      $first = $this->parseScalar();
      $this->skipWhitespace();
      if ($this->isToken(T_DOUBLE_ARROW)) {
        $this->nextToken()->skipWhitespace();
        $key = $first;
        $value = $this->parseValue();
      }
      else {
        $key = NULL;
        $value = new ScalarValueNode($first);
      }
    }
    else {
      $key = NULL;
      $value = $this->parseValue();
    }
    $this->skipWhitespace();

    $item = new ArrayItem($key, $value);
    if (!empty($comments)) {
      $item->comment = implode("", $comments);
    }
    return $item;
  }

  private function parseFactory() {
    $symbol = '';
    while ($this->isToken([T_STRING, T_NS_SEPARATOR, T_DOUBLE_COLON])) {
      $symbol .= $this->currentToken[1];
      $this->nextToken()->skipWhitespace();
    }
    return $symbol;
  }

  private function parseScalar() {
    if ($this->isToken(T_LNUMBER)) {
      $result = (int) $this->currentToken[1];
      $this->nextToken()->skipWhitespace();
    }
    elseif ($this->isToken(T_DNUMBER)) {
      $result = (double) $this->currentToken[1];
      $this->nextToken()->skipWhitespace();
    }
    elseif ($this->isToken(T_CONSTANT_ENCAPSED_STRING)) {
      $result = substr($this->currentToken[1], 1, -1);
      $this->nextToken()->skipWhitespace();
    }
    elseif ($this->isToken(T_STRING)) {
      $constants = ['FALSE' => FALSE, 'TRUE' => TRUE, 'NULL' => NULL];
      $value = strtoupper($this->currentToken[1]);
      if (isset($constants[$value])) {
        $result = $constants[$value];
        $this->nextToken()->skipWhitespace();
      }
      else {
        $this->unexpectedToken();
      }
    }
    else {
      $this->unexpectedToken();
    }
    return $result;
  }

  private function parseClassName() {
    $className = '';

    while ($this->currentToken[0] == T_NS_SEPARATOR || $this->currentToken[0] == T_STRING) {
      $className .= $this->currentToken[1];
      $this->nextToken();
    }

    return $className;
  }

  private function isScalar($token) {
    if (in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER])) {
      return TRUE;
    }
    if ($token[0] == T_STRING) {
      return in_array(strtolower($token[1]), ['true', 'false', 'null']);
    }
    return FALSE;
  }

  private function isArray($token) {
    return $token === '[' || ($token[0] ?? NULL) == T_ARRAY;
  }

  private function isToken($charOrTypeOptions, $token = NULL): bool {
    $charOrTypeOptions = (array) $charOrTypeOptions;
    $token = $token ?: $this->currentToken;
    foreach ($charOrTypeOptions as $charOrType) {
      if ($token === $charOrType) {
        return TRUE;
      }
      if (is_array($token) && $this->currentToken[0] == $charOrType) {
        return TRUE;
      }
    }
    return FALSE;
  }

  private function nextToken() {
    $this->currentToken = $this->tokens[$this->pos++] ?? [NULL, NULL];
    $this->currentTokenId = is_string($this->currentToken) ? $this->currentToken : token_name($this->currentToken[0]);
    return $this;
  }

  private function expect($token) {
    if ($this->currentToken[0] == $token || $this->currentToken == $token) {
      $this->nextToken();
    }
    else {
      $this->unexpectedToken();
    }
    return $this;
  }

  private function expectSequence(array $tokens, bool $skipWhitespace = TRUE) {
    foreach ($tokens as $token) {
      $this->expect($token);
      if ($skipWhitespace) { $this->skipWhitespace(); }
    }
    return $this;
  }

  private function skipWhitespace() {
    while ($this->currentToken[0] == T_WHITESPACE) {
      $this->nextToken();
    }
    return $this;
  }

  private function unexpectedToken() {
    $token = $this->currentToken;
    if (is_array($token)) {
      $token[0] = token_name($token[0]);
    }
    throw new \Exception('Unexpected token: ' . json_encode($token));
  }

}

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
    $buf[] = 'return ' . $this->printNode($document->data) . ";\n";
    return implode("\n", $buf);
  }

  private function printNode(ValueNode $node, int $indent = 0): string {
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

    if ($node instanceof ScalarValueNode) {
      $constants = [FALSE => 'FALSE', TRUE => 'TRUE', NULL => 'NULL'];
      $value = $constants[$node->scalar] ?? var_export($node->scalar, TRUE);
      return $prefix . $value . $suffix;
    }
    elseif ($node instanceof ArrayValueNode) {
      $isSeq = array_keys($node->items) === range(0, count($node->items) - 1);
      $isShort = array_reduce($node->items, function ($carry, $item) {
          return $carry && ($item->value instanceof ScalarValueNode) && empty($item->comment) && strlen($item->value->scalar) < 15;
      }, count($node->items) < 5);

      $parts = [];
      $parentIndent = str_repeat(' ', $indent * 2);
      $childIndent = str_repeat(' ', (1 + $indent) * 2);
      foreach ($node->items as $item) {
        $part = '';
        if ($item->comment) {
          $part .= $childIndent;
          $part .= rtrim(str_replace("\n", "\n$childIndent", $item->comment), " ");
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

// Example usage:
$code = <<<'CODE'
<?php
use Civi\Core\SettingsDefinition;
use CRM_Mosaico_ExtensionUtil as E;

// About this doc
return SettingsDefinition::create([
  /* The name is important */
  'name' => 'hello',
  // The label is shown to somebody
  'label' => E::ts('Hello World!'),
  'active' => TRUE,
  'html' => [
    // To boldly go
    'bold' => TRUE,
  ],
  'details' => fn() => [
    'alskjdf asdf' => 123,
  ]
]);
CODE;

// $code = <<<'CODE'
// <?php
// use CRM_Mosaico_ExtensionUtil as E;
//
// // About this doc
// return MyStuff::go([
//   'details' => fn() => [
//     'alskjdf asdf' => XX::yy(123),
//   ]
//   'older' => function () { return [
//     'alskjdf asdf' => 123,
//   ]; }
// ]);
// CODE;

$parser = new Parser();
$document = $parser->parse($code);
// $document->dereferenceClassAliases();
print_r($document);
print_r([
  'value of label is' => $document->data['label'],
  'value of html.bold is' => $document->data['html']['bold'],
]);

echo "\n";
echo (new Printer())->print($document);
