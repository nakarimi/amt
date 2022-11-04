<?php

namespace Drupal\amt_validations\Plugin\QueueWorker;

/**
 * @file
 * Contains \Drupal\amt_validations\Plugin\QueueWorker\EventsQueue.php.
 */

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes tasks for example module.
 *
 * @QueueWorker(
 *   id = "events_standing",
 *   title = @Translation("Queue Worker EventS"),
 *   cron = {"time" = 90}
 * )
 */
class EventsQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $this->proccessStanding($item['id']);
  }

  /**
   * Create New Entities for Standing Event.
   *
   * This function Creates New Events based on Current
   * Standing Informations.
   *
   * @param int $id
   *   Event ID.
   */
  private function proccessStanding($id) {

    // Delete All Sub Event if exists.
    // To Create New ones.
    amt_validations_deletefutureevents($id);
    // Duplicating Event.
    amt_validations_duplicate_events($id);
  }

}
