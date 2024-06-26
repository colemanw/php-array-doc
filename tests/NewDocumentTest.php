<?php

namespace PhpArrayDocument;

class NewDocumentTest extends \PHPUnit\Framework\TestCase {

  public function testCreate() {
    $example = version_compare(PHP_VERSION, '7.4', '<') ? 'setting-definition-7.3.php' : 'setting-definition-7.4.php';

    $doc = new PhpArrayDocument();
    $doc->use['SettingsDefinition'] = 'Civi\Core\SettingsDefinition';
    $doc->use['E'] = 'CRM_Mosaico_ExtensionUtil';
    $doc->setOuterComments([
      "// About this doc\n",
      "// It has content.\n",
      "/" . '* Lots of content *' . "/\n",
    ]);
    $doc->root = ArrayNode::create();
    $doc->root->factory = 'SettingsDefinition::create';
    $doc->root['name'] = ScalarNode::create('hello')
      ->setOuterComments(["/* The name is important */\n"]);
    $doc->root['label'] = ScalarNode::create('Hello World!')
      ->setFactory('E::ts')
      ->setOuterComments(["// The label is shown to somebody\n"]);
    $doc->root['active'] = ScalarNode::create(TRUE);
    $doc->root['default'] = ScalarNode::create('ok');
    $doc->root['default']->setInnerComments("The default is something\nMade with one or two lines\nOr three.\n");

    $doc->root['html'] = ArrayNode::create();
    $doc->root['html']['bold'] = ScalarNode::create(TRUE)
      ->setOuterComments([
        "// To boldly go\n",
        "// where no font face has gone before\n",
      ]);
    $doc->root['details'] = ArrayNode::create();
    $doc->root['details']->deferred = TRUE;
    $doc->root['details']['alskjdf asdf'] = ScalarNode::create(123);

    $printer = new Printer();
    $actual = $printer->print($doc);

    $file = dirname(__DIR__) . '/examples/' . $example;
    $expected = file_get_contents($file);
    $this->assertEquals($expected, $actual);
  }

}
