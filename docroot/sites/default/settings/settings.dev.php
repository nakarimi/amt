<?php
/**
 * This file stores all settings that should be used on development sites.
 */

/**
 * Assertions.
 *
 * The Drupal project primarily uses runtime assertions to enforce the
 * expectations of the API by failing when incorrect calls are made by code
 * under development.
 *
 * @see http://php.net/assert
 * @see https://www.drupal.org/node/2492225
 *
 * If you are using PHP 7.0 it is strongly recommended that you set
 * zend.assertions=1 in the PHP.ini file (It cannot be changed from .htaccess
 * or runtime) on development machines and to 0 in production.
 *
 * @see https://wiki.php.net/rfc/expectations
 */
assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();

/**
 * Add the production settings to ensure maximum consistency.
 */
if (file_exists(__DIR__ . '/settings.live.php')) {
  include_once __DIR__ . '/settings.live.php';
}

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Drupal 8 Configuration of Environmental Indicator
$config['environment_indicator.indicator']['bg_color'] = '#2c9c3a';
$config['environment_indicator.indicator']['name'] = 'Environment: DEV';

// Diable GA tracking on this environment.
  // $config['google_analytics.settings']['account'] = 'UA-XXXXXXX-X';

