<?php

/**
 * @file
 * Contains \Drupal\uc_store\Plugin\views\field\Price.
 */

namespace Drupal\uc_store\Plugin\views\field;

use Drupal\views\Plugin\views\field\Numeric;
use Drupal\views\ResultRow;

/**
 * Field handler to provide formatted prices.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_price")
 */
class Price extends Numeric {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['format'] = array('default' => 'uc_price');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['format'] =  array(
      '#title' => t('Format'),
      '#type' => 'radios',
      '#options' => array(
        'uc_price' => t('Ubercart price'),
        'numeric' => t('Numeric'),
      ),
      '#default_value' => $this->options['format'],
      '#weight' => -1,
    );

    foreach (array('separator', 'format_plural', 'prefix', 'suffix') as $field) {
      $form[$field]['#states']['visible']['input[name="options[format]"]']['value'] = 'numeric';
    }
  }

  public function render(ResultRow $values) {
    if ($this->options['format'] == 'uc_price') {
      $value = $this->getValue($values);

      if (is_null($value) || ($value == 0 && $this->options['empty_zero'])) {
        return '';
      }

      return uc_currency_format($value);
    }
    else {
      return parent::render($values);
    }
  }
}
