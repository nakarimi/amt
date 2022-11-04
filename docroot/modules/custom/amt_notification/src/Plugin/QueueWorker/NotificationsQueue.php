<?php

namespace Drupal\amt_notification\Plugin\QueueWorker;

/**
 * @file
 * Contains \Drupal\amt_notification\Plugin\QueueWorker\NotificationsQueue.php.
 */

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes tasks for notifications.
 *
 * @QueueWorker(
 *   id = "notifications",
 *   title = @Translation("Notifications queue worker"),
 *   cron = {"time" = 15}
 * )
 */
class NotificationsQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

    $subject = '';
    $body = '';
    // There is a config page that allows the users to set the contents
    // of the emails that are going to be sent to the students.
    // we named that config 'email_template_settings', this settings include
    // two types of emails, 1. thank you emails 2. reminder emails.
    // here we use one of them according to the notif_type parameter of the queue item.
    $email_template = \Drupal\config_pages\Entity\ConfigPages::config('email_template_settings');
    if ( $item['notif_type'] == 'thank_you') {
      $subject = $email_template->field_thank_you_email_subject->value;
      $body = $email_template->field_thank_you_email_body->value;
    }
    else if ($item['notif_type'] == 'reminder') {
      $subject = $email_template->field_reminder_email_subject->value;
      $body = $email_template->field_reminder_email_body->value;
    }

    // In the email_template_settings form user can use specific shortcodes
    // to make a dynamic content, here we translate these shortcodes to
    // the actual value.
    $short_codes = ['{first_name}', '{last_name}'];
    $translations = [$item['first_name'], $item['last_name']];
    $subject = str_replace($short_codes, $translations, $subject);
    $body = str_replace($short_codes, $translations, $body);

    // Students can choose to be notified by different methods, here we send them
    // notifications via the way they have chosen.
    if (strpos($item['notify_by'], 'Both') !== false || strpos($item['notify_by'], 'Email') !== false) {
      $this->sendEmail($item['email_address'], $subject, $body);
    }

    if (strpos($item['notify_by'], 'Both') !== false || strpos($item['notify_by'], 'SMS') !== false) {
      $this->sendMessage($item['phone_number'], $body);
    }
  }

  /**
   * Prepares the email to send to the user.
   *
   * The main goal of this function is to make the code easier to understand.
   */
  private function sendEmail(string $email_address, string $email_subject, string $email_body) {
    $email_info = [
      'email' => $email_address,
      'subject' => $email_subject,
      'body' => $email_body,
    ];
    _amt_notification_send_email($email_info);
  }

  /**
   * Prepares the message to send to the user.
   *
   * The main goal of this function is to make the code easier to understand.
   */
  private function sendMessage(string $phone_number, string $message_body) {
    _amt_notification_send_sms($phone_number, $message_body);
  }
}
