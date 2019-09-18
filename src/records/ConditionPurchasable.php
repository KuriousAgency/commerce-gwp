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
use yii\db\ActiveQueryInterface;

/**
 * Gift product record.
 *
 * @property ActiveQueryInterface $gift
 * @property int $giftId
 * @property int $id
 * @property ActiveQueryInterface $purchasable
 * @property int $purchasableId
 * @property int $purchasableType
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class ConditionPurchasable extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%gwp_condition_purchasables}}';
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getGift(): ActiveQueryInterface
    {
        return $this->hasOne(Gift::class, ['id' => 'giftId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getPurchasable(): ActiveQueryInterface
    {
        return $this->hasOne(Purchasable::class, ['id' => 'purchasableId']);
    }
}
