# PhpArrayDocument

This is a parser/printer for a subset of PHP focused on data. (*It serves a role similar to a full-service YAML library - except with `*.php` data-files.*) Key features:

* Allows `array` values and `scalar` values (`string`, `int`, `bool`, etc).
* Allows comments for the overall document and for individual items in the tree.
* Allows *tagged-values* (a.k.a. *factory-functions*) which look like *global or static method-calls*.
* Allows deferred construction (`fn() => [...data...]`)
* Prohibits objects, loops, math, concatenation, includes, custom functions, etc.

## Examples: Data documents

A basic document looks like this:

```php
<?php
return [
  // First item is a string.
  'key_1' => 'value_1',

  // Second item is an array of floats.
  'key_2' => [2.1, 2.2, 2.3],

  // Third item is an array.
  'key_3' => [
    'part_a' => 'Apple',
    'part_b' => 'Banana',
  ]
];
```

This next example is similar, but it adds a "tagged value" or "factory function" called `MyHelper::translate()`:

```php
<?php
use MyHelper as H;

return [
  'id' => 1234,
  'name' => 'greeter',
  'label' => H::translate('Hello World'),
];
```

Note that `H::translate('Hello world')` takes _exactly one parameter_. It only supports a subset of PHP function-calls -- i.e. with one parameter; with global-function or static-method. (`H::translate` is less like an open-ended *function-call* and more like a *tag* that describes `Hello world`.)

<!--

> If you were writing similar document with XML/DOM, it  might look like:
>
> ```xml
> <document xmlns:h="MyHelper">
>   <array>
>     <array-item>
>       <key>name</key>
>       <value>greeter</value>
>     </array-item>
>     <array-item>
>       <key>label</key>
>       <value h:translate>Hello World</value>
>     </array-item>
>   </array>
> </document>
> ```

-->

The same concept can be applied to generate objects/records, as long as there is _exactly one parameter_:

```php
use MyHelper as H;

return H::record([
  'id' => 1234,
  'name' => 'greeter',
  'label' => H::translate('Hello World'),
]);
```

Value resolution may be defererd, as in:

```php
use MyHelper as H;

return H::record([
  'id' => 1234,
  'name' => 'greeter',
  'label' => fn() => H::translate('Hello World'),
]);
```

## Examples: File manipulation

Generate a new `*.php` data file. Populate it with `importData($array)`:

```php
$doc = PhpArrayDocument::create();
$doc->getRoot()->importData([
  'id' => 1234,
  'name' => 'greeting',
  'label' => ScalarNode::create('Hello World')->setFactory('E::ts'),
]);

$file = 'my.data.php';
file_put_contents($file, (new Printer())->print($doc));
```

Read an existing file. Update individual items in the parse-tree. Save the updated file.

```php
$file = 'my.data.php';
$doc = (new Parser())->parse(file_get_contents($file));

$root = $doc->getRoot();
$root['id'] = ScalarNode::create(100);
$root['name'] = ScalarNode::create('greeting');
$root['label'] = ScalarNode::create('Hello World')->setFactory('E::ts');

file_put_contents($file, (new Printer())->print($doc));
```

Update an existing file. Do a global search (`walkNodes()`). Find references to `OldHelper::method` and replace them with `NewHelper::method`.

```php
$file = 'my.data.php';
$doc = (new Parser())->parse(file_get_contents($file));

foreach ($doc->getRoot()->walkNodes() as $node) {
  if ($node->getFactory() === 'OldHelper::method') {
    $node->setFactory('NewHelper::method');
  }
}

file_put_contents($file, (new Printer())->print($doc));
```

## Cheatsheet

Some commands to help with debugging:

```bash
## Tokenize an improvised PHP snippet
echo '<?php return [1,2,3];' | ./scripts/tokenize.php

## Tokenize a PHP file
cat examples/simple-array-7.4.php | ./scripts/tokenize.php | less

## Parse an improvised PHP snippet
echo '<?php return [1,2,3];' | ./scripts/parse.php

## Parse a PHP file
cat examples/simple-array-7.4.php | ./scripts/parse.php | less
```

## Model

* Basic Concepts
    * __Substance__: Each "_PHP array document_" contains a tree of `array`s and `scalar`s.
    * __Metadata__: Individual values may be annotated with comments and/or factory-functions (such as `ts(...)`).
    * __Evaluation__: If you `include` or `require` the PHP document directly, you will literally get `array`s and `scalar`s.
    * __Read/Write__: If you need to programmatically inspect or update the content, then the `PhpArrayDocument` aims to help.
* Verbs
    * __Parse__: Read the PHP document as an instance of `PhpArrayDocument`
    * __Print__: Render `PhpArrayDocument` as a string (`<?php return [...]`)
    * __Import Data__: Add basic PHP array data to a `PhpArrayDocument` (*without any metadata/comments/factory-functions*)
    * __Export Data__: Grab the PHP (*discarding any metadata/comments/factory-functions*)
    * __Walk Nodes__: Visit all the nodes in the tree. Useful for general filtering/searching/replacing.
* Classes
    * Data-Focused Classes
        * `PhpArrayDocument`: This captures the overall `*.php` file, including any top-level elements (`use` or docblocks) and the root `array`.
        * `ArrayNode` (extends `BaseNode`): An element in the tree that corresponds to `array()`
        * `ArrayItemNode` (extends `BaseNode`): A key-value pair that exists within an `array()`
        * `ScalarNode` (extends `BaseNode`): An atomic value that lives inside an array.
    * Functionality-Focused Classes
        * `Parser`: Take a raw `string`. Generate a `PhpArrayDocument`.
        * `Printer`: Take a `PhpArrayDocument`. Generate a string
