<?php

namespace Drupal\link_to_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link_to_entity\Event\LinkToEntityEvent;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Plugin implementation of the 'link_entity_query_param' formatter.
 *
 * @FieldFormatter(
 *   id = "link_entity_query_param",
 *   label = @Translation("Link entity query param"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class LinkEntityQueryParam extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

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
  public static function defaultSettings() {
    return array(
      'link_page' => '',
      'query_field' => '',
      ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['link_page'] = array(
      '#title' => t('Define link page'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('link_page'),
      '#description' => t('Define the page where entity will be linked with query parameter. for example /faq'),
      '#required' => TRUE,
    );
    $element['query_field'] = array(
      '#title' => t('Query parameter name'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('query_field'),
      '#description' => t('Define name of the query parameter. for example, tid,nid etc'),
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Link page set to: @link_page', array('@link_page' => $this->getSetting('link_page')));
    $summary[] = t('Query parameter name: @query_field', array('@query_field' => $this->getSetting('query_field')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    $link_page = $this->getSetting('link_page');
    $query_field = $this->getSetting('query_field');
    $entities = $this->getEntitiesToView($items, $langcode);
    
    foreach ($entities as $delta => $entity) {
      $label = $entity->label();
      $url = Url::fromUserInput($link_page, ['query' => [$query_field => $entity->id()]]);
      $e = new LinkToEntityEvent($entity->toArray(), $url);
      $event = $this->eventDispatcher->dispatch('link_to_entity.updateLinkQueryParam', $e);
      $url = $event->getUrl();

      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $label,
        '#url' => $url,
        '#options' => $url->getOptions(),
      ];

      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    return $elements;
  }

}
