<?php

/**
 * @file
 * Definition of Drupal\uc_order\UcOrderProductRenderController.
 */

namespace Drupal\uc_order;

use Drupal\Core\Entity\EntityRenderController;

/**
 * Render controller for ordered products.
 */
class UcOrderProductRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $product) {
      $product->content['qty'] = array(
        '#theme' => 'uc_qty',
        '#qty' => $product->qty->value,
        '#cell_attributes' => array('class' => array('qty')),
      );
      $node = node_load($product->nid->value);
      $title = node_access('view', $node) ? l($product->title->value, 'node/' . $product->nid->value) : check_plain($product->title->value);
      $product->content['product'] = array(
        '#markup' => $title . uc_product_get_description($product),
        '#cell_attributes' => array('class' => array('product')),
      );
      $product->content['model'] = array(
        '#markup' => check_plain($product->model->value),
        '#cell_attributes' => array('class' => array('sku')),
      );
      if (user_access('administer products')) {
        $product->content['cost'] = array(
          '#theme' => 'uc_price',
          '#price' => $product->cost->value,
          '#cell_attributes' => array('class' => array('cost')),
        );
      }
      $product->content['price'] = array(
        '#theme' => 'uc_price',
        '#price' => $product->price->value,
        '#suffixes' => array(),
        '#cell_attributes' => array('class' => array('price')),
      );
      $product->content['total'] = array(
        '#theme' => 'uc_price',
        '#price' => $product->price->value * $product->qty->value,
        '#suffixes' => array(),
        '#cell_attributes' => array('class' => array('total')),
      );
    }
  }
}
