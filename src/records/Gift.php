<?php
/**
 * GWP plugin for Craft CMS 3.x
 *
 * Commerce GWP plugin for Craft CMS
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\commerce\gwp\records;

use craft\db\ActiveRecord;
use craft\records\Category;
use craft\records\UserGroup;
use DateTime;
use yii\db\ActiveQueryInterface;

/**
 * Gift record.
 *
 * @property bool $allCategories
 * @property bool $allGroups
 * @property bool $allPurchasables
 * @property DateTime $dateFrom
 * @property DateTime $dateTo
 * @property string $description
 * @property ActiveQueryInterface $giftUserGroups
 * @property bool $enabled
 * @property bool $excludeOnSale
 * @property bool $hasFreeShippingForMatchingItems
 * @property bool $hasFreeShippingForOrder
 * @property UserGroup[] $groups
 * @property int $id
 * @property int $maxPurchaseQty
 * @property string $name
 * @property int $perEmailLimit
 * @property int $perUserLimit
 * @property int $purchaseQty
 * @property int $purchaseTotal
 * @property int $sortOrder
 * @property int $totalUseLimit
 * @property int $totalUses
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class Gift extends ActiveRecord
{

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%gwp_gifts}}';
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getGiftUserGroups(): ActiveQueryInterface
    {
        return $this->hasMany(GiftUserGroup::class, ['giftId' => 'id']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getGifttPurchasables(): ActiveQueryInterface
    {
        return $this->hasMany(GiftPurchasable::class, ['giftId' => 'id']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getGiftCategories(): ActiveQueryInterface
    {
        return $this->hasMany(GiftCategory::class, ['giftId' => 'id']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getGroups(): ActiveQueryInterface
    {
        return $this->hasMany(UserGroup::class, ['id' => 'giftId'])->via('giftUserGroups');
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getPurchasables(): ActiveQueryInterface
    {
        return $this->hasMany(Purchasable::class, ['id' => 'giftId'])->via('giftPurchasables');
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getCategories(): ActiveQueryInterface
    {
        return $this->hasMany(Category::class, ['id' => 'giftId'])->via('giftCategories');
    }
}
