<?php

namespace Drupal\amt_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure twilio settings for this site.
 */
class SMSTestForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smstest_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone Number'),
    ];
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text Message'),
    ];
    $buildForm = parent::buildForm($form, $form_state);
    $buildForm['actions']['submit']['#value'] = $this->t('Send Message');
    return $buildForm;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Send SMS Function can be used anywhere.
    _amt_notification_send_sms($form_state->getValue('number'), $form_state->getValue('text'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['amt_notification.adminsettings'];
  }

}
