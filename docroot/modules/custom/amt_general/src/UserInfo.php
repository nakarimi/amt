<?php

namespace Drupal\amt_general;

use Drupal\user\Entity\User;

/**
 * Provides additional information about the user-entity which is targeted.
 *
 * @package Drupal\amt_general
 */
class UserInfo {
  /**
   * Drupal's user-entity which is targeted by this class.
   *
   * @var \Drupal\user\Entity\User
   */
  public $entity;

  /**
   * Targets the User-entity specified by the $id param.
   *
   * @param mixed $id
   *   The id of user which should be targeted,
   *   when not set the current-user is used.
   *
   * @see getEntity()
   */
  public function __construct($id = NULL) {
    if (!$id) {
      $id = \Drupal::currentUser()->id();
    }
    $this->entity = User::load($id);
  }

  /**
   * Checks if user was found and is targeted.
   */
  public function isValid() {
    return $this->entity;
  }

  /**
   * Checks for staff-member users.
   */
  public function isStaffMember() {
    return $this->entity->hasRole('instructor')
      || $this->entity->hasRole('studio_director')
      || $this->entity->hasRole('studio_manager');
  }

  /**
   * Checks if the user is an Administrator.
   *
   * @return bool
   *   true for Administrator users.
   */
  public function isAdmin() {
    return $this->entity->hasRole('administrator');
  }

  /**
   * Checks if the user is an Executive.
   *
   * @return bool
   *   true for Executive users.
   */
  public function isExecutive() {
    return $this->entity->hasRole('executive');
  }

  /**
   * Checks if the user is an Instructor.
   *
   * @return bool
   *   true for Instructor users.
   */
  public function isInstructor() {
    return $this->entity->hasRole('instructor');
  }

  /**
   * Checks if the user is a Studio-Director.
   *
   * @return bool
   *   true for Studio-Director users.
   */
  public function isDirector() {
    return $this->entity->hasRole('studio_director');
  }

  /**
   * Checks if the user is a Studio-Manager.
   *
   * @return bool
   *   true for Studio-Manager users.
   */
  public function isManager() {
    return $this->entity->hasRole('studio_manager');
  }

}
