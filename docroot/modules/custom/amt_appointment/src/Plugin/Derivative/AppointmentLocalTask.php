<?php

namespace Drupal\amt_appointment\Plugin\Derivative;

use Drupal\amt_appointment\Form\ContinuousAppointmentForm;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for specific entity bundles.
 *
 * @see \Drupal\amt_appointment\Controller\EntityFormController
 */
class AppointmentLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an AppointmentLocalTask object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $entityTypeId = ContinuousAppointmentForm::ENTITY_TYPE;
    $entityType = $this->entityTypeManager->getDefinition($entityTypeId);

    $hasEditPath = $entityType->hasLinkTemplate(ContinuousAppointmentForm::LINK_TEMPLATE);

    if ($hasEditPath) {
      $this->derivatives["$entityTypeId.amt_tab"] = [
        'route_name' => 'amt_appointment.edit_continuous',
        'title' => $this->t('Edit as Standing'),
        'base_route' => "entity.$entityTypeId.canonical",
        'weight' => 3,
      ];
    }

    // Refine result:
    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
