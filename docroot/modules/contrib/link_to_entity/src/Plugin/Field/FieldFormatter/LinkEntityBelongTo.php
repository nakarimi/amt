<?php

namespace Drupal\link_to_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Url;
use Drupal\link_to_entity\Event\LinkToEntityEvent;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Plugin implementation of the 'Link_Entity_Belong_to' formatter.
 *
 * @FieldFormatter(
 *   id = "Link_Entity_Belong_to",
 *   label = @Translation("Link entity belongs to"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class LinkEntityBelongTo extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'], $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $entities = $this->getEntitiesToView($items, $langcode);
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityType()->id();

    foreach ($entities as $delta => $item) {
      $label = $item->label();
      $url = Url::fromRoute('entity.' . $entity_type . '.canonical', [$entity_type => $entity->id()]);
      $e = new LinkToEntityEvent($item->toArray(), $url);
      $event = $this->eventDispatcher->dispatch('link_to_entity.updateLinkBelong', $e);
      $url = $event->getUrl();

      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $label,
        '#url' => $url,
        '#options' => $url->getOptions(),
      ];

      $elements[$delta]['#cache']['tags'] = $item->getCacheTags();
    }

    return $elements;
  }

}
