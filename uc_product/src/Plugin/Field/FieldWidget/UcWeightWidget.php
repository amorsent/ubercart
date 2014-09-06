<?php

/**
 * @file
 * Contains \Drupal\uc_product\Plugin\Field\FieldWidget\UcWeightWidget.
 */

namespace Drupal\uc_product\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the Ubercart weight widget.
 *
 * @FieldWidget(
 *   id = "uc_weight",
 *   label = @Translation("Weight"),
 *   field_types = {
 *     "uc_weight",
 *   }
 * )
 */
class UcWeightWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : 0;
    $units = isset($items[$delta]->units) ? $items[$delta]->units : \Drupal::config('uc_store.settings')->get('units.weight');

    $element += array(
      '#type' => 'fieldset',
      '#attributes' => array('class' => array(
        'container-inline',
        'fieldgroup',
        'form-composite',
      )),
    );

    $element['value'] = array(
      '#type' => 'number',
      '#title' => t('Weight'),
      '#title_display' => 'invisible',
      '#default_value' => $value,
      '#size' => 6,
      '#min' => 0,
    );

    $element['units'] = array(
      '#type' => 'select',
      '#title' => t('Units'),
      '#title_display' => 'invisible',
      '#default_value' => $units,
      '#options' => array(
        'lb' => t('Pounds'),
        'kg' => t('Kilograms'),
        'oz' => t('Ounces'),
        'g' => t('Grams'),
      ),
    );

    return $element;
  }

}
