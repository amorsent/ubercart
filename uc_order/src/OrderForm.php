<?php

/**
 * @file
 * Contains \Drupal\uc_order\OrderFormController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Ubercart order form.
 */
class OrderForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $order = $this->entity;

    $form['#order'] = $order;
    $form['order_id'] = array('#type' => 'hidden', '#value' => $order->id());
    $form['order_uid'] = array('#type' => 'hidden', '#value' => $order->getUserId());

    $modified = $form_state->getValue('order_modified') ?: $order->getChangedTime();
    $form['order_modified'] = array('#type' => 'hidden', '#value' => $modified);

    $panes = _uc_order_pane_list('edit');
    foreach ($panes as $pane) {
      if (in_array('edit', $pane['show'])) {
        $func = $pane['callback'];
        if (function_exists($func)) {
          $func('edit-form', $order, $form, $form_state);
        }
      }
    }

    $form = parent::form($form, $form_state);

    $form_state->loadInclude('uc_store', 'inc', 'includes/uc_ajax_attach');
    $form['#process'][] = 'uc_ajax_process_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $element['submit']['#value'] = $this->t('Save changes');
    $element['delete']['#access'] = $this->entity->access('delete');
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $order = $this->buildEntity($form, $form_state);

    if ($form_state->getValue('order_modified') != $order->getChangedTime()) {
      $form_state->setErrorByName('order_modified', t('This order has been modified by another user, changes cannot be saved.'));
    }

    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $order = $this->entity;
    $original = clone $order;

    // Build list of changes to be applied.
    $panes = _uc_order_pane_list();
    foreach ($panes as $pane) {
      if (in_array('edit', $pane['show'])) {
        $pane['callback']('edit-process', $order, $form, $form_state);
      }
    }

    $log = array();

    foreach (array_keys($order->getFieldDefinitions()) as $key) {
      if ($original->$key->value !== $order->$key->value) {
        if (!is_array($order->$key->value)) {
          $log[$key] = array('old' => $original->$key->value, 'new' => $order->$key->value);
        }
      }
    }

    if (\Drupal::moduleHandler()->moduleExists('uc_stock')) {
      $qtys = array();
      foreach ($order->products as $product) {
        $qtys[$product->order_product_id] = $product->qty;
      }
    }

    if (is_array($form_state->getValue('products'))) {
      foreach ($form_state->getValue('products') as $product) {
        if (!isset($product['remove']) && intval($product['qty']) > 0) {
          foreach (array('qty', 'title', 'model', 'weight', 'weight_units', 'cost', 'price') as $field) {
            $order->products[$product['order_product_id']]->$field = $product[$field];
          }

          if (\Drupal::moduleHandler()->moduleExists('uc_stock')) {
            $product = (object)$product;
            $temp = $product->qty;
            $product->qty = $product->qty - $qtys[$product->order_product_id];
            uc_stock_adjust_product_stock($product, 0, $order);
            $product->qty = $temp;
          }
        }
        else {
          $log['remove_' . $product['nid']] = $product['title'] . ' removed from order.';
        }
      }
    }

    // Load line items again, since some may have been updated by the form.
    $order->line_items = $order->getLineItems();

    $order->logChanges($log);

    $order->save();

    drupal_set_message(t('Order changes saved.'));
  }

}
