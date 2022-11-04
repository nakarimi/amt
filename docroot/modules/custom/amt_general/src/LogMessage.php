<?php

namespace Drupal\amt_general;

use Drupal\Core\Render\Markup;

/**
 * Provid the functionality of log and show Message.
 *
 * @package Drupal\amt_general\LogMessage
 */
class LogMessage {

  /**
   * Helper function to log and optionally display a message to the screen.
   *
   * @param string $message
   *   The message to be logged.
   * @param string $status
   *   The status of the message.
   * @param bool $display
   *   Whether the message should also be displayed to the user.
   * @param string $type
   *   The company name.
   */
  public static function showMessage($message, $status = 'info', $display = FALSE, $type = 'AMT') {
    $message = Markup::create($message);
    // Log the message to the system log.
    \Drupal::logger($type)->{$status}($message);

    // Check if the message is supposed to be displayed as well.
    if ($display) {
      $statusMap = [
        'info' => 'addStatus',
        'warning' => 'addWarning',
        'error' => 'addError',
      ];

      // Get the callback based on the status of the message.
      $callback = $statusMap['info'];
      if (!empty($statusMap[$status])) {
        $callback = $statusMap[$status];
      }

      // Actually add the message to the message queue.
      \Drupal::messenger()->{$callback}($message);
    }
  }

}
