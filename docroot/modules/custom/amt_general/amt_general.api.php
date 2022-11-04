<?php

/**
 * @file
 * Callbacks and hooks related to AMT system.
 */

/**
 * @addtogroup callbacks
 * @{
 */

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Callback for AJAX events.
 *
 * AJAX callback functions can be used to update existing or
 * add new form fields, update values in form fields and more,
 * while users can still interact with the form.
 *
 * @param array &$form
 *   All data of the form.
 * @param \Drupal\Core\Form\FormStateInterface $formState
 *   State of the form.
 *
 * @return \Drupal\Core\Ajax\AjaxResponse
 *   Returns the data back as AjaxResponse object.
 */
function callback_form_ajax(array &$form, FormStateInterface $formState) {
  // Here we do something meaningful with the results.
  $response = new AjaxResponse();
  $response->addCommand(new HtmlCommand('#message-box', 'my message.'));
  return $response;
}

/**
 * @} End of "addtogroup callbacks".
 */

/**
 * @addtogroup hooks
 * @{
 */

/* Place any hook definitions here. */

/**
 * @} End of "addtogroup hooks"
 */
