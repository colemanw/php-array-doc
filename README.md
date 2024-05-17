# PhpArrayDocument

This is a parser/encoder for a subset of PHP focused on data. A document
looks like:

```php
<?php
return [
  'key_1' => 'value_1',
  'key_2' => [2.1, 2.2, 2.3],
  'key_3' => [
    'part_a' => 'Apple',
    'part_b' => 'Banana',
  ]
];
```

Additionally, you may declare tagged/factory values:

```php
<?php
use MyHelper as H;
return [
  'name' => 'greeter',
  'label' => H::translate('Hello World'),
];
```

Or even:

```php
use MyHelper as H;
return H::record([
  'name' => 'greeter',
  'label' => H::translate('Hello World'),
]);
```

## Example

```php
# Update a file
use PhpArrayDocument\Parser;
$file = 'my-example.data.php';
$doc = (new Parser())->parse(file_get_contents($file));

$doc->root['label']->scalar = 'Hello World';
$doc->root['label']->factory = 'E::ts';

file_put_contents($file, (new Printer())->print($doc));
```

```php
# Rename a factory method
$doc = (new Parser())->parse(file_get_contents($file));
foreach ($doc->root->walkNodes() as $node) {
  if ($node->factory === 'OldHelper::method') {
    $node->factory = 'NewHelper::method';
  }
} 
```

## Cheatsheet

Some comands to help with debugging:

```bash
## Parse a PHP file
cat examples/simple-array.php | ./scripts/parse.php | less

## Parse an improvised PHP snippet
echo '<?php return [1,2,3];' | ./scripts/parse.php

## Tokenize a PHP file
cat examples/simple-array.php | ./scripts/tokenize.php | less

## Tokenize an improvised PHP snippet
echo '<?php return [1,2,3];' | ./scripts/tokenize.php
```
 