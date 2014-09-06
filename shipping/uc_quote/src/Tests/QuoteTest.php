<?php

/**
 * @file
 * Contains \Drupal\uc_quote\Tests\QuoteTest.
 */

namespace Drupal\uc_quote\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests shipping quote functionality.
 *
 * @group Ubercart
 */
class QuoteTest extends UbercartTestBase {

  public static $modules = array(/*'rules_admin', */'uc_payment', 'uc_payment_pack', 'uc_quote', 'uc_flatrate');
  public static $adminPermissions = array('configure quotes'/*, 'administer rules', 'bypass rules access'*/);

  public function setUp() {
    parent::setUp();
    module_load_include('inc', 'uc_flatrate', 'uc_flatrate.admin');
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Creates a flat rate shipping quote with optional conditions.
   *
   * @param $edit
   *   Data to use to create shipping quote, same format as the values
   *   submitted from the add flatrate method form.
   * @param $condition
   *   If specified, a RulesAnd component defining the conditions to apply
   *   for this method.
   */
  protected function createQuote($edit = array(), $condition = FALSE) {
    $edit += array(
      'title' => $this->randomMachineName(8),
      'label' => $this->randomMachineName(8),
      'base_rate' => mt_rand(1, 10),
      'product_rate' => mt_rand(1, 10),
    );
    $this->drupalPostForm('admin/store/settings/quotes/methods/flatrate/add', $edit, 'Submit');
    $method = db_query("SELECT * FROM {uc_flatrate_methods} ORDER BY mid DESC LIMIT 1")->fetchObject();
    // if ($condition) {
    //   $name = 'get_quote_from_flatrate_' . $method->mid;
    //   $condition['LABEL'] = $edit['label'] . ' conditions';
    //   $oldconfig = rules_config_load($name);
    //   $newconfig = rules_import(array($name => $condition));
    //   $newconfig->id = $oldconfig->id;
    //   unset($newconfig->is_new);
    //   $newconfig->status = ENTITY_CUSTOM;
    //   $newconfig->save();
    //   entity_flush_caches();
    // }
    $this->assertTrue($method->base_rate == $edit['base_rate'], 'Flatrate quote was created successfully');
    return $method;
  }

  /**
   * Simulates selection of a delivery country on the checkout page.
   *
   * @param $country
   *   The text version of the country name to select, e.g. "Canada" or
   *   "United States".
   */
  protected function selectCountry($country = "Canada") {
    $dom = new \DOMDocument();
    $dom->loadHTML($this->content);
    $parent = $dom->getElementById('edit-panes-delivery-delivery-country');
    $options = $parent->getElementsByTagName('option');
    for ($i = 0; $i < $options->length; $i++) {
      if ($options->item($i)->textContent == $country) {
        $options->item($i)->setAttribute('selected', 'selected');
      }
      else {
        $options->item($i)->removeAttribute('selected');
      }
    }
    $this->drupalSetContent($dom->saveHTML());
    return $this->drupalPostAjaxForm(NULL, array(), 'panes[delivery][country]');
  }

  /**
   * Simulates selection of a quote on the checkout page.
   *
   * @param $n
   *   The index of the quote to select.
   */
  protected function selectQuote($n) {
    // Get the list of available quotes.
    $xpath = '//*[@name="panes[quotes][quotes][quote_option]"]';
    $elements = $this->xpath($xpath);
    $vals = array();
    foreach ($elements as $element) {
      $vals[(string) $element['id']] = (string) $element['value'];
    }

    // Set the checked attribute of the chosen quote.
    $dom = new \DOMDocument();
    $dom->loadHTML($this->content);
    $i = 0;
    $selected = '';
    foreach ($vals as $id => $value) {
      if ($i == $n) {
        $dom->getElementById($id)->setAttribute('checked', 'checked');
        $selected = $value;
      }
      else {
        $dom->getElementById($id)->removeAttribute('checked');
      }
      $i++;
    }
    $this->drupalSetContent($dom->saveHTML());

    // Post the selection via Ajax.
    $option = array('panes[quotes][quotes][quote_option]' => $selected);
    return $this->drupalPostAjaxForm(NULL, array(), $option);
  }

  /**
   * Verifies shipping pane is hidden when there are no shippable items.
   */
  public function testNoQuote() {
    $product = $this->createProduct(array('shippable' => FALSE));
    $quote = $this->createQuote();
    $this->addToCart($product);
    $this->drupalPostForm('cart', array('items[0][qty]' => 1), t('Checkout'));
    $this->assertNoText('Calculate shipping cost', 'Shipping pane is not present with no shippable item.');
  }

  /**
   * Tests basic flatrate shipping quote functionality.
   */
  public function testQuote() {
    // Create product and quotes.
    $product = $this->createProduct();
    $quote1 = $this->createQuote();
    $quote2 = $this->createQuote(array(), array(
      'LABEL' => 'quote_conditions',
      'PLUGIN' => 'and',
      'REQUIRES' => array('rules'),
      'USES VARIABLES' => array(
        'order' => array(
          'type' => 'uc_order',
          'label' => 'Order'
        ),
      ),
      'AND' => array( array(
        'data_is' => array(
          'data' => array('order:delivery-address:country'),
          'value' => '840',
        ),
      )),
    ));
    // Define strings to test for.
    $qty = mt_rand(2, 100);
    foreach (array($quote1, $quote2) as $quote) {
      $quote->amount = uc_currency_format($quote->base_rate + $quote->product_rate * $qty);
      $quote->option_text = $quote->label . ': ' . $quote->amount;
      $quote->total = uc_currency_format($product->price->value * $qty + $quote->base_rate + $quote->product_rate * $qty);
    }

    // Add product to cart, update qty, and go to checkout page.
    $this->addToCart($product);
    $this->drupalPostForm('cart', array('items[0][qty]' => $qty), t('Checkout'));
    $this->assertText($quote1->option_text, 'The default quote option is available');
    $this->assertText($quote2->option_text, 'The second quote option is available');
    $this->assertText($quote1->total, 'Order total includes the default quote.');

    // Select a different quote and ensure the total updates correctly.  Currently, we have to do this
    // by examining the ajax return value directly (rather than the page contents) because drupalPostAjaxForm() can
    // only handle replacements via the 'wrapper' property, and the ajax callback may use a command with a selector.
    $edit = array('panes[quotes][quotes][quote_option]' => 'flatrate_2---0');
    $edit = $this->populateCheckoutForm($edit);
    $result = $this->ucPostAjax(NULL, $edit, $edit);
    $this->assertText($quote2->total, 'The order total includes the selected quote.');

    // @todo Re-enable when shipping quote conditions are available.
    // Switch to a different country and ensure the ajax updates the page correctly.
    // $edit['panes[delivery][country]'] = 124;
    // $result = $this->ucPostAjax(NULL, $edit, 'panes[delivery][country]');
    // $this->assertText($quote1->option_text, 'The default quote is still available after changing the country.');
    // $this->assertNoText($quote2->option_text, 'The second quote is no longer available after changing the country.');
    // $this->assertText($quote1->total, 'The total includes the default quote.');

    // Proceed to review page and ensure the correct quote is present.
    $edit['panes[quotes][quotes][quote_option]'] = 'flatrate_1---0';
    $edit = $this->populateCheckoutForm($edit);
    $this->drupalPostForm(NULL, $edit, t('Review order'));
    $this->assertRaw(t('Your order is almost complete.'));
    $this->assertText($quote1->total, 'The total is correct on the order review page.');

    // Submit the review.
    $this->drupalPostForm(NULL, array(), t('Submit order'));
    $order_id = db_query("SELECT order_id FROM {uc_orders} WHERE delivery_first_name = :name", array(':name' => $edit['panes[delivery][first_name]']))->fetchField();
    if ($order_id) {
      $order = uc_order_load($order_id);
      foreach ($order->line_items as $line) {
        if ($line['type'] == 'shipping') {
          break;
        }
      }
      // Verify line item is correct.
      $this->assertEqual($line['type'], 'shipping', t('The shipping line item was saved to the order.'));
      $this->assertEqual($quote1->amount, uc_currency_format($line['amount']), t('Stored shipping line item has the correct amount.'));

      // Verify order total is correct on order-view form.
      $this->drupalGet('admin/store/orders/' . $order_id);
      $this->assertText($quote1->total, 'The total is correct on the order admin view page.');

      // Verify shipping line item is correct on order edit form.
      $this->drupalGet('admin/store/orders/' . $order_id . '/edit');
      $this->assertFieldByName('line_items[' . $line['line_item_id'] . '][title]', $quote1->label, t('Found the correct shipping line item title.'));
      $this->assertFieldByName('line_items[' . $line['line_item_id'] . '][amount]', substr($quote1->amount, 1), t('Found the correct shipping line item title.'));

      // Verify that the "get quotes" button works as expected.
      $result = $this->ucPostAjax('admin/store/orders/' . $order_id . '/edit', array(), array('op' => t('Get shipping quotes')));
      $this->assertText($quote1->option_text, 'The default quote is available on the order-edit page.');
      // @todo Change to assertNoText when shipping quote conditions are available.
      $this->assertText($quote2->option_text, 'The second quote is available on the order-edit page.');
    }
    else {
      $this->fail('No order was created.');
    }

  }
}
