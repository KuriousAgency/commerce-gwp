<?php
/**
 * GWP plugin for Craft CMS 3.x
 *
 * Commerce GWP plugin for Craft CMS
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\commerce\gwp;

use kuriousagency\commerce\gwp\services\GwpService;
use kuriousagency\commerce\gwp\variables\GwpVariable;
use kuriousagency\commerce\gwp\adjusters\Discount;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\services\Elements;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\commerce\services\OrderAdjustments;
use craft\commerce\Plugin as Commerce;

use craft\commerce\elements\Order;

use yii\base\Event;

/**
 * Class Gwp
 *
 * @author    Kurious Agency
 * @package   GWP
 * @since     1.0.0
 *
 * @property  GwpService $GwpService
 */
class Gwp extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Gwp
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
		self::$plugin = $this;
		
		$this->setComponents([
            'service' => GwpService::class
        ]);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'ommerce-gwp/default';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['commerce-gwp'] = 'commerce-gwp/default/index';
                $event->rules['commerce-gwp/new'] = 'commerce-gwp/default/edit';
                $event->rules['commerce-gwp/<id:\d+>'] = 'commerce-gwp/default/edit';
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('gwp', GwpVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
		);
		
		// Event::on(OrderAdjustments::class, OrderAdjustments::EVENT_REGISTER_ORDER_ADJUSTERS, function(RegisterComponentTypesEvent $event) {
		// 	$event->types[] = Discount::class;
		// });

		Event::on(Elements::class,Elements::EVENT_BEFORE_SAVE_ELEMENT,function(Event $e) {
			
			if ($e->element instanceof Order) {

				$order = $e->element;
				$customerChoiceGifts = [];

				// save customer selected free gifts so we can add them with the other free gifts
				foreach($order->lineItems as $lineItem) {
					if(array_key_exists('gwpCustomerChoice',$lineItem->options)) {
						$customerChoiceGifts[] = $lineItem->purchasableId;
					}
				}

				// Craft::dd($customerChoiceGifts);

				if(($order->isCompleted == false) && ($order->id)) {

					// delete all promotion items and then reapply
					foreach($order->lineItems as $lineItem) {
						if(array_key_exists('promotionalItem',$lineItem->options)) {
							$order->removeLineItem($lineItem);
						}
					}

					// get matched gifts
					$freeGifts = $customerChoiceGifts;
					$gifts = Gwp::$plugin->service->getGiftsForOrder($order);

					foreach($gifts as $gift) {
						if(!$gift->customerChoice) {
							$freeGifts = array_merge($freeGifts,$gift->getProductPurchasableIds());
						}
					}

					$freeGifts = array_count_values($freeGifts);

					if($gifts && $freeGifts) {

						foreach($freeGifts as $giftId=>$qty) {
							$options = [];
							$options['promotionalItem'] = true;
							if(in_array($giftId,$customerChoiceGifts)) {
								$options['gwpCustomerChoice'] = true;
							}
							$lineItem = Commerce::getInstance()->getLineItems()->resolveLineItem($order->id,$giftId,$options);
							$lineItem->qty = $qty;
							$order->addLineItem($lineItem);
						}
					}

				}
			}
		});

		Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, function(Event $e) {
			
			$order = $e->sender;

			$gifts = Gwp::$plugin->service->getGiftsForOrder($order);

			// Craft::dd($gifts);

			foreach($gifts as $gift) {
				Gwp::$plugin->service->orderCompleteHandler($order,$gift);
			}
			
		});

        Craft::info(
            Craft::t(
                'commerce-gwp',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

}
