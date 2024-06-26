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

    $this->assertEquals('Civi\Core\SettingsDefinition', $document->getUses()['SettingsDefinition']);
    $this->assertEquals('CRM_Mosaico_ExtensionUtil', $document->getUses()['E']);
    $this->assertEquals("// About this doc\n", $document->getOuterComments()[0]);
    $this->assertEquals("// It has content.\n", $document->getOuterComments()[1]);
    $this->assertEquals("/" . '* Lots of content *' . "/\n", $document->getOuterComments()[2]);

    $this->assertArrayNode($document->getRoot(), FALSE, 'SettingsDefinition::create');
    $this->assertScalarNode($document->getRoot()['name'], 'hello', FALSE, NULL, "The name is important\n");
    $this->assertScalarNode($document->getRoot()['label'], 'Hello World!', FALSE, 'E::ts', "The label is shown to somebody\n");
    $this->assertScalarNode($document->getRoot()['active'], TRUE, FALSE, NULL, NULL);
    $this->assertScalarNode($document->getRoot()['default'], 'ok', FALSE, NULL, "The default is something\nMade with one or two lines\nOr three.\n");
    $this->assertArrayNode($document->getRoot()['html'], FALSE, NULL);
    $this->assertScalarNode($document->getRoot()['html']['bold'], TRUE, FALSE, NULL, "To boldly go\nwhere no font face has gone before\n");
    $this->assertArrayNode($document->getRoot()['details'], TRUE, NULL);
  }

  protected function assertScalarNode($node, $value, bool $deferred, ?string $factory, ?string $cleanComment) {
    $this->assertInstanceOf(ScalarNode::class, $node);
    $this->assertEquals($value, $node->getScalar());
    $this->assertEquals($deferred, $node->isDeferred());
    $this->assertEquals($factory, $node->getFactory());
    $this->assertEquals($cleanComment, $node->getInnerComments());
  }

  protected function assertArrayNode($node, bool $deferred, ?string $factory) {
    $this->assertInstanceOf(ArrayNode::class, $node);
    $this->assertEquals($deferred, $node->isDeferred(), 'Check value of $node->deferred');
    $this->assertEquals($factory, $node->getFactory(), 'Check value of $node->factory');
  }

}
