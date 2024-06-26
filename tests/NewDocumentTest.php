<?php

namespace PhpArrayDocument;

class NewDocumentTest extends \PHPUnit\Framework\TestCase {

  public function testCreate() {
    $example = version_compare(PHP_VERSION, '7.4', '<') ? 'setting-definition-7.3.php' : 'setting-definition-7.4.php';

    $doc = new PhpArrayDocument();
    $doc->use['SettingsDefinition'] = 'Civi\Core\SettingsDefinition';
    $doc->use['E'] = 'CRM_Mosaico_ExtensionUtil';
    $doc->dataComments = [
      "// About this doc\n",
      "// It has content.\n",
      "/" . '* Lots of content *' . "/\n",
    ];
    $doc->root = new ArrayNode();
    $doc->root->factory = 'SettingsDefinition::create';
    $doc->root['name'] = new ScalarNode('hello');
    $doc->root['name']->comment[] = "/* The name is important */\n";
    $doc->root['label'] = new ScalarNode('Hello World!');
    $doc->root['label']->factory = 'E::ts';
    $doc->root['label']->comment[] = "// The label is shown to somebody\n";
    $doc->root['active'] = new ScalarNode(TRUE);
    $doc->root['default'] = new ScalarNode('ok');
    $doc->root['default']->setCleanComment("The default is something\nMade with one or two lines\nOr three.\n");

    $doc->root['html'] = new ArrayNode();
    $doc->root['html']['bold'] = new ScalarNode(TRUE);
    $doc->root['html']['bold']->comment[] = "// To boldly go\n";
    $doc->root['html']['bold']->comment[] = "// where no font face has gone before\n";

    $doc->root['details'] = new ArrayNode();
    $doc->root['details']->deferred = TRUE;
    $doc->root['details']['alskjdf asdf'] = new ScalarNode(123);

    $printer = new Printer();
    $actual = $printer->print($doc);

    $file = dirname(__DIR__) . '/examples/' . $example;
    $expected = file_get_contents($file);
    $this->assertEquals($expected, $actual);
  }

}
