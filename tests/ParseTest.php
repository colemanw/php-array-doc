<?php

use PhpArrayDocument\ArrayNode;
use PhpArrayDocument\Parser;
use PhpArrayDocument\ScalarNode;

class ParseTest extends \PHPUnit\Framework\TestCase {

  public function testSettingDefinition() {
    $example = version_compare(PHP_VERSION, '7.4', '<') ? 'setting-definition-7.3.php' : 'setting-definition-7.4.php';

    $file = dirname(__DIR__) . '/examples/' . $example;

    $input = file_get_contents($file);

    $parser = new Parser();
    $document = $parser->parse($input);

    $this->assertEquals('Civi\Core\SettingsDefinition', $document->use['SettingsDefinition']);
    $this->assertEquals('CRM_Mosaico_ExtensionUtil', $document->use['E']);
    $this->assertEquals("// About this doc\n", $document->getOuterComments()[0]);
    $this->assertEquals("// It has content.\n", $document->getOuterComments()[1]);
    $this->assertEquals("/" . '* Lots of content *' . "/\n", $document->getOuterComments()[2]);

    $this->assertArrayNode($document->root, FALSE, 'SettingsDefinition::create');
    $this->assertScalarNode($document->root['name'], 'hello', FALSE, NULL, "The name is important\n");
    $this->assertScalarNode($document->root['label'], 'Hello World!', FALSE, 'E::ts', "The label is shown to somebody\n");
    $this->assertScalarNode($document->root['active'], TRUE, FALSE, NULL, NULL);
    $this->assertScalarNode($document->root['default'], 'ok', FALSE, NULL, "The default is something\nMade with one or two lines\nOr three.\n");
    $this->assertArrayNode($document->root['html'], FALSE, NULL);
    $this->assertScalarNode($document->root['html']['bold'], TRUE, FALSE, NULL, "To boldly go\nwhere no font face has gone before\n");
    $this->assertArrayNode($document->root['details'], TRUE, NULL);
  }

  protected function assertScalarNode($node, $value, bool $deferred, ?string $factory, ?string $cleanComment) {
    $this->assertInstanceOf(ScalarNode::class, $node);
    $this->assertEquals($value, $node->getScalar());
    $this->assertEquals($deferred, $node->deferred);
    $this->assertEquals($factory, $node->factory);
    $this->assertEquals($cleanComment, $node->getInnerComments());
  }

  protected function assertArrayNode($node, bool $deferred, ?string $factory) {
    $this->assertInstanceOf(ArrayNode::class, $node);
    $this->assertEquals($deferred, $node->deferred, 'Check value of $node->deferred');
    $this->assertEquals($factory, $node->factory, 'Check value of $node->factory');
  }

}
