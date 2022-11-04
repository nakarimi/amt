<?php

namespace Drupal\duplicate_role\Form;

use Drupal\user\Entity\Role;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for adding a new role.
 */
class DuplicateRoleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'duplicate_role_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $note = NULL) {
    $form = array();
    $u_roles = user_role_names();
    asort($u_roles);

    $options = array();
    $options[] = t('-- Please select one role --');

    foreach ($u_roles as $key => $value) {
      $options[$key] = $value;
    }

    $form['base_role'] = array(
      '#type' => 'select',
      '#title' => t('Choose role to duplicate'),
      '#description' => t("Select role to duplicate"),
      '#options' => $options,
    );

    $form['new_role_name'] = array(
      '#type' => 'textfield',
      '#title' => t('New role'),
      '#required' => TRUE,
      '#size' => 40,
      '#maxlength' => 40,
      '#description' => t("New role name"),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Create new role'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $new_role_name = $form_state->getValue('new_role_name');
    $roles = user_role_names();
    if (in_array($new_role_name, $roles)) {
      $form_state->setErrorByName('new_role_name', t('This role %role_name already exists. Please try a different name.', array('%role_name' => $new_role_name)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $base_role = $form_state->getValue('base_role');
    $new_role_name = $form_state->getValue('new_role_name');
    $roles = strtolower(str_replace(' ', '_', $new_role_name));

    $data = array('id' => $roles, 'label' => $new_role_name);
    $role = Role::create($data);
    $role->save();

    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $roles = entity_load('user_role', $base_role);
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->load($base_role);
    foreach ($permissions as $permission_name => $permission_obj) {
      if ($roles->hasPermission($permission_name)) {
        $permis_name[$permission_name] = $roles->hasPermission($permission_name);
        $riyu[] = $permission_name;
      }
    }
    user_role_grant_permissions($role->id(), $riyu);

    drupal_set_message(t('New role %role_name added successfully.', array('%role_name' => $new_role_name)));

  }

}
