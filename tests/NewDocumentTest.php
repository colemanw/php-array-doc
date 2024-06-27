<?php

namespace PhpArrayDocument;

class NewDocumentTest extends \PHPUnit\Framework\TestCase {

  public function testCreate() {
    $example = version_compare(PHP_VERSION, '7.4', '<') ? 'setting-definition-7.3.php' : 'setting-definition-7.4.php';

    $doc = PhpArrayDocument::create()
      ->addUse('Civi\Core\SettingsDefinition')
      ->addUse('CRM_Mosaico_ExtensionUtil', 'E')
      ->setOuterComments([
        "// About this doc\n",
        "// It has content.\n",
        "/" . '* Lots of content *' . "/\n",
      ]);
    $doc->getRoot()->setFactory('SettingsDefinition::create');
    $doc->getRoot()['name'] = ScalarNode::create('hello')
      ->setOuterComments(["/* The name is important */\n"]);
    $doc->getRoot()['label'] = ScalarNode::create('Hello World!')
      ->setFactory('E::ts')
      ->setOuterComments(["// The label is shown to somebody\n"]);
    $doc->getRoot()['active'] = ScalarNode::create(TRUE);
    $doc->getRoot()['default'] = ScalarNode::create('ok');
    $doc->getRoot()['default']->setInnerComments("The default is something\nMade with one or two lines\nOr three.\n");

    $doc->getRoot()['html'] = ArrayNode::create();
    $doc->getRoot()['html']['bold'] = ScalarNode::create(TRUE)
      ->setOuterComments([
        "// To boldly go\n",
        "// where no font face has gone before\n",
      ]);
    $doc->getRoot()['details'] = ArrayNode::create();
    $doc->getRoot()['details']->setDeferred(TRUE);
    $doc->getRoot()['details']['alskjdf asdf'] = ScalarNode::create(123);

    $printer = new Printer();
    $actual = $printer->print($doc);
    $file = dirname(__DIR__) . '/examples/' . $example;
    $expected = file_get_contents($file);
    $this->assertEquals($expected, $actual);
  }

  public function testCreateMergeData() {
    $example = version_compare(PHP_VERSION, '7.4', '<') ? 'simple-array-7.3.php' : 'simple-array-7.4.php';

    $basicData = [
      'name' => 'hello',
      'label' => 'Hello World!',
      'active' => TRUE,
      'html' => [
        'bold' => TRUE,
      ],
      'details' => [
        'zero' => 0,
        'zero-ish' => '0',
        'one' => 1,
        'one-ish' => '1',
        'null' => NULL,
        'null-ish' => 'NULL',
        'null-ishish' => 'null',
        'true' => TRUE,
        'true-ish' => 'TRUE',
        'true-ishish' => 'true',
        'false' => FALSE,
        'false-ish' => 'FALSE',
        'false-ishish' => 'false',
        'float' => 12.3,
        'float-ish' => '45.6',
        'int' => 123,
        'int-ish' => '123',
        'fun\\ny \'b"u`siness' => 'a\\b\'c"d`e',
        'array-empty' => [],
        'array-short-seq' => [3, 2, 1],
        'array-shorter-seq' => [3],
        'array-long-seq' => [
          '123456789 123456789 123456789 123456789 123456789 123456789 ',
        ],
        'array-kv' => [
          'k' => 'v',
        ],
      ],
    ];

    $doc = PhpArrayDocument::create();
    $doc->getRoot()->importData($basicData);
    $doc->getRoot()['details']->setDeferred(TRUE);

    $printer = new Printer();
    $actual = $printer->print($doc);
    $file = dirname(__DIR__) . '/examples/' . $example;
    $expected = file_get_contents($file);
    $this->assertEquals($expected, $actual);
  }

}
