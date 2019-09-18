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
 * Customer gift record.
 *
 * @property Customer $customer
 * @property int $customerId
 * @property Gift $gift
 * @property int $giftId
 * @property int $id
 * @property int $uses
 * @author Kurious Agency
 * @since 1.0.0
 */
class CustomerGiftUse extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%gwp_customer_giftuses}}';
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getGift(): ActiveQueryInterface
    {
        return $this->hasOne(Gift::class, ['id', 'giftId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getCustomer(): ActiveQueryInterface
    {
        return $this->hasOne(Customer::class, ['id', 'customerId']);
    }
}
