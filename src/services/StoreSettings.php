<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\services;

use Craft;
use craft\commerce\db\Table;
use craft\commerce\models\StoreSettings as StoreSettingsModel;
use craft\commerce\Plugin;
use craft\commerce\records\StoreSettings as StoreSettingsRecord;
use craft\db\Query;
use craft\elements\Address;
use craft\elements\Address as AddressElement;
use craft\errors\ElementNotFoundException;
use Throwable;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Stores service.
 *
 * @property-read StoreSettingsModel $store
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0
 */
class StoreSettings extends Component
{
    /**
     * @var ?StoreSettingsModel
     */
    private ?StoreSettingsModel $_store = null;

    /**
     * @return void
     */
    public function init(): void
    {
        parent::init();

        if ($this->_store == null) {
            $currentStoreId = Plugin::getInstance()->getStores()->getCurrentStore()->id; // FIXME We need to ensure we have a store before creating a store settings.
            // We always ensure we have a store record and an associated store address.
            $this->_store = $this->getStoreSettingsByStoreId($currentStoreId);
            if (!$this->_store) {
                $storeRecord = new StoreSettingsRecord();
                $storeRecord->id = $currentStoreId;
                $storeRecord->save();
                $this->_store = Craft::createObject(['class' => StoreSettingsModel::class, 'id' => $storeRecord->id]);
            }

            // Make sure the store always has an address location set.
            $storeLocationAddress = $this->_store->getLocationAddressId() ? AddressElement::findOne($this->_store->locationAddressId) : null;
            if ($storeLocationAddress === null) {
                $this->_createDefaultStoreLocationAddress();
            }
        }
    }

    /**
     * @param int $storeId
     * @return StoreSettingsModel|null
     * @throws InvalidConfigException
     */
    public function getStoreSettingsByStoreId(int $storeId): ?StoreSettingsModel
    {
        $store = $this->_createStoreQuery()->where(['id' => $storeId])->one();
        if ($store === null) {
            return null;
        }

        if ($store['locationAddressId'] === null) {
            unset($store['locationAddressId']);
        }

        return Craft::createObject(array_merge(['class' => StoreSettingsModel::class], $store));
    }

    /**
     * Returns the store record.
     *
     * @return StoreSettingsModel
     */
    public function getStore(): StoreSettingsModel
    {
        return $this->_store;
    }

    /**
     * Saves the store
     *
     * @param StoreSettingsModel $store
     * @return bool
     * @throws InvalidConfigException
     */
    public function saveStore(StoreSettingsModel $store): bool
    {
        $storeRecord = StoreSettingsRecord::findOne($store->id);

        if (!$storeRecord) {
            throw new InvalidConfigException('Invalid store ID');
        }

        $storeRecord->countries = $store->countries;
        $storeRecord->marketAddressCondition = $store->marketAddressCondition->getConfig();
        $storeRecord->locationAddressId = $store->getLocationAddressId();

        if (!$storeRecord->save()) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function _createDefaultStoreLocationAddress(): void
    {
        if (!$this->_store instanceof StoreSettingsModel) {
            return;
        }

        $storeLocationAddress = new AddressElement();
        $storeLocationAddress->title = 'Store';
        $storeLocationAddress->countryCode = 'US';
        if (Craft::$app->getElements()->saveElement($storeLocationAddress, false)) {
            $storeRecord = StoreSettingsRecord::findOne($this->_store->id);
            if ($storeRecord === null) {
                return;
            }

            $storeRecord->locationAddressId = $storeLocationAddress->id;
            $storeRecord->save();
            $this->_store->setLocationAddressId($storeLocationAddress->id); // update the model
        }
    }

    /**
     * Returns a Query object prepped for retrieving the store.
     */
    private function _createStoreQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'locationAddressId',
                'marketAddressCondition',
                'countries',
            ])
            ->from([Table::STORESETTINGS]);
    }
}
