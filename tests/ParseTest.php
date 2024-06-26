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
    $this->assertEquals("// About this doc\n", $document->dataComments[0]);
    $this->assertEquals("// It has content.\n", $document->dataComments[1]);
    $this->assertEquals("/" . '* Lots of content *' . "/\n", $document->dataComments[2]);

    $this->assertArrayNode($document->root, FALSE, 'SettingsDefinition::create');
    $this->assertScalarNode($document->root['name'], 'hello', FALSE, NULL);
    $this->assertScalarNode($document->root['label'], 'Hello World!', FALSE, 'E::ts');
    $this->assertArrayNode($document->root['html'], FALSE, NULL);
    $this->assertScalarNode($document->root['html']['bold'], TRUE, FALSE, NULL);
    $this->assertArrayNode($document->root['details'], TRUE, NULL);
  }

  protected function assertScalarNode($node, $value, bool $deferred, ?string $factory) {
    $this->assertInstanceOf(ScalarNode::class, $node);
    $this->assertEquals($value, $node->scalar);
    $this->assertEquals($deferred, $node->deferred);
    $this->assertEquals($factory, $node->factory);
  }

  protected function assertArrayNode($node, bool $deferred, ?string $factory) {
    $this->assertInstanceOf(ArrayNode::class, $node);
    $this->assertEquals($deferred, $node->deferred, 'Check value of $node->deferred');
    $this->assertEquals($factory, $node->factory, 'Check value of $node->factory');
  }

}
