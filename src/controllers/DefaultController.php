<?php
/**
 * GWP plugin for Craft CMS 3.x
 *
 * Commerce GWP plugin for Craft CMS
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\commerce\gwp\controllers;

use kuriousagency\commerce\gwp\Gwp;
use kuriousagency\commerce\gwp\models\Gift;


use Craft;
use craft\web\Controller;
use craft\commerce\base\Purchasable;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\Plugin as Commerce;
use craft\elements\Category;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\i18n\Locale;
use function explode;
use function get_class;
use yii\web\HttpException;
use yii\web\Response;


/**
 * @author    Kurious Agency
 * @package   GWP
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // $this->requirePermission('commerce-managePromotions');
        parent::init();
    }

    /**
     * @throws HttpException
     */
    public function actionIndex(): Response
    {
        $gifts = Gwp::$plugin->service->getAllGifts();
        return $this->renderTemplate('commerce-gwp/index', compact('gifts'));
    }

    /**
     * @param int|null $id
     * @param Gift|null $gift
     * @return Response
     * @throws HttpException
     */
    public function actionEdit(int $id = null, Gift $gift = null): Response
    {
        $variables = compact('id', 'gift');

        if (!$variables['gift']) {
            if ($variables['id']) {
                $variables['gift'] = Gwp::$plugin->service->getGiftById($variables['id']);

                if (!$variables['gift']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['gift'] = new Gift();
            }
        }

		$this->_populateVariables($variables);
		
		// Craft::dd($variables->getProductPurchasables);

        return $this->renderTemplate('commerce-gwp/_edit', $variables);
    }

    /**
     * @throws HttpException
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $gift = new Gift();
        $request = Craft::$app->getRequest();

        $gift->id = $request->getBodyParam('id');
        $gift->name = $request->getBodyParam('name');
		$gift->description = $request->getBodyParam('description');
		$gift->enabled = (bool)$request->getBodyParam('enabled');
        $gift->perUserLimit = $request->getBodyParam('perUserLimit');
        $gift->perEmailLimit = $request->getBodyParam('perEmailLimit');
		$gift->totalUseLimit = $request->getBodyParam('totalUseLimit');
        $gift->purchaseAll = $request->getBodyParam('purchaseAll');
        $gift->purchaseTotal = $request->getBodyParam('purchaseTotal');
        $gift->purchaseQty = $request->getBodyParam('purchaseQty');
        $gift->maxPurchaseQty = $request->getBodyParam('maxPurchaseQty');
		$gift->customerChoice = $request->getBodyParam('customerChoice');
		$gift->maxCustomerChoice = $request->getBodyParam('maxCustomerChoice');

        $date = $request->getBodyParam('dateFrom');
        if ($date) {
            $dateTime = DateTimeHelper::toDateTime($date) ?: null;
            $gift->dateFrom = $dateTime;
        }

        $date = $request->getBodyParam('dateTo');
        if ($date) {
            $dateTime = DateTimeHelper::toDateTime($date) ?: null;
            $gift->dateTo = $dateTime;
        }

        // Format into a %
        // $percentGiftAmount = $request->getBodyParam('percentGift');
        $localeData = Craft::$app->getLocale();
        // $percentSign = $localeData->getNumberSymbol(Locale::SYMBOL_PERCENT);
        // if (strpos($percentGiftAmount, $percentSign) || (float)$percentGiftAmount >= 1) {
        //     $gift->percentGift = (float)$percentGiftAmount / -100;
        // } else {
        //     $gift->percentGift = (float)$percentGiftAmount * -1;
        // }

        $purchasables = [];
        $purchasableGroups = $request->getBodyParam('purchasables') ?: [];
        foreach ($purchasableGroups as $group) {
            if (is_array($group)) {
                array_push($purchasables, ...$group);
            }
        }
        $purchasables = array_unique($purchasables);
        $gift->setPurchasableIds($purchasables);

        $categories = $request->getBodyParam('categories', []);
        if (!$categories) {
            $categories = [];
        }
        $gift->setCategoryIds($categories);

        $groups = $request->getBodyParam('groups', []);
        if (!$groups) {
            $groups = [];
        }
		$gift->setUserGroupIds($groups);
		
		// product purchasables
		$productPurchasables = [];
        $productPurchasableGroups = $request->getBodyParam('productPurchasables') ?: [];
        foreach ($productPurchasableGroups as $group) {
            if (is_array($group)) {
                array_push($productPurchasables, ...$group);
            }
        }
        $productPurchasables = array_unique($productPurchasables);
		$gift->setProductPurchasableIds($productPurchasables);
		
		// product categories
		$productCategories = $request->getBodyParam('productCategories', []);
        if (!$productCategories) {
            $productCategories = [];
		}
		
		// $gift->setProductCategoryIds($productCategories);

        // Save it
        if (Gwp::$plugin->service->saveGift($gift)
        ) {
            Craft::$app->getSession()->setNotice(Craft::t('commerce-gwp', 'Gift saved.'));
            $this->redirectToPostedUrl($gift);
        } else {
            Craft::$app->getSession()->setError(Craft::t('commerce-gwp', 'Couldn’t save gift.'));
        }

        // Send the model back to the template
        $variables = [
            'gift' => $gift
        ];
        $this->_populateVariables($variables);

        Craft::$app->getUrlManager()->setRouteParams($variables);
    }

    /**
     *
     */
    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $ids = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
        if ($success = Gwp::$plugin->service->reorderGifts($ids)) {
            return $this->asJson(['success' => $success]);
        }

        return $this->asJson(['error' => Craft::t('commerce-gwp', 'Couldn’t reorder gifts.')]);
    }

    /**
     * @throws HttpException
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Gwp::$plugin->service->deleteGiftById($id);

        return $this->asJson(['success' => true]);
    }

    // /**
    //  * @throws HttpException
    //  */
    public function actionClearGwpUsageHistory()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Gwp::$plugin->service->clearGwpUsageHistoryById($id);

        return $this->asJson(['success' => true]);
    }

    // Private Methods
    // =========================================================================

    /**
     * @param array $variables
     */
    private function _populateVariables(&$variables)
    {

		if ($variables['gift']->id) {
            $variables['title'] = $variables['gift']->name;
        } else {
            $variables['title'] = Craft::t('commerce-gwp', 'Create a Gift');
        }

        //getting user groups map      
        $groups = Craft::$app->getUserGroups()->getAllGroups();
        $variables['groups'] = ArrayHelper::map($groups, 'id', 'name');
       
        $variables['categoryElementType'] = Category::class;
        $variables['categories'] = null;
        $categories = $categoryIds = [];

        if (empty($variables['id']) && Craft::$app->getRequest()->getParam('categoryIds')) {
            $categoryIds = explode('|', Craft::$app->getRequest()->getParam('categoryIds'));
        } else {
            $categoryIds = $variables['gift']->getCategoryIds();
        }

        foreach ($categoryIds as $categoryId) {
            $id = (int)$categoryId;
            $categories[] = Craft::$app->getElements()->getElementById($id);
        }

		$variables['categories'] = $categories;
		
        $variables['purchasables'] = null;

        if (empty($variables['id']) && Craft::$app->getRequest()->getParam('purchasableIds')) {

            $purchasableIdsFromUrl = explode('|', Craft::$app->getRequest()->getParam('purchasableIds'));
            $purchasableIds = [];
            foreach ($purchasableIdsFromUrl as $purchasableId) {
                $purchasable = Craft::$app->getElements()->getElementById((int)$purchasableId);
                if ($purchasable && $purchasable instanceof Product) {
                    $purchasableIds[] = $purchasable->defaultVariantId;
                } else {
                    $purchasableIds[] = $purchasableId;
                }
            }
        } else {
            $purchasableIds = $variables['gift']->getPurchasableIds();
        }

        $purchasables = [];
        foreach ($purchasableIds as $purchasableId) {
            $purchasable = Craft::$app->getElements()->getElementById((int)$purchasableId);
            if ($purchasable && $purchasable instanceof PurchasableInterface) {
                $class = get_class($purchasable);
                $purchasables[$class] = $purchasables[$class] ?? [];
                $purchasables[$class][] = $purchasable;
            }
        }
		$variables['purchasables'] = $purchasables;


		// product categories
		// $variables['productCategories'] = null;
        // $productCategories = $productCategoryIds = [];

        // if (empty($variables['id']) && Craft::$app->getRequest()->getParam('productCategoryIds')) {
        //     $productCategoryIds = explode('|', Craft::$app->getRequest()->getParam('productCategoryIds'));
        // } else {
        //     $productCategoryIds = $variables['gift']->getProductCategoryIds();
        // }

        // foreach ($productCategoryIds as $categoryId) {
        //     $id = (int)$categoryId;
        //     $productCategories[] = Craft::$app->getElements()->getElementById($id);
        // }

		// $variables['productCategories'] = $productCategories;

		// product purchasables
		$variables['productPurchasables'] = null;

        if (empty($variables['id']) && Craft::$app->getRequest()->getParam('productPurchasableIds')) {
            $productPurchasableIdsFromUrl = explode('|', Craft::$app->getRequest()->getParam('productPurchasableIds'));
            $productPurchasableIds = [];
            foreach ($productPurchasableIdsFromUrl as $purchasableId) {
                $purchasable = Craft::$app->getElements()->getElementById((int)$purchasableId);
                if ($purchasable && $purchasable instanceof Product) {
                    $productPurchasableIds[] = $purchasable->defaultVariantId;
                } else {
                    $productPurchasableIds[] = $purchasableId;
                }
            }
        } else {
            $productPurchasableIds = $variables['gift']->getProductPurchasableIds();
		}

		// Craft::dump($productPurchasableIds);

        $productPurchasables = [];
        foreach ($productPurchasableIds as $purchasableId) {
            $purchasable = Craft::$app->getElements()->getElementById((int)$purchasableId);
            if ($purchasable && $purchasable instanceof PurchasableInterface) {
                $class = get_class($purchasable);
                $productPurchasables[$class] = $productPurchasables[$class] ?? [];
                $productPurchasables[$class][] = $purchasable;
            }
        }
		$variables['productPurchasables'] = $productPurchasables;

		// purchasableTypes
        $variables['purchasableTypes'] = [];
        $purchasableTypes = Commerce::getInstance()->getPurchasables()->getAllPurchasableElementTypes();

        /** @var Purchasable $purchasableType */
        foreach ($purchasableTypes as $purchasableType) {
            $variables['purchasableTypes'][] = [
                'name' => $purchasableType::displayName(),
                'elementType' => $purchasableType
            ];
		}
	}

}
