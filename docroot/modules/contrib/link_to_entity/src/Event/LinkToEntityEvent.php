<?php

namespace Drupal\link_to_entity\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Url;

/**
 * Class LinkToEntityEvent
 * @package Drupal\link_to_entity\Event
 */
class LinkToEntityEvent extends Event {

  /**
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * @var array.
   */
  protected $entity;

  /**
   * Constructor.
   */
  public function __construct($entity, Url $url) {
    $this->entity = $entity;
    $this->url = $url;
  }

  /**
   * Return the entity.
   * @return array()
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Return the url.
   * @return \Drupal\Core\Url
   */
  public function getUrl() {
    return $this->url;
  }

}
