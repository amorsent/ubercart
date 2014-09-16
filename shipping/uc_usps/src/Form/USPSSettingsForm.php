<?php

/**
 * @file
 * Contains \Drupal\uc_usps\Form\USPSSettingsForm.
 */

namespace Drupal\uc_usps\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures USPS settings.
 */
class USPSSettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'uc_usps_admin_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $usps_config = $this->config('uc_usps.settings');

    // Put fieldsets into vertical tabs
    $form['usps-settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'js' => array(
          'vertical-tabs' => drupal_get_path('module', 'uc_usps') . '/js/uc_usps.admin.js',
        ),
      ),
    );

    // Container for credential forms
    $form['uc_usps_credentials'] = array(
      '#type'          => 'details',
      '#title'         => t('Credentials'),
      '#description'   => t('Account number and authorization information.'),
      '#group'         => 'usps-settings',
    );

    $form['uc_usps_credentials']['uc_usps_user_id'] = array(
      '#type' => 'textfield',
      '#title' => t('USPS user ID'),
      '#description' => t('To acquire or locate your user ID, refer to the <a href="!url">USPS documentation</a>.', array('!url' => 'http://drupal.org/node/1308256')),
      '#default_value' => $usps_config->get('user_id'),
    );

    $form['domestic'] = array(
      '#type' => 'details',
      '#title' => t('USPS Domestic'),
      '#description' => t('Set the conditions that will return a USPS quote.'),
      '#group'         => 'usps-settings',
    );

    $form['domestic']['uc_usps_online_rates'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display USPS "online" rates'),
      '#default_value' => $usps_config->get('online_rates'),
      '#description' => t('Show your customer standard USPS rates (default) or discounted "online" rates.  Online rates apply only if you, the merchant, pay for and print out postage from the USPS <a href="https://sss-web.usps.com/cns/landing.do">Click-N-Ship</a> web site.'),
    );

    $form['domestic']['uc_usps_env_services'] = array(
      '#type' => 'checkboxes',
      '#title' => t('USPS envelope services'),
      '#default_value' => $usps_config->get('env_services'),
      '#options' => \Drupal\uc_usps\USPSUtilities::envelopeServices(),
      '#description' => t('Select the USPS services that are available to customers. Be sure to include the services that the Postal Service agrees are available to you.'),
    );

    $form['domestic']['uc_usps_services'] = array(
      '#type' => 'checkboxes',
      '#title' => t('USPS parcel services'),
      '#default_value' => $usps_config->get('services'),
      '#options' => \Drupal\uc_usps\USPSUtilities::services(),
      '#description' => t('Select the USPS services that are available to customers. Be sure to include the services that the Postal Service agrees are available to you.'),
    );

    $form['international'] = array(
      '#type' => 'details',
      '#title' => t('USPS International'),
      '#description' => t('Set the conditions that will return a USPS International quote.'),
      '#group'         => 'usps-settings',
    );

    $form['international']['uc_usps_intl_env_services'] = array(
      '#type' => 'checkboxes',
      '#title' => t('USPS international envelope services'),
      '#default_value' => $usps_config->get('intl_env_services'),
      '#options' => \Drupal\uc_usps\USPSUtilities::internationalEnvelopeServices(),
      '#description' => t('Select the USPS services that are available to customers. Be sure to include the services that the Postal Service agrees are available to you.'),
    );

    $form['international']['uc_usps_intl_services'] = array(
      '#type' => 'checkboxes',
      '#title' => t('USPS international parcel services'),
      '#default_value' => $usps_config->get('intl_services'),
      '#options' => \Drupal\uc_usps\USPSUtilities::internationalServices(),
      '#description' => t('Select the USPS services that are available to customers. Be sure to include the services that the Postal Service agrees are available to you.'),
    );

    // Container for quote options
    $form['uc_usps_quote_options'] = array(
      '#type'          => 'details',
      '#title'         => t('Quote options'),
      '#description'   => t('Preferences that affect computation of quote.'),
      '#group'         => 'usps-settings',
    );

    $form['uc_usps_quote_options']['uc_usps_all_in_one'] = array(
      '#type' => 'radios',
      '#title' => t('Product packages'),
      '#default_value' => $usps_config->get('all_in_one'),
      '#options' => array(
        0 => t('Each product in its own package'),
        1 => t('All products in one package'),
      ),
      '#description' => t('Indicate whether each product is quoted as shipping separately or all in one package. Orders with one kind of product will still use the package quantity to determine the number of packages needed, however.'),
    );

    // Insurance
    $form['uc_usps_quote_options']['uc_usps_insurance'] = array(
      '#type' => 'checkbox',
      '#title' => t('Package insurance'),
      '#default_value' => $usps_config->get('insurance'),
      '#description' => t('When enabled, the quotes presented to the customer will include the cost of insurance for the full sales price of all products in the order.'),
      '#disabled' => TRUE,
    );

    // Delivery Confirmation
    $form['uc_usps_quote_options']['uc_usps_delivery_confirmation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Delivery confirmation'),
      '#default_value' => $usps_config->get('delivery_confirmation'),
      '#description' => t('When enabled, the quotes presented to the customer will include the cost of delivery confirmation for all packages in the order.'),
      '#disabled' => TRUE,
    );

    // Signature Confirmation
    $form['uc_usps_quote_options']['uc_usps_signature_confirmation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Signature confirmation'),
      '#default_value' => $usps_config->get('signature_confirmation'),
      '#description' => t('When enabled, the quotes presented to the customer will include the cost of signature confirmation for all packages in the order.'),
      '#disabled' => TRUE,
    );

    // Container for markup forms
    $form['uc_usps_markups'] = array(
      '#type'          => 'details',
      '#title'         => t('Markups'),
      '#description'   => t('Modifiers to the shipping weight and quoted rate.'),
      '#group'         => 'usps-settings',
    );

    $form['uc_usps_markups']['uc_usps_rate_markup_type'] = array(
      '#type' => 'select',
      '#title' => t('Rate markup type'),
      '#default_value' => $usps_config->get('rate_markup_type'),
      '#options' => array(
        'percentage' => t('Percentage (%)'),
        'multiplier' => t('Multiplier (×)'),
        'currency' => t('Addition (!currency)', array('!currency' => \Drupal::config('uc_store.settings')->get('currency.symbol'))),
      ),
    );
    $form['uc_usps_markups']['uc_usps_rate_markup'] = array(
      '#type' => 'textfield',
      '#title' => t('Shipping rate markup'),
      '#default_value' => $usps_config->get('rate_markup'),
      '#description' => t('Markup shipping rate quote by dollar amount, percentage, or multiplier.'),
    );

    // Form to select type of weight markup
    $form['uc_usps_markups']['uc_usps_weight_markup_type'] = array(
      '#type'          => 'select',
      '#title'         => t('Weight markup type'),
      '#default_value' => $usps_config->get('weight_markup_type'),
      '#options'       => array(
        'percentage' => t('Percentage (%)'),
        'multiplier' => t('Multiplier (×)'),
        'mass'       => t('Addition (!mass)', array('!mass' => '#')),
      ),
      '#disabled' => TRUE,
    );

    // Form to select weight markup amount
    $form['uc_usps_markups']['uc_usps_weight_markup'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Shipping weight markup'),
      //'#default_value' => $usps_config->get('weight_markup'),
      '#default_value' => 0,
      '#description'   => t('Markup shipping weight on a per-package basis before quote, by weight amount, percentage, or multiplier.'),
      '#disabled' => TRUE,
    );

    // Taken from system_settings_form(). Only, don't use its submit handler.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    );
    $form['actions']['cancel'] = array(
      '#markup' => l(t('Cancel'), 'admin/store/settings/quotes'),
    );

    if (!empty($_POST) && form_get_errors()) {
      drupal_set_message(t('The settings have not been saved because of the errors.'), 'error');
    }
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'system_settings_form';
    }

    return parent::buildForm($form, $form_state);
  }


  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('uc_usps_rate_markup'))) {
      $form_state->setErrorByName('uc_usps_rate_markup', t('Rate markup must be a numeric value.'));
    }
    if (!is_numeric($form_state->getValue('uc_usps_weight_markup'))) {
      $form_state->setErrorByName('uc_usps_weight_markup', t('Weight markup must be a numeric value.'));
    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $usps_config = $this->config('uc_usps.settings');

    $values = $form_state->getValues();
    $usps_config
      ->set('user_id', $values['uc_usps_user_id'])
      ->set('online_rates', $values['uc_usps_online_rates'])
      ->set('env_services', $values['uc_usps_env_services'])
      ->set('services', $values['uc_usps_services'])
      ->set('intl_env_services', $values['uc_usps_intl_env_services'])
      ->set('intl_services', $values['uc_usps_intl_services'])
      ->set('rate_markup_type', $values['uc_usps_rate_markup_type'])
      ->set('rate_markup', $values['uc_usps_rate_markup'])
      ->set('weight_markup_type', $values['uc_usps_weight_markup_type'])
      ->set('weight_markup', $values['uc_usps_weight_markup'])
      ->set('all_in_one', $values['uc_usps_all_in_one'])
      ->set('insurance', $values['uc_usps_insurance'])
      ->set('delivery_confirmation', $values['uc_usps_delivery_confirmation'])
      ->set('signature_confirmation', $values['uc_usps_signature_confirmation'])
      ->save();

    drupal_set_message(t('The configuration options have been saved.'));

    // @todo: Still need these two lines?
    //cache_clear_all();
    //drupal_theme_rebuild();

    parent::submitForm($form, $form_state);
  }

}
