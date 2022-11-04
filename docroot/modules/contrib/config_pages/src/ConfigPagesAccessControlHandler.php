<?php

namespace Drupal\config_pages;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the config page entity type.
 *
 * @see \Drupal\config_pages\Entity\ConfigPages
 */
class ConfigPagesAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view') {
      return AccessResult::allowed();
    }
    if ($operation == 'update' && ($account->hasPermission('edit config_pages entity') || $account->hasPermission('edit ' . $entity->id() . ' config page entity'))) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
