<?php
/**
 * GWP plugin for Craft CMS 3.x
 *
 * Commerce GWP plugin for Craft CMS
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\commerce\gwp\variables;

use kuriousagency\commerce\gwp\Gwp;

use Craft;

/**
 * @author    Kurious Agency
 * @package   GWP
 * @since     1.0.0
 */
class GwpVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null $optional
     * @return string
     */
    public function getCustomerChoiceGifts($order)
    {
		$gifts = Gwp::$plugin->service->getCustomerChoiceGiftsForOrder($order);

		return $gifts;
		
	}

	public function getPurchasableElements($ids)
	{
		
		$elements = [];
		
		foreach($ids as $id) {
			$elements[] = Craft::$app->getElements()->getElementById($id);
			// $type::find()->id($id)->one();
		}
		
		return $elements;


	}
	
}
