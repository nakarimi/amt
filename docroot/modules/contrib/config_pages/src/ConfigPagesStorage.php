<?php

namespace Drupal\config_pages;

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for ConfigPages.
 *
 * This extends the base storage class, adding required special handling for
 * ConfigPages entities.
 */
class ConfigPagesStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $entity = NULL;

    // Default behavior allow to load entity by ID.
    if (is_numeric($id)) {
      $entities = $this->loadMultiple([$id]);
      $entity = isset($entities[$id]) ? $entities[$id] : NULL;
    }
    else {
      // If config page type name given try to load it.
      $entity = ConfigPages::config($id);
    }
    return $entity;
  }

}
