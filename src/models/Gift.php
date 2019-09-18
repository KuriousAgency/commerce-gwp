<?php
/**
 * GWP plugin for Craft CMS 3.x
 *
 * Commerce GWP plugin for Craft CMS
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\commerce\gwp\models;

use kuriousagency\commerce\gwp\Gwp;

use Craft;
use craft\base\Model;
// use craft\commerce\Plugin;
use craft\helpers\UrlHelper;
use DateTime;

/**
 * Discount model
 *
 * @property string|false $cpEditUrl
 * @property-read string $percentDiscountAsPercent
 * @property array $categoryIds
 * @property array $purchasableIds
 * @property array $userGroupIds
 * @author Kurious Agency
 * @since 1.0.0
 */
class Gift extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var string Name of the discount
     */
    public $name;

    /**
     * @var string The description of this discount
     */
	public $description;
	
	/**
     * @var int Per user coupon use limit
     */
    public $perUserLimit = 0;

    /**
     * @var int Per email coupon use limit
     */
    public $perEmailLimit = 0;

    /**
     * @var int Total use limit by guests or users
     */
    public $totalUseLimit = 0;

    /**
     * @var int Total use counter;
     */
    public $totalUses = 0;

    /**
     * @var DateTime|null Date the discount is valid from
     */
    public $dateFrom;

    /**
     * @var DateTime|null Date the discount is valid to
     */
	public $dateTo;
	
	    /**
     * @var bool Match all selected purchasable.
     */
    public $purchaseAll;

    /**
     * @var float Total minimum spend on matching items
     */
    public $purchaseTotal = 0;

    /**
     * @var int Total minimum qty of matching items
     */
    public $purchaseQty = 0;

    /**
     * @var int Total maximum spend on matching items
     */
    public $maxPurchaseQty = 0;

	public $customerChoice;
	
    public $maxCustomerChoice = 0;

    /**
     * @var bool Match all user groups.
     */
    public $allGroups;

    /**
     * @var bool Match all products
     */
    public $allPurchasables;

    /**
     * @var bool Match all product types
     */
    public $allCategories;

    /**
     * @var bool Discount enabled?
     */
    public $enabled = true;

    /**
     * @var int sortOrder
     */
    public $sortOrder;

    /**
     * @var DateTime|null
     */
    public $dateCreated;

    /**
     * @var DateTime|null
     */
    public $dateUpdated;

    /**
     * @var int[] Product Ids
     */
    private $_purchasableIds;

    /**
     * @var int[] Product Type IDs
     */
    private $_categoryIds;

    /**
     * @var int[] Group IDs
     */
	private $_userGroupIds;
	
	 /**
     * @var int[] Product Ids
     */
    private $_productPurchasableIds;

    /**
     * @var int[] Product Type IDs
     */
	// private $_productCategoryIds;
	


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dateFrom';
        $attributes[] = 'dateTo';

        return $attributes;
    }

    /**
     * @return string|false
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('commerce-gwp/discounts/' . $this->id);
    }

    /**
     * @return array
     */
    public function getCategoryIds(): array
    {
        if (null === $this->_categoryIds) {
            $this->_loadRelations();
        }

        return $this->_categoryIds;
    }

    /**
     * @return array
     */
    public function getPurchasableIds(): array
    {
        if (null === $this->_purchasableIds) {
            $this->_loadRelations();
        }

        return $this->_purchasableIds;
    }

    /**
     * @return array
     */
    public function getUserGroupIds(): array
    {
        if (null === $this->_userGroupIds) {
            $this->_loadRelations();
        }

        return $this->_userGroupIds;
	}

    /**
     * @return array
     */
    public function getProductPurchasableIds(): array
    {		
		if (null === $this->_productPurchasableIds) {
            $this->_loadRelations();
        }

        return $this->_productPurchasableIds;
    }

    /**
     * Sets the related condition product type ids
     *
     * @param array $categoryIds
     */
    public function setCategoryIds(array $categoryIds)
    {
        $this->_categoryIds = array_unique($categoryIds);
    }

    /**
     * Sets the related condition product ids
     *
     * @param array $purchasableIds
     */
    public function setPurchasableIds(array $purchasableIds)
    {
        $this->_purchasableIds = array_unique($purchasableIds);
    }

    /**
     * Sets the related user group ids
     *
     * @param array $userGroupIds
     */
    public function setUserGroupIds(array $userGroupIds)
    {
        $this->_userGroupIds = array_unique($userGroupIds);
	}

    /**
     * Sets the related product ids
     *
     * @param array $purchasableIds
     */
    public function setProductPurchasableIds(array $purchasableIds)
    {		
		$this->_productPurchasableIds = array_unique($purchasableIds);
    }

    

    /**
     * @inheritdoc
     */
    public function rules()
    {
		// TODO check purchasableIds, categoryIds unique?
		
		return [
            [['name'], 'required'],
            [
                [
                    'purchaseTotal',
                    'purchaseQty',
					'maxPurchaseQty',
					'maxCustomerChoice'
                ], 'number', 'skipOnEmpty' => false
			],
			[
                ['purchasableIds'], 'required', 'when' => function($model) {
                	return !$model->categoryIds;
            	}
            ],
            [
                ['categoryIds'], 'required', 'when' => function($model) {
                	return !$model->purchasableIds;
            	}
			],
			// [
            //     ['productPurchasableIds'], 'required', 'when' => function($model) {
            //     	return !$model->productCategoryIds;
            // 	}
            // ],
            // [
            //     ['productCategoryIds'], 'required', 'when' => function($model) {
            //     	return !$model->productPurchasableIds;
            // 	}
            // ],
        ];
    }

    // Private Methods
    // =========================================================================

    /**
     * Loads the sale relations
     */
    private function _loadRelations()
    {
        Gwp::$plugin->service->populateGiftRelations($this);
    }
}
