<?php
/**
 * @file Contains the code to generate the custom drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function drush_delete_drush_command() {
  $items = array();
  $items['delete-all'] = [
    'description' => 'Delete all contents.',
    'arguments' => [
      'type' => 'The entity type name',
    ],
    'drupal dependencies' => ['drush_delete'],
    'aliases' => ['delete-all article'],
  ];
  $items['delete-all-entity'] = [
    'description' => 'Delete all entity content.',
    'arguments' => [
      'type' => 'The entity type name',
    ],
    'drupal dependencies' => ['drush_delete'],
    'aliases' => ['dae'],
  ];
  $items['delete-all-taxonomy-vocabulary-term'] = [
    'description' => 'Delete selected Taxonomy Vocabulary terms.',
    'arguments' => [
      'type' => 'The taxonomy vocabulary name',
    ],
    'drupal dependencies' => ['drush_delete'],
    'aliases' => ['delete-all taxonomy-vocabulary'],
  ];
  return $items;
}

/**
 * Call back function drush_custom_drush_command_say_hello()
 * The call back function name in the  following format
 *   drush_{module_name}_{item_id_for_command}()
 */
function drush_drush_delete_delete_all($type = '') {
  $service = \Drupal::service('drush_delete.entity');
  drush_print($service->deleteAllEntityType($type));
}

/**
 * Call back function drush_custom_drush_command_say_hello()
 * The call back function name in the  following format
 *   drush_{module_name}_{item_id_for_command}()
 */
function drush_drush_delete_delete_all_entity($type = '') {
  $service = \Drupal::service('drush_delete.entity');
  drush_print($service->deleteAllEntity($type));
}

/**
 * Call back function drush_custom_drush_command_say_hello()
 * The call back function name in the  following format
 *   drush_{module_name}_{item_id_for_command}()
 */
function drush_drush_delete_delete_all_taxonomy_vocabulary_term($type = '') {
  $service = \Drupal::service('drush_delete.entity');
  drush_print($service->deleteAllTerms($type));
}
