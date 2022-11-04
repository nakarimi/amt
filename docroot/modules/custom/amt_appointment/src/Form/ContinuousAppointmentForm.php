<?php

namespace Drupal\amt_appointment\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eck\Form\Entity\EckEntityForm;

/**
 * Continuous-Appointment form.
 */
class ContinuousAppointmentForm extends EckEntityForm {
  const ENTITY_TYPE = 'events';
  const OPERATION = 'continuous_appointment';
  const LINK_TEMPLATE = 'amt-' . self::OPERATION;

  /**
   * Implements hook_entity_type_alter().
   *
   * Registers this as form-mode handler.
   */
  public static function alterEntityType(array &$entity_types) {
    /* @var $eckEntityType \Drupal\Core\Entity\ContentEntityType */
    $eckEntityType = $entity_types[self::ENTITY_TYPE];
    // Handle our own route:
    $eckEntityType->setFormClass(self::OPERATION, self::class);
    // Handle "entity.events.edit_form.continuous_appointment":
    $eckEntityType->setFormClass('edit_' . self::OPERATION, self::class);

    $eckEntityType->setLinkTemplate(self::LINK_TEMPLATE, '/events/{eventId}/edit/continuous');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Handles both "events_lesson_continuous_appointment_form"
    // and "events_group_lesson_continuous_appointment_form".
    if (self::isContinuous($this->entity)) {
      $form['#attached']['library'][] = 'amt_appointment/continuous_appointment_form';
    }

    return $form;
  }

  /**
   * Checks for Continuous and/or Standing events.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   Appointment's entity.
   *
   * @return bool
   *   true for Standing-Appointments
   */
  public static function isContinuous(EntityInterface $event) {
    $bundle = $event->bundle();

    // Identifying the lesson bundle.
    // Returns expiration date for the event.
    if ($bundle === 'lesson' || $bundle === 'group_lesson' || $bundle === 'services') {
      $field = $event->field_expiration_date;
      return $field && $field->value;
    }
    return FALSE;
  }

}
