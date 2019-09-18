<?php
/**
 * GWP plugin for Craft CMS 3.x
 *
 * Commerce GWP plugin for Craft CMS
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\commerce\gwp\services;

use kuriousagency\commerce\gwp\Gwp;

use Craft;

use craft\commerce\elements\Order;
use kuriousagency\commerce\gwp\models\Gift;
use kuriousagency\commerce\gwp\records\Gift as GiftRecord;
use kuriousagency\commerce\gwp\records\ConditionCategory as ConditionCategoryRecord;
use kuriousagency\commerce\gwp\records\ConditionPurchasable as ConditionPurchasableRecord;
use kuriousagency\commerce\gwp\records\ConditionUserGroup as ConditionUserGroupRecord;
use kuriousagency\commerce\gwp\records\ProductPurchasable as ProductPurchasableRecord;
use kuriousagency\commerce\gwp\records\CustomerGiftUse as CustomerGiftUseRecord;
use kuriousagency\commerce\gwp\records\EmailGiftUse as EmailGiftUseRecord;


use craft\db\Query;
use craft\elements\Category;
use craft\commerce\Plugin as Commerce;
use craft\commerce\models\LineItem;
use craft\commerce\base\Purchasable;
use craft\commerce\base\PurchasableInterface;
use DateTime;
use yii\base\Component;
use yii\base\Exception;
use yii\db\Expression;
use function in_array;

/**
 * @author    Kurious Agency
 * @package   GWP
 * @since     1.0.0
 */
