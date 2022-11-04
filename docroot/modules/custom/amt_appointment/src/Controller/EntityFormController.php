<?php

namespace Drupal\amt_appointment\Controller;

use Drupal\amt_appointment\Form\ContinuousAppointmentForm;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provide form for our form-display.
 */
class EntityFormController extends ControllerBase {

  /**
   * Builds Standing-appointment form-mode.
   *
   * @param int $events
   *   Appointment id.
   *
   * @return array
   *   Created Form array.
   */
  public function buildEditForm($events) {
    $entityName = ContinuousAppointmentForm::ENTITY_TYPE;
    /* @var $manager \Drupal\Core\Entity\EntityTypeManager */
    $manager = \Drupal::entityTypeManager();

    $entity = $manager->getStorage($entityName)
      ->load($events);

    $form = $manager->getFormObject($entityName, ContinuousAppointmentForm::OPERATION)
      ->setEntity($entity);

    return \Drupal::formBuilder()->getForm($form);
  }

  /**
   * Checks for possibility to edit Future-Appointments.
   *
   * @param mixed $events
   *   Appointment id.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   allowed for Standing-events.
   */
  public function accessEditContinuous($events) {
    $manager = \Drupal::entityTypeManager()
      ->getStorage(ContinuousAppointmentForm::ENTITY_TYPE);

    $entity = $manager->load($events);

    return AccessResult::allowedIf($entity instanceof EntityInterface
      && ContinuousAppointmentForm::isContinuous($entity)
    )
      ->addCacheableDependency($entity)
      ->cachePerPermissions();
  }

}
