<?php

/**
 * @file
 * Contains \Drupal\uc_order\Plugin\views\field\FullName.
 */

namespace Drupal\uc_order\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide full names.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("uc_order_full_name")
 */
class FullName extends FieldPluginBase {

  /**
   * Override init function to provide generic option to link to user.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    if (!empty($this->options['link_to_user'])) {
      $this->additional_fields['uid'] = array('table' => 'uc_orders', 'field' => 'uid');
    }
  }

  /**
   * Overrides FieldPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_user'] = array('default' => FALSE, 'bool' => TRUE);
    $options['format'] = array('default' => 'first_last');
    return $options;
  }

  /**
   * Overrides FieldPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['link_to_user'] = array(
      '#title' => t('Link this field to its user'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_user'],
    );

    $form['format'] =  array(
      '#type' => 'select',
      '#title' => t('Format'),
      '#options' => array(
        'first_last' => t('First Last'),
        'last_c_first' => t('Last, First'),
        'last_first' => t('Last First'),
      ),
      '#default_value' => $this->options['format'],
    );
  }

  /**
   * Renders whatever the data is as a link to the order.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  protected function render_link($data, $values) {
    $account = \Drupal::currentUser();
    if (!empty($this->options['link_to_user']) && $account->hasPermission('access user profiles')) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'user/' . $this->getValue($values, 'uid');;
    }
    return $data;
  }

  /**
   * Overrides FieldPluginBase::render().
   */
  public function render(ResultRow $values) {
    $first = $this->getValue($values);
    $last = $this->getValue($values, 'last_name');

    switch ($this->options['format']) {
      case 'last_first':
        $output = "$last $first";
      case 'last_c_first':
        $output = "$last, $first";
      case 'first_last':
        $output = "$first $last";
    }

    if (isset($output)) {
      return $this->render_link($this->sanitizeValue($output), $values);
    }
  }
}
