<?php
/**
 * GWP plugin for Craft CMS 3.x
 *
 * Commerce GWP plugin for Craft CMS
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\commerce\gwp\controllers;

use kuriousagency\commerce\gwp\Gwp;

use craft\commerce\Plugin as Commerce;

use Craft;
use craft\web\Controller;

/**
 * @author    Kurious Agency
 * @package   GWP
 * @since     1.0.0
 */
class CartController extends Controller
{

	// Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['add-to-cart'];
	
	public function actionAddToCart() {

		$order = Commerce::getInstance()->getCarts()->getCart(true);
		
		$lineItems = $order->getLineItems();
		foreach ($lineItems as $key => $item)
		{
		
			foreach($order->lineItems as $lineItem) {
				if(array_key_exists('gwpCustomerChoice',$lineItem->options)) {
					$order->removeLineItem($lineItem);
				}
			}
			
		}

		$this->run('/commerce/cart/update-cart');

	}

}