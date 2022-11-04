<?php

namespace Drupal\amt_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure twilio settings for this site.
 */
class TwilioSettingsForm extends ConfigFormBase {
  // @var string Config settings name;
  const SETTINGS = 'amt_notification.twiliosettings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twilio_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'amt_notification.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('amt_notification.adminsettings');

    $form['twilio_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Auth Token'),
      '#default_value' => $config->get('token'),
    ];

    $form['twilio_usid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account SID'),
      '#default_value' => $config->get('usid'),
    ];

    $form['twilio_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twilio Number'),
      '#default_value' => $config->get('tnumb'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('amt_notification.adminsettings')
      // Set the submitted configuration setting.
      ->set('token', $form_state->getValue('twilio_token'))
      // You can set multiple configurations at once by making.
      // multiple calls to set();
      ->set('usid', $form_state->getValue('twilio_usid'))
      ->set('tnumb', $form_state->getValue('twilio_number'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
