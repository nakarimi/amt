<?php

/**
* Implements hook_drush_command().
*/
function amt_migrate_drush_command() {
  $items['migrate-prune-configs'] = array(
    'description' => dt('Prune migrate configs.'),
    'arguments' => [
      'module_name' => dt('Module name'),
    ],
    'aliases' => array('mpc'),
    'examples' => array(
      'drush migrate-prune-configs module_name' => 'Removes migrate configurations.',
    ),
    'callback' => 'amt_migrate_migrate_prune_configs',
  );
  $items['moduler-config-reset'] = array(
    'description' => dt('Resets default config for a given module.'),
    'arguments' => [
      'module_name' => dt('Module name'),
    ],
    'aliases' => ['mcr'],
    'required-arguments' => 1,
    'callback' => 'amt_migrate_config_reset',
  );
  $items['migrate-re-register-migrations'] = array(
    'description' => dt('Re-register migrations and groups.'),
    'arguments' => [
      'module_name' => dt('Module name'),
    ],
    'aliases' => ['mrm'],
    'required-arguments' => 1,
    'callback' => 'amt_migrate_re_register_migrations',
  );
  return $items;
}

/**
 * Removes configs for a given module.
 */
function amt_migrate_migrate_prune_configs($module_name) {
  //Confirmation will be Y/N when use type “y” the condition will be executed if not, it will not.
  if (drush_confirm(dt("Are you sure you want to remove migrate configs?"))) {
  drush_print("Removing.....");

  if (_amt_migrate_remove_configs($module_name)) {
    drush_print("Removed successfully");

    return TRUE;
  }
  }
}

/**
 * Installs configs for a given module.
 * @param  string $module_name module name.
 */
function amt_migrate_config_reset($module_name) {
  if (!in_array($module_name, array_keys(\Drupal::moduleHandler()->getModuleList()))) {
  return drush_set_error(dt('Module @module_name not found.', ['@module_name' => $module_name]));
  }
  // Initiate config installer for given module.
  if (_amt_migrate_install_config($module_name)) {
  drush_print('Configuration was successfully reset.');

  return TRUE;
  }
}

/**
 * Drush command callback for re-registering migration configs.
 * @param  string $module_name module name.
 */
function amt_migrate_re_register_migrations($module_name) {
  // Check input.
  if (!in_array($module_name, array_keys(\Drupal::moduleHandler()->getModuleList()))) {
  return drush_set_error(dt('Module @module_name not found.', ['@module_name' => $module_name]));
  }
  // Confirm re-registering.
  if (drush_confirm(dt("Are you sure you want to re-register migrate configs?"))) {
  drush_print("Removing.....");
  if (_amt_migrate_remove_configs($module_name)) {
      drush_print("Removed successfully");
  }
  else {
      drush_print("There was a problem removing old configs");
  }

  if (_amt_migrate_install_config($module_name)) {
      drush_print("Configuration was successfully reset.");
  }
  else {
      drush_print("There was a problem resetting configuration for " . $module_name);
  }

  return TRUE;
  }

}

/**
 * Helper module to remove configs for a given module.
 * @param  string $module_name module name.
 * @return bool             TRUE if the files exist.
 */
function _amt_migrate_remove_configs($module_name) {
  // Get the path for the module we want to remove configs for.
  $module_path = DRUPAL_ROOT. base_path() . drupal_get_path('module', $module_name) . '/config/install';

  // Get all of the migration config names.
  $configs = array();
  $files = file_scan_directory($module_path, '/migrate_plus/');

  if ($files) {
  foreach ($files as $file) {
      $configs[] = $file->name;
  }

  // Delete each config using configFactory.
  foreach ($configs as $config_name) {
      drush_print("..." . $config_name);
      \Drupal::configFactory()->getEditable($config_name)->delete();
  }

  return TRUE;
  }

  return FALSE;
}

/**
 * Helper module to install configs for a given module.
 * @param  string $module_name module name.
 * @return bool             TRUE if no error from installDefaultConfig.
 */
function _amt_migrate_install_config($module_name) {
  // Initiate config installer for given module.
  \Drupal::service('config.installer')->installDefaultConfig('module', $module_name);

  return TRUE;
}
