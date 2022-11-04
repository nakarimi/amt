<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines an entity reference mapper.
 *
 * @FeedsTarget(
 *   id = "config_entity_reference",
 *   field_types = {"entity_reference"},
 *   arguments = {"@entity_type.manager", "@entity.repository"}
 * )
 */
class ConfigEntityReference extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a ConfigEntityReference object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $type = $field_definition->getSetting('target_type');
    if (!\Drupal::entityTypeManager()->getDefinition($type)->entityClassImplements(ConfigEntityInterface::class)) {
      return;
    }

    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('target_id');
  }

  /**
   * Returns possible fields to reference by for a config entity.
   *
   * @return array
   *   A list of fields to reference by.
   */
  protected function getPotentialFields() {
    $options = [
      'id' => $this->t('ID'),
      'uuid' => $this->t('UUID'),
    ];

    return $options;
  }

  /**
   * Returns the entity type that can be referenced.
   *
   * @return string
   *   The entity type being referenced.
   */
  protected function getEntityType() {
    return $this->settings['target_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'reference_by' => 'id',
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = $this->getPotentialFields();

    // Hack to find out the target delta.
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'target-settings-') === 0) {
        list(, , $delta) = explode('-', $key);
        break;
      }
    }

    $form['reference_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference by'),
      '#options' => $options,
      '#default_value' => $this->configuration['reference_by'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    if ($target_id = $this->findEntity($values['target_id'], $this->configuration['reference_by'])) {
      $values['target_id'] = $target_id;
      return;
    }

    throw new EmptyFeedException();
  }

  /**
   * Searches for an entity by entity key.
   *
   * @param string $value
   *   The value to search for.
   *
   * @return int|bool
   *   The entity id, or false, if not found.
   */
  protected function findEntity($value, $field) {
    switch ($this->configuration['reference_by']) {
      case 'id':
        return $value;

      case 'uuid':
        if (NULL !== ($entity = $this->entityRepository->loadEntityByUuid($this->getEntityType(), $value))) {
          return $entity->id();
        }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $options = $this->getPotentialFields();

    $summary = [];

    if ($this->configuration['reference_by'] && isset($options[$this->configuration['reference_by']])) {
      $summary[] = $this->t('Reference by: %message', ['%message' => $options[$this->configuration['reference_by']]]);
    }
    else {
      $summary[] = $this->t('Please select a field to reference by.');
    }

    return implode('<br>', $summary);
  }

}
