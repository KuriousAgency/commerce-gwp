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
use craft\records\UserGroup;
use yii\db\ActiveQueryInterface;

/**
 * Gift user record.
 *
 * @property ActiveQueryInterface $gift
 * @property int $giftId
 * @property int $id
 * @property ActiveQueryInterface $productType
 * @property int $userGroupId
 * @author Kurious Agency
 * @since 1.0.0
 */
class ConditionUserGroup extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%gwp_condition_usergroups}}';
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
    public function getProductType(): ActiveQueryInterface
    {
        return $this->hasOne(UserGroup::class, ['id' => 'userGroupId']);
    }
}
