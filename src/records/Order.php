<?php
/**
 * GWP plugin for Craft CMS 3.x
 *
 * Commerce GWP plugin for Craft CMS
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace craft\commerce\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Customer discount record.
 *
 * @property Customer $customer
 * @property int $customerId
 * @property Discount $discount
 * @property int $giftId
 * @property int $id
 * @property int $uses
 * @author Kurious Agency
 * @since 1.0.0
 */
class Order extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%commerce_customer_discountuses}}';
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getDiscount(): ActiveQueryInterface
    {
        return $this->hasOne(Discount::class, ['id', 'giftId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getCustomer(): ActiveQueryInterface
    {
        return $this->hasOne(Customer::class, ['id', 'customerId']);
    }
}
