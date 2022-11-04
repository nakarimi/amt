<?php
$databases = array();
$config_directories = array();

/**
 * Load services definition file
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';
/**
 * Load services definition file
 */
$settings['hash_salt'] = 'OsV5Aaon-Az-zzkqC3LJJfs-tgT4iTBoJAUnrkl2aYXTWEEmtJWU1F7NCe-z60977RPQl51txg';
$settings['install_profile'] = 'minimal';

$settings['update_free_access'] = FALSE;
$settings['file_private_path'] = 'sites/default/private';
$config_directories['sync'] = '../config/d8_sync';

/**
 * If there is a local settings file, then include it.
 */
$local_settings = DRUPAL_ROOT . '/sites/default/settings/settings.local.php';
if (file_exists($local_settings)) {
  include_once $local_settings;
}

/**
 * Add the site-specific settings.
 */
$settings['drupal_env'] = (!empty($_SERVER['PANTHEON_ENVIRONMENT'])) ? $_SERVER['PANTHEON_ENVIRONMENT'] : 'local';
$env_settings = __DIR__ . '/settings.' . $settings['drupal_env'] . '.php';

// Fail safe, if environment isn't set correctly, just assume production.
if (empty($settings['drupal_env']) || !file_exists($env_settings)) {
  $settings['drupal_env'] = 'live';
}

// Include settings file as long as it's present.
if (file_exists($env_settings)) {
  include_once $env_settings;
}

/**
 * If there is a local overrides file, then include it.
 */
$local_overrides = DRUPAL_ROOT . '/sites/default/settings/settings.local-overrides.php';
if (file_exists($local_overrides)) {
  include_once $local_overrides;
}
