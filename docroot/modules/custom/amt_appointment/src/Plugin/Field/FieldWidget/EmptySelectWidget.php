<?php

namespace Drupal\amt_appointment\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'options_select_empty' widget.
 *
 * Extents 'options_select' field-widget to prevent loading any entities,
 * and by default generates an empty Option-select form-widget.
 * This is useful to reduce the load time, when you have lots of entities
 * and want to add just few later (maybe using "hook_form_alter()" hook).
 *
 * @FieldWidget(
 *   id = "options_select_empty",
 *   label = @Translation("Select list (Empty)"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class EmptySelectWidget extends OptionsSelectWidget {

  /**
   * Builds our element without any Select-list-option.
   *
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Prevents loading any options by NOT calling "parent::formElement(...)".
    $element = OptionsWidgetBase::formElement($items, $delta, $element, $form, $form_state);

    // Simulates behavior of overridden method
    // (If there is any selected value, The "$items" should have
    // dynamic-properties with "$this->column" as name)
    $emptyLabel = $this->getEmptyLabel();
    $selection = $items[$delta]->{$this->column};

    // Merges our form-settings with base element:
    return array_replace($element, [
      '#type' => 'select',
      '#options' => $emptyLabel ? ['_none' => $emptyLabel] : [],
      '#default_value' => $selection ? [$selection] : [],
      '#multiple' => $this->multiple,
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * Ensures our Select-widget has always an empty-label,
   * (To prevent Drupal's warning-messages)
   */
  protected function getEmptyLabel() {
    // return $this->required ? t('- Select a value -') : t('- None -');
  }

}
