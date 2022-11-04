<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\tamper\SourceDefinitionInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Base class for tamper plugin tests.
 */
abstract class TamperPluginTestBase extends UnitTestCase {

  /**
   * The tamper plugin under test.
   *
   * @var \Drupal\tamper\TamperInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->plugin = $this->instantiatePlugin();
    $this->plugin->setStringTranslation($this->getMock(TranslationInterface::class));

    parent::setUp();
  }

  /**
   * Instantiates a plugin.
   *
   * @return \Drupal\tamper\TamperInterface
   *   A tamper plugin.
   */
  abstract protected function instantiatePlugin();

  /**
   * Returns a mocked source definition.
   *
   * @return \Drupal\tamper\SourceDefinitionInterface
   *   A source definition.
   */
  protected function getMockSourceDefinition() {
    $mock = $this->getMock(SourceDefinitionInterface::class);
    $mock->expects($this->any())
      ->method('getList')
      ->willReturn(['foo', 'bar']);
    return $mock;
  }

  /**
   * @covers ::getPluginId
   */
  public function testGetPluginId() {
    $this->assertInternalType('string', $this->plugin->getPluginId());
  }

  /**
   * @covers ::getPluginDefinition
   */
  public function testGetPluginDefinition() {
    $this->assertInternalType('array', $this->plugin->getPluginDefinition());
  }

  /**
   * @covers ::getConfiguration
   */
  public function testGetConfiguration() {
    $this->assertInternalType('array', $this->plugin->getConfiguration());
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $this->assertInternalType('array', $this->plugin->defaultConfiguration());
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm() {
    $this->assertInternalType('array', $this->plugin->buildConfigurationForm([], $this->getMock(FormStateInterface::class)));
  }

  /**
   * @covers ::multiple
   */
  public function testMultiple() {
    $this->assertInternalType('boolean', $this->plugin->multiple());
  }

}
