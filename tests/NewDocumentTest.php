<?php

namespace PhpArrayDocument;

class NewDocumentTest extends \PHPUnit\Framework\TestCase {

  public function testCreate() {
    $example = version_compare(PHP_VERSION, '7.4', '<') ? 'setting-definition-7.3.php' : 'setting-definition-7.4.php';

    $doc = PhpArrayDocument::create();
    $doc->use['SettingsDefinition'] = 'Civi\Core\SettingsDefinition';
    $doc->use['E'] = 'CRM_Mosaico_ExtensionUtil';
    $doc->setOuterComments([
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
        'alskjdf asdf' => 123,
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
