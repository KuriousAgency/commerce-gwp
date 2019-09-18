<?php
/**
 * GWP plugin for Craft CMS 3.x
 *
 * GWP plugin for Craft CMS
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\commerce\gwp\migrations;

use kuriousagency\commerce\gwp\Gwp;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * @author    Kurious Agency
 * @package   GWP
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            // $this->insertDefaultData();
        }

        return true;
    }

   /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%gwp_gifts}}');
        if ($tableSchema === null) {
			$tablesCreated = true;
			
			// new table store orderId and gwpId 
			// cascade delete when carts are purged
			$this->createTable('{{%gwp_orders}}', [
				'id' => $this->primaryKey(),
				'giftId' => $this->integer()->notNull(),
				'orderId' => $this->integer()->notNull(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]);
			
			$this->createTable('{{%gwp_customer_giftuses}}', [
				'id' => $this->primaryKey(),
				'giftId' => $this->integer()->notNull(),
				'customerId' => $this->integer()->notNull(),
				'uses' => $this->integer()->notNull()->unsigned(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]);
	
			$this->createTable('{{%gwp_email_giftuses}}', [
				'id' => $this->primaryKey(),
				'giftId' => $this->integer()->notNull(),
				'email' => $this->string()->notNull(),
				'uses' => $this->integer()->notNull()->unsigned(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]);
		   
			$this->createTable('{{%gwp_condition_purchasables}}', [
				'id' => $this->primaryKey(),
				'giftId' => $this->integer()->notNull(),
				'purchasableId' => $this->integer()->notNull(),
				'purchasableType' => $this->string()->notNull(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]);
	
			$this->createTable('{{%gwp_condition_categories}}', [
				'id' => $this->primaryKey(),
				'giftId' => $this->integer()->notNull(),
				'categoryId' => $this->integer()->notNull(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]);
	
			$this->createTable('{{%gwp_condition_usergroups}}', [
				'id' => $this->primaryKey(),
				'giftId' => $this->integer()->notNull(),
				'userGroupId' => $this->integer()->notNull(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]);

			$this->createTable('{{%gwp_product_purchasables}}', [
				'id' => $this->primaryKey(),
				'giftId' => $this->integer()->notNull(),
				'purchasableId' => $this->integer()->notNull(),
				'purchasableType' => $this->string()->notNull(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]);
	
			$this->createTable('{{%gwp_gifts}}', [
				'id' => $this->primaryKey(),
				'name' => $this->string()->notNull(),
				'description' => $this->text(),
				'perUserLimit' => $this->integer()->notNull()->defaultValue(0)->unsigned(),
				'perEmailLimit' => $this->integer()->notNull()->defaultValue(0)->unsigned(),
				'totalUseLimit' => $this->integer()->notNull()->defaultValue(0)->unsigned(),
				'totalUses' => $this->integer()->notNull()->defaultValue(0)->unsigned(),
				'dateFrom' => $this->dateTime(),
				'dateTo' => $this->dateTime(),
				'purchaseAll' => $this->boolean(),
				'purchaseTotal' => $this->integer()->notNull()->defaultValue(0),
				'purchaseQty' => $this->integer()->notNull()->defaultValue(0),
				'maxPurchaseQty' => $this->integer()->notNull()->defaultValue(0),
				'customerChoice' => $this->boolean(),
				'maxCustomerChoice' => $this->integer()->notNull()->defaultValue(0),
				'allGroups' => $this->boolean(),
				'allPurchasables' => $this->boolean(),
				'allCategories' => $this->boolean(),
				'enabled' => $this->boolean(),
				'sortOrder' => $this->integer(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid(),
			]);
			
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
		$this->createIndex(null, '{{%gwp_condition_purchasables}}', ['giftId', 'purchasableId'], true);
        $this->createIndex(null, '{{%gwp_condition_purchasables}}', 'purchasableId', false);
        $this->createIndex(null, '{{%gwp_condition_categories}}', ['giftId', 'categoryId'], true);
        $this->createIndex(null, '{{%gwp_condition_categories}}', 'categoryId', false);
        $this->createIndex(null, '{{%gwp_condition_usergroups}}', ['giftId', 'userGroupId'], true);
        $this->createIndex(null, '{{%gwp_condition_usergroups}}', 'userGroupId', false);
        $this->createIndex(null, '{{%gwp_gifts}}', 'dateFrom', false);
		$this->createIndex(null, '{{%gwp_gifts}}', 'dateTo', false);
		$this->createIndex(null, '{{%gwp_product_purchasables}}', ['giftId', 'purchasableId'], true);
		$this->createIndex(null, '{{%gwp_product_purchasables}}', 'purchasableId', false);
		$this->createIndex(null, '{{%gwp_orders}}', ['giftId', 'orderId'], true);
		$this->createIndex(null, '{{%gwp_orders}}', 'orderId', false);
		$this->createIndex(null, '{{%gwp_email_giftuses}}', ['email', 'giftId'], true);
        $this->createIndex(null, '{{%gwp_email_giftuses}}', ['giftId'], false);
        $this->createIndex(null, '{{%gwp_customer_giftuses}}', ['customerId', 'giftId'], true);
        $this->createIndex(null, '{{%gwp_customer_giftuses}}', 'giftId', false);
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
		$this->addForeignKey(null, '{{%gwp_condition_purchasables}}', ['giftId'], '{{%gwp_gifts}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%gwp_condition_purchasables}}', ['purchasableId'], '{{%commerce_purchasables}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%gwp_condition_categories}}', ['giftId'], '{{%gwp_gifts}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%gwp_condition_categories}}', ['categoryId'], '{{%categories}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%gwp_condition_usergroups}}', ['giftId'], '{{%gwp_gifts}}', ['id'], 'CASCADE', 'CASCADE');
		$this->addForeignKey(null, '{{%gwp_condition_usergroups}}', ['userGroupId'], '{{%usergroups}}', ['id'], 'CASCADE', 'CASCADE');		
		$this->addForeignKey(null, '{{%gwp_product_purchasables}}', ['giftId'], '{{%gwp_gifts}}', ['id'], 'CASCADE', 'CASCADE');
		$this->addForeignKey(null, '{{%gwp_product_purchasables}}', ['purchasableId'], '{{%commerce_purchasables}}', ['id'], 'CASCADE', 'CASCADE');
		$this->addForeignKey(null, '{{%gwp_orders}}', ['giftId'], '{{%gwp_gifts}}', ['id'], 'CASCADE', 'CASCADE');
		$this->addForeignKey(null, '{{%gwp_orders}}', ['orderId'], '{{%commerce_orders}}', ['id'], 'CASCADE', 'CASCADE');
		$this->addForeignKey(null, '{{%gwp_email_giftuses}}', ['giftId'], '{{%gwp_gifts}}', ['id'], 'CASCADE', 'CASCADE');
		$this->addForeignKey(null, '{{%gwp_customer_giftuses}}', ['customerId'], '{{%commerce_customers}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%gwp_customer_giftuses}}', ['giftId'], '{{%commerce_discounts}}', ['id'], 'CASCADE', 'CASCADE');
    }


    /**
     * @return void
     */
    protected function removeTables()
    {
		$this->dropForeignKeys();
        $this->dropTables();
	}
	
	public function dropForeignKeys()
    {
		if ($this->_tableExists('{{%gwp_condition_purchasables}}')) {
            MigrationHelper::dropAllForeignKeysToTable('{{%gwp_condition_purchasables}}', $this);
            MigrationHelper::dropAllForeignKeysOnTable('{{%gwp_condition_purchasables}}', $this);
        }
        if ($this->_tableExists('{{%gwp_condition_categories}}')) {
            MigrationHelper::dropAllForeignKeysToTable('{{%gwp_condition_categories}}', $this);
            MigrationHelper::dropAllForeignKeysOnTable('{{%gwp_condition_categories}}', $this);
        }
        if ($this->_tableExists('{{%gwp_condition_usergroups}}')) {
            MigrationHelper::dropAllForeignKeysToTable('{{%gwp_condition_usergroups}}', $this);
            MigrationHelper::dropAllForeignKeysOnTable('{{%gwp_condition_usergroups}}', $this);
		}
		if ($this->_tableExists('{{%gwp_product_purchasables}}')) {
            MigrationHelper::dropAllForeignKeysToTable('{{%gwp_product_purchasables}}', $this);
            MigrationHelper::dropAllForeignKeysOnTable('{{%gwp_product_purchasables}}', $this);
		}
		if ($this->_tableExists('{{%gwp_orders}}')) {
            MigrationHelper::dropAllForeignKeysToTable('{{%gwp_orders}}', $this);
            MigrationHelper::dropAllForeignKeysOnTable('{{%gwp_orders}}', $this);
        }
		if ($this->_tableExists('{{%gwp_customer_giftuses}}')) {
            MigrationHelper::dropAllForeignKeysToTable('{{%gwp_customer_giftuses}}', $this);
            MigrationHelper::dropAllForeignKeysOnTable('{{%gwp_customer_giftuses}}', $this);
        }
        if ($this->_tableExists('{{%gwp_email_giftuses}}')) {
            MigrationHelper::dropAllForeignKeysToTable('{{%gwp_email_giftuses}}', $this);
            MigrationHelper::dropAllForeignKeysOnTable('{{%gwp_email_giftuses}}', $this);
		}
		
	}

	public function dropTables()
    {
		$this->dropTableIfExists('{{%gwp_gifts}}');
		$this->dropTableIfExists('{{%gwp_condition_purchasables}}');
        $this->dropTableIfExists('{{%gwp_condition_categories}}');
		$this->dropTableIfExists('{{%gwp_condition_usergroups}}');
		$this->dropTableIfExists('{{%gwp_product_purchasables}}');
		$this->dropTableIfExists('{{%gwp_orders}}');
		$this->dropTableIfExists('{{%gwp_customer_giftuses}}');
        $this->dropTableIfExists('{{%gwp_email_giftuses}}');
	}

	/**
     * Returns if the table exists.
     *
     * @param string $tableName
     * @param Migration|null $migration
     * @return bool If the table exists.
     */
    private function _tableExists(string $tableName): bool
    {
        $schema = $this->db->getSchema();
        $schema->refresh();

        $rawTableName = $schema->getRawTableName($tableName);
        $table = $schema->getTableSchema($rawTableName);

        return (bool)$table;
    }

}