class GwpService extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var Gift[]
     */
	private $_allGifts;
	private $_gift;
	private $_order;
	

    // Public Methods
    // =========================================================================

    /**
     * Get a gift by its ID.
     *
     * @param int $id
     * @return Gift|null
     */
    public function getGiftById($id)
    {
        foreach ($this->getAllGifts() as $gift) {
            if ($gift->id == $id) {
                return $gift;
            }
        }

        return null;
    }

    /**
     * Get all gifts.
     *
     * @return Gift[]
     */
    public function getAllGifts(): array
    {
        if (null === $this->_allGifts) {
            $gifts = $this->_createGiftQuery()
                ->addSelect([
                    'ap.purchasableId',
                    'apt.categoryId',
					'aug.userGroupId',
					'app.purchasableId as productPurchasableId',
                ])
                ->leftJoin('{{%gwp_condition_purchasables}} ap', '[[ap.giftId]]=[[gifts.id]]')
                ->leftJoin('{{%gwp_condition_categories}} apt', '[[apt.giftId]]=[[gifts.id]]')
				->leftJoin('{{%gwp_condition_usergroups}} aug', '[[aug.giftId]]=[[gifts.id]]')
				->leftJoin('{{%gwp_product_purchasables}} app', '[[app.giftId]]=[[gifts.id]]')
                ->all();

            $allGiftsById = [];
            $purchasables = [];
            $categories = [];
			$userGroups = [];
			$productPurchasables = [];
        	$productCategories = [];

            foreach ($gifts as $gift) {
                $id = $gift['id'];
                if ($gift['purchasableId']) {
                    $purchasables[$id][] = $gift['purchasableId'];
                }

                if ($gift['categoryId']) {
                    $categories[$id][] = $gift['categoryId'];
                }

                if ($gift['userGroupId']) {
                    $userGroups[$id][] = $gift['userGroupId'];
				}
				
				if ($gift['productPurchasableId']) {
					$productPurchasables[$id][] = $gift['productPurchasableId'];
				}

                unset($gift['purchasableId'], $gift['userGroupId'], $gift['categoryId'], $gift['productPurchasableId']);

                if (!isset($allGiftsById[$id])) {
                    $allGiftsById[$id] = new Gift($gift);
                }
            }

            foreach ($allGiftsById as $id => $gift) {
                $gift->setPurchasableIds($purchasables[$id] ?? []);
                $gift->setCategoryIds($categories[$id] ?? []);
				$gift->setUserGroupIds($userGroups[$id] ?? []);
				$gift->setProductPurchasableIds($productPurchasables[$id] ?? []);
            }

            $this->_allGifts = $allGiftsById;
        }

        return $this->_allGifts;
    }

    /**
     * Populates a gift's relations.
     *
     * @param Gift $gift
     */
    public function populateGiftRelations(Gift $gift)
    {
        $rows = (new Query())->select(
            'ap.purchasableId,
            apt.categoryId,
			aug.userGroupId,
			app.purchasableId as productPurchasableId')
            ->from('{{%gwp_gifts}} gifts')
            ->leftJoin('{{%gwp_condition_purchasables}} ap', '[[ap.giftId]]=[[gifts.id]]')
            ->leftJoin('{{%gwp_condition_categories}} apt', '[[apt.giftId]]=[[gifts.id]]')
			->leftJoin('{{%gwp_condition_usergroups}} aug', '[[aug.giftId]]=[[gifts.id]]')
			->leftJoin('{{%gwp_product_purchasables}} app', '[[app.giftId]]=[[gifts.id]]')
			->where(['gifts.id' => $gift->id])
			->orderBy('app.id')
            ->all();

        $purchasableIds = [];
        $categoryIds = [];
		$userGroupIds = [];
		$productPurchasableIds = [];

        foreach ($rows as $row) {
            if ($row['purchasableId']) {
                $purchasableIds[] = $row['purchasableId'];
            }

            if ($row['categoryId']) {
                $categoryIds[] = $row['categoryId'];
            }

            if ($row['userGroupId']) {
                $userGroupIds[] = $row['userGroupId'];
			}

			if ($row['productPurchasableId']) {
                $productPurchasableIds[] = $row['productPurchasableId'];
            }
        }

        $gift->setPurchasableIds($purchasableIds);
        $gift->setCategoryIds($categoryIds);
		$gift->setUserGroupIds($userGroupIds);
		$gift->setProductPurchasableIds($productPurchasableIds);
	}
	
	  /**
     * Is gift coupon available to the order
     *
     * @param Order $order
     * @param string|null $explanation
     * @return bool
     */
    public function orderGiftAvailable(Order $order, $gift, string &$explanation = null): bool
    {

        if (!$gift) {
            $explanation = Craft::t('commerce-gwp', 'Gift not valid');
            return false;
        }

        $customer = $order->getCustomer();
        $user = $customer ? $customer->getUser() : null;

        if ($gift->totalUseLimit > 0 && $gift->totalUses >= $gift->totalUseLimit) {
            $explanation = Craft::t('commerce-gwp', 'Gift use has reached its limit');
            return false;
        }

        $now = $order->dateUpdated ?? new DateTime();
        $from = $gift->dateFrom;
        $to = $gift->dateTo;
        if (($from && $from > $now) || ($to && $to < $now)) {
            $explanation = Craft::t('commerce-gwp', 'Gift is out of date');

            return false;
        }

        if (!$gift->allGroups) {
            $groupIds = $user ? Commerce::getInstance()->getCustomers()->getUserGroupIdsForUser($user) : [];
            if (empty(array_intersect($groupIds, $gift->getUserGroupIds()))) {
                $explanation = Craft::t('commerce-gwp', 'Gift is not allowed for the customer');

                return false;
            }
        }

        if ($gift->perUserLimit > 0 && !$user) {
            $explanation = Craft::t('commerce-gwp', 'Gift is limited to use by registered users only.');

            return false;
        }

        if ($gift->perUserLimit > 0 && $user) {
            // The 'Per User Limit' can only be tracked against logged in users since guest customers are re-generated often
            $usage = (new Query())
                ->select(['uses'])
                ->from(['{{%gwp_customer_giftuses}}'])
                ->where(['customerId' => $customer->id, 'giftId' => $gift->id])
                ->scalar();

            if ($usage && $usage >= $gift->perUserLimit) {
                $explanation = Craft::t('commerce-gwp', 'This gift limited to {limit} uses.', [
                    'limit' => $gift->perUserLimit,
                ]);

                return false;
            }
        }

        if ($gift->perEmailLimit > 0 && $order->getEmail()) {
            $usage = (new Query())
                ->select(['uses'])
                ->from(['{{%gwp_email_giftuses}}'])
                ->where(['email' => $order->getEmail(), 'giftId' => $gift->id])
                ->scalar();

            if ($usage && $usage >= $gift->perEmailLimit) {
                $explanation = Craft::t('commerce-gwp', 'This gift limited to {limit} uses.', [
                    'limit' => $gift->perEmailLimit,
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * Match a line item against a gift.
     *
     * @param LineItem $lineItem
     * @param Gift $gift
     * @return bool
     */
    public function matchLineItem(LineItem $lineItem, Gift $gift): bool
    {
        if (!$this->matchOrder($lineItem->order, $gift)) {
            return false;
        }

        // if ($lineItem->onSale && $gift->excludeOnSale) {
        //     return false;
        // }

        // // can't match something not promotable
        // if (!$lineItem->purchasable->getIsPromotable()) {
        //     return false;
        // }

        if ($gift->getPurchasableIds() && !$gift->allPurchasables) {
            $purchasableId = $lineItem->purchasableId;
            if (!in_array($purchasableId, $gift->getPurchasableIds(), true)) {
                return false;
            }
        }

        if ($gift->getCategoryIds() && !$gift->allCategories && $lineItem->getPurchasable()) {
            $purchasable = $lineItem->getPurchasable();

            if (!$purchasable) {
                return false;
            }

            $relatedTo = ['sourceElement' => $purchasable->getPromotionRelationSource()];
			$relatedCategories = Category::find()->relatedTo($relatedTo)->ids();
			$purchasableIsRelateToOneOrMoreCategories = (bool)array_intersect($relatedCategories, $gift->getCategoryIds());
            if (!$purchasableIsRelateToOneOrMoreCategories) {
                return false;
            }
		}
		
		// fail safe to make sure
		if(!$gift->getCategoryIds() && !$gift->getPurchasableIds()) {
			return false;
		}

		return true;
	}

    /**
     * @param Order $order
     * @param Gift $gift
     * @return bool
     */
	public function matchOrder(Order $order, Gift $gift): bool
    {
		
		$explanation = '';
		
		// If the gift is no longer enabled don't use
        if (!$gift->enabled) {
            return false;
		}

		// Only use the gift if it is still available (it may have expired since being valid on the order)
		if ($this->orderGiftAvailable($order, $gift, $explanation)) {
			return true;
		} 

        return false;
    }
	
	// public function getGiftsByPurchasableId(PurchasableInterface $purchasable, $currency)
	// {
		
	// 	$addonCategories = [];
	// 	$addonProducts = [];
	// 	$addOnPurchasableTypes = [];
	// 	$addonElements = [];
	// 	$purchaseableElements = [];
	// 	$addOnPurchasables = [];
	// 	$giftValues = [];
		
	// 	// get product and purchasable ids
	// 	$relatedTo = ['sourceElement' => $purchasable->getPromotionRelationSource()];
	// 	$relatedCategories = Category::find()->relatedTo($relatedTo)->ids();

	// 	// get gift purchasables related to purchasable categories
	// 	$productCategories = $this->_createGiftQuery()
	// 		->select([
	// 			'gifts.percentGift',
	// 			'gifts.perItemGift',
	// 			'apc.categoryId',
	// 			'app.purchasableId',
	// 			'app.purchasableType'
	// 		])	
	// 		->innerjoin('{{%gwp_condition_categories}} acc', '[[acc.giftId]]=[[gifts.id]]')	
	// 		->innerjoin('{{%gwp_product_purchasables}} app', '[[app.giftId]]=[[gifts.id]]')
	// 		->where(['in', 'acc.categoryId', $relatedCategories])
	// 		->orderBy('app.id')
	// 		->all();


	// 	// Craft::dd($productCategories);

	// 	foreach($productCategories as $category) {

	// 		$giftValue = $this->getFormattedGiftValue($purchasable['perItemGift'],$purchasable['percentGift'],$currency);

	// 		$addonCategories[] = $category['categoryId'];
	// 		$addonProducts[$category['purchasableType']][] = $category['purchasableId'];
			
	// 		$giftValues[$category['purchasableId']] = $giftValue;
	// 		$giftValues[$category['categoryId']] = $giftValue;
	// 	}

	// 	// get gift purchasables related to purchasableId
	// 	$productPurchasables = $this->_createGiftQuery()
	// 		->select([
	// 			'gifts.percentGift',
	// 			'gifts.perItemGift',
	// 			'apc.categoryId',
	// 			'app.purchasableId',
	// 			'app.purchasableType'
	// 		])	
	// 		->innerjoin('{{%gwp_condition_purchasables}} ap', '[[ap.giftId]]=[[gifts.id]]')	
	// 		->innerjoin('{{%gwp_product_purchasables}} app', '[[app.giftId]]=[[gifts.id]]')
	// 		->where(['ap.purchasableId' => $purchasable->id])
	// 		->orderBy('app.id')
	// 		->all();

	// 	foreach($productPurchasables as $purchasable) {

	// 		$giftValue = $this->getFormattedGiftValue($purchasable['perItemGift'],$purchasable['percentGift'],$currency);

	// 		$addonCategories[] = $purchasable['categoryId'];
	// 		$addonProducts[$purchasable['purchasableType']][] = $purchasable['purchasableId'];

	// 		$giftValues[$purchasable['purchasableId']] = $giftValue;
	// 		$giftValues[$purchasable['categoryId']] = $giftValue;
	// 	}

	// 	// get purchasable elements
	// 	foreach($addonProducts as $type => $products) {
	// 		$addOnPurchasables[$type] = array_unique($products);
	// 	}
		
	// 	foreach($addOnPurchasables as $type=>$addOnPurchasable) {
	// 		$purchaseableElements = array_merge($purchaseableElements,$type::find()->id($addOnPurchasable)->fixedOrder(true)->all());
	// 	}

	// 	$addOnCategories = Category::find()->id($addonCategories)->all();

	// 	$allElements = array_merge($purchaseableElements,$addOnCategories);

	// 	// Craft::dd($giftValues);

	// 	foreach($allElements as $element) {
	// 		$type = explode("\\",get_class($element));
	// 		$type = strtolower(end($type));

	// 		$addonElements[] = ['type'=>$type,'element' => $element,'gift'=>$giftValues[$element->id]];
	// 	}

	// 	return $addonElements;
	// }

    /**
     * Save a gift.
     *
     * @param Gift $model the gift being saved
     * @param bool $runValidation should we validate this gift before saving.
     * @return bool
     * @throws \Exception
     */
    public function saveGift(Gift $model, bool $runValidation = true): bool
    {
        if ($model->id) {
            $record = GiftRecord::findOne($model->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce-gwp', 'No gift exists with the ID “{id}”', ['id' => $model->id]));
            }
        } else {
            $record = new GiftRecord();
        }

        if ($runValidation && !$model->validate()) {
            Craft::info('Gift not saved due to validation error.', __METHOD__);

            return false;
		}

        $record->name = $model->name;
        $record->description = $model->description;
        $record->dateFrom = $model->dateFrom;
        $record->dateTo = $model->dateTo;
		$record->enabled = $model->enabled;
		$record->perUserLimit = $model->perUserLimit;
		$record->perEmailLimit = $model->perEmailLimit;
		$record->totalUseLimit = $model->totalUseLimit;
		$record->purchaseAll = $model->purchaseAll;
        $record->purchaseTotal = $model->purchaseTotal;
        $record->purchaseQty = $model->purchaseQty;
        $record->maxPurchaseQty = $model->maxPurchaseQty;
		$record->customerChoice = $model->customerChoice;
		$record->maxCustomerChoice = $model->maxCustomerChoice;
        $record->sortOrder = $record->sortOrder ?: 999;
        $record->allGroups = $model->allGroups = empty($model->getUserGroupIds());
        $record->allCategories = $model->allCategories = empty($model->getCategoryIds());
		$record->allPurchasables = $model->allPurchasables = empty($model->getPurchasableIds());
		
        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $record->save(false);
            $model->id = $record->id;

            ConditionUserGroupRecord::deleteAll(['giftId' => $model->id]);
            ConditionPurchasableRecord::deleteAll(['giftId' => $model->id]);
			ConditionCategoryRecord::deleteAll(['giftId' => $model->id]);
			ProductPurchasableRecord::deleteAll(['giftId' => $model->id]);

            foreach ($model->getUserGroupIds() as $groupId) {
                $relation = new ConditionUserGroupRecord;
                $relation->userGroupId = $groupId;
                $relation->giftId = $model->id;
                $relation->save(false);
            }

            foreach ($model->getCategoryIds() as $categoryId) {
                $relation = new ConditionCategoryRecord();
                $relation->categoryId = $categoryId;
                $relation->giftId = $model->id;
                $relation->save(false);
            }

            foreach ($model->getPurchasableIds() as $purchasableId) {
                $relation = new ConditionPurchasableRecord();
                $element = Craft::$app->getElements()->getElementById($purchasableId);
                $relation->purchasableType = get_class($element);
                $relation->purchasableId = $purchasableId;
                $relation->giftId = $model->id;
                $relation->save(false);
			}

            foreach ($model->getProductPurchasableIds() as $purchasableId) {
                $relation = new ProductPurchasableRecord();
                $element = Craft::$app->getElements()->getElementById($purchasableId);
                $relation->purchasableType = get_class($element);
                $relation->purchasableId = $purchasableId;
                $relation->giftId = $model->id;
                $relation->save(false);
            }

            $transaction->commit();

            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Delete a gift by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteGiftById($id): bool
    {
        $record = GiftRecord::findOne($id);

        if ($record) {
            return $record->delete();
        }

        return false;
    }

    /**
     * Clears a coupon's usage history.
     *
     * @param int $id the coupon's ID
     */
    public function clearGwpUsageHistoryById(int $id)
    {
        $db = Craft::$app->getDb();

        $db->createCommand()
            ->delete('{{%gwp_customer_giftuses}}', ['giftId' => $id])
            ->execute();

        $db->createCommand()
            ->delete('{{%gwp_email_giftuses}}', ['giftId' => $id])
            ->execute();

        $db->createCommand()
            ->update('{{%gwp_gifts}}', ['totalUses' => 0], ['id' => $id])
            ->execute();
    }

    /**
     * Reorder gifts by an array of ids.
     *
     * @param array $ids
     * @return bool
     */
    public function reorderGifts(array $ids): bool
    {
        foreach ($ids as $sortOrder => $id) {
            Craft::$app->getDb()->createCommand()
                ->update('{{%gwp_gifts}}', ['sortOrder' => $sortOrder + 1], ['id' => $id])
                ->execute();
        }

        return true;
	}

	public function getGiftsForOrder(Order $order): array
    {
		$this->_order = $order;
        $gifts = [];
        $availableGifts = [];

        foreach ($this->getAllGifts() as $gift) {
            if ($this->matchOrder($order, $gift)) {
                $availableGifts[] = $gift;
            }
		}

        foreach ($availableGifts as $gift) {

			if($matchedGift = $this->_matchGift($order,$gift)) {
				$gifts[] = $matchedGift;
			}
			
		}

        return $gifts;
	}
	
	public function getCustomerChoiceGiftsForOrder(Order $order): array
    {
		$this->_order = $order;
        $gifts = [];
        $availableGifts = [];

        foreach ($this->getAllGifts() as $gift) {
            if ($this->matchOrder($order, $gift)) {
                $availableGifts[] = $gift;
            }
		}

        foreach ($availableGifts as $gift) {
			if($gift->customerChoice) {
				if( $matchedGift = $this->_matchGift($order,$gift)) {
					$gifts[] = $matchedGift;
				}
			}
		}

        return $gifts;
    }
	

	private function _matchGift($order,$gift)
	{

		$this->_gift = $gift;

        $now = new DateTime();
        $from = $this->_gift->dateFrom;
        $to = $this->_gift->dateTo;
        if (($from && $from > $now) || ($to && $to < $now)) {
            return false;
		}

        //checking items that match the conditions
        $matchingQty = 0;
        $matchingTotal = 0;
        $matchingLineIds = [];
        foreach ($this->_order->getLineItems() as $item) {
            $lineItemHashId = spl_object_hash($item);
            if ($this->matchLineItem($item, $this->_gift)) {
                if (!$this->_gift->allGroups) {
                    $customer = $this->_order->getCustomer();
                    $user = $customer ? $customer->getUser() : null;
                    $userGroups = Commerce::getInstance()->getCustomers()->getUserGroupIdsForUser($user);
                    if ($user && array_intersect($userGroups, $this->_gift->getUserGroupIds())) {
                        $matchingLineIds[] = $lineItemHashId;
                        $matchingQty += $item->qty;
                        $matchingTotal += $item->getSubtotal();
                    }
                } else {
                    $matchingLineIds[] = $lineItemHashId;
                    $matchingQty += $item->qty;
                    $matchingTotal += $item->getSubtotal();
                }
            }
		}

		if($this->_gift->purchaseAll) {
			if(count($matchingLineIds) < count($this->_gift->getPurchasableIds())) {
				return false;
			}
		}

		if(!$matchingLineIds) {
			return false;
		}

        if (!$matchingQty) {
            return false;
		}

        // Have they entered a max qty?
        if ($this->_gift->maxPurchaseQty > 0 && $matchingQty > $this->_gift->maxPurchaseQty) {
            return false;
		}

        // Reject if they have not added enough matching items
        if ($matchingQty < $this->_gift->purchaseQty) {
            return false;
		}

        // Reject if the matching items values is not enough
        if ($matchingTotal < $this->_gift->purchaseTotal) {
            return false;
		}

		//TODO gift successfull store in gwp_orders table
		// $this->saveGiftOrder($this->_gift->id,$order->id);	

		return $this->_gift;

		// return $gifts;
	}
	
    /**
     * Updates gift uses counters.
     *
     * @param Order $order
     */
    public function orderCompleteHandler($order,$gift)
    {

		// Craft::dd($gift);
		
		
		/** @var GiftRecord $gift */
        // $gift = GiftRecord::find()->where(['code' => $order->couponCode])->one();
        if (!$gift || !$gift->id) {
            return;
		}

        if ($gift->totalUseLimit) {
            // Increment total uses.
            Craft::$app->getDb()->createCommand()
                ->update('{{%gwp_gifts}}', [
                    'totalUses' => new Expression('[[totalUses]] + 1')
                ], [
                    'id' => $gift->id
                ])
                ->execute();
        }

        if ($gift->perUserLimit && $order->customerId) {
            $customerGiftUseRecord = CustomerGiftUseRecord::find()->where(['customerId' => $order->customerId, 'giftId' => $gift->id])->one();

            if (!$customerGiftUseRecord) {
                $customerGiftUseRecord = new CustomerGiftUseRecord();
                $customerGiftUseRecord->customerId = $order->customerId;
                $customerGiftUseRecord->giftId = $gift->id;
                $customerGiftUseRecord->uses = 1;
                $customerGiftUseRecord->save();
            } else {
                Craft::$app->getDb()->createCommand()
                    ->update('{{%gwp_customer_giftuses}}', [
                        'uses' => new Expression('[[uses]] + 1')
                    ], [
                        'customerId' => $order->customerId,
                        'giftId' => $gift->id
                    ])
                    ->execute();
            }
        }

        if ($gift->perEmailLimit && $order->customerId) {
            $customerGiftUseRecord = EmailGiftUseRecord::find()->where(['email' => $order->getEmail(), 'giftId' => $gift->id])->one();

            if (!$customerGiftUseRecord) {
                $customerGiftUseRecord = new EmailGiftUseRecord();
                $customerGiftUseRecord->email = $order->getEmail();
                $customerGiftUseRecord->giftId = $gift->id;
                $customerGiftUseRecord->uses = 1;
                $customerGiftUseRecord->save();
            } else {
                Craft::$app->getDb()->createCommand()
                    ->update('{{%gwp_email_giftuses}}', [
                        'uses' => new Expression('[[uses]] + 1')
                    ], [
                        'email' => $order->getEmail(),
                        'giftId' => $gift->id
                    ])
                    ->execute();
            }
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving gifts
     *
     * @return Query
     */
    private function _createGiftQuery(): Query
    {
        return (new Query())
            ->select([
                'gifts.id',
                'gifts.name',
                'gifts.description',
                'gifts.dateFrom',
				'gifts.dateTo',
				'gifts.perUserLimit',
				'gifts.perEmailLimit',
				'gifts.totalUseLimit',
				'gifts.purchaseAll',
                'gifts.purchaseTotal',
                'gifts.purchaseQty',
                'gifts.maxPurchaseQty',
                'gifts.customerChoice',
                'gifts.maxCustomerChoice',
                'gifts.allGroups',
                'gifts.allPurchasables',
                'gifts.allCategories',
                'gifts.enabled',
                'gifts.sortOrder',
                'gifts.dateCreated',
                'gifts.dateUpdated',
            ])
            ->from(['gifts' => '{{%gwp_gifts}}'])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }
}
