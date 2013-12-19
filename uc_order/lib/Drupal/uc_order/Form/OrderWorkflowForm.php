<?php

/**
 * @file
 * Contains \Drupal\uc_order\Form\OrderWorkflowForm.
 */

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;

/**
 * Displays the order workflow form for order state and status customization.
 */
class OrderWorkflowForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_workflow_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $states = uc_order_state_options_list();
    $statuses = entity_load_multiple('uc_order_status');

    $form['order_states'] = array(
      '#type' => 'details',
      '#title' => t('Order states'),
      '#collapsed' => TRUE,
    );
    $form['order_states']['order_states'] = array(
      '#type' => 'table',
      '#header' => array(t('State'), t('Default order status')),
    );

    foreach ($states as $state_id => $title) {
      $form['order_states']['order_states'][$state_id]['title'] = array(
        '#markup' => $title,
      );

      // Create the select box for specifying a default status per order state.
      $options = array();
      foreach ($statuses as $status) {
        if ($status->state == $state_id) {
          $options[$status->id] = $status->name;
        }
      }
      if (empty($options)) {
        $form['order_states']['order_states'][$state_id]['default'] = array(
          '#markup' => t('- N/A -'),
        );
      }
      else {
        $form['order_states']['order_states'][$state_id]['default'] = array(
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => uc_order_state_default($state_id),
        );
      }
    }

    $form['order_statuses'] = array(
      '#type' => 'details',
      '#title' => t('Order statuses'),
    );
    $form['order_statuses']['order_statuses'] = array(
      '#type' => 'table',
      '#header' => array(t('ID'), t('Title'), t('List position'), t('State'), t('Remove')),
    );

    foreach ($statuses as $status) {
      $form['#locked'][$status->id] = $status->locked;

      $form['order_statuses']['order_statuses'][$status->id]['id'] = array(
        '#markup' => $status->id,
      );
      $form['order_statuses']['order_statuses'][$status->id]['name'] = array(
        '#type' => 'textfield',
        '#default_value' => $status->name,
        '#size' => 32,
        '#required' => TRUE,
      );
      $form['order_statuses']['order_statuses'][$status->id]['weight'] = array(
        '#type' => 'weight',
        '#delta' => 20,
        '#default_value' => $status->weight,
      );
      if ($status->locked) {
        $form['order_statuses']['order_statuses'][$status->id]['state'] = array(
          '#markup' => $states[$status->state],
        );
      }
      else {
        $form['order_statuses']['order_statuses'][$status->id]['state'] = array(
          '#type' => 'select',
          '#options' => $states,
          '#default_value' => $status->state,
        );
        $form['order_statuses']['order_statuses'][$status->id]['remove'] = array(
          '#type' => 'checkbox',
        );
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit changes'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    foreach ($form_state['values']['order_states'] as $key => $value) {
      variable_set('uc_state_' . $key . '_default', $value['default']);
    }

    foreach ($form_state['values']['order_statuses'] as $id => $value) {
      $status = entity_load('uc_order_status', $id);
      if (!$form['#locked'][$id] && $value['remove']) {
        $status->delete();
        drupal_set_message(t('Order status %status removed.', array('%status' => $status->name)));
      }
      else {
        $status->name = $value['name'];
        $status->weight = (int) $value['weight'];

        // The state cannot be changed if the status is locked.
        if (!$form['#locked'][$key]) {
          $status->state = $value['state'];
        }

        $status->save();
      }
    }

    drupal_set_message(t('Order workflow information saved.'));
  }

}
