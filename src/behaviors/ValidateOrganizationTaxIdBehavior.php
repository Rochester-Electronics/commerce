<?php

namespace craft\commerce\behaviors;

use Craft;
use craft\commerce\Plugin;
use craft\elements\Address;
use craft\events\DefineRulesEvent;
use DvK\Vat\Validator;
use Exception;
use RuntimeException;
use yii\base\Behavior;

class ValidateOrganizationTaxIdBehavior extends Behavior
{
    /** @var Address */
    public $owner;

    /**
     * @var Validator
     */
    private Validator $_vatValidator;

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        if (!$owner instanceof Address) {
            throw new RuntimeException('ValidateOrganizationTaxIdBehavior can only be attached to an Address element');
        }

        parent::attach($owner);
    }

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            Address::EVENT_DEFINE_RULES => 'defineRules',
        ];
    }

    /**
     * @param DefineRulesEvent $event
     * @return void
     */
    public function defineRules(DefineRulesEvent $event): void
    {
        $rules = $event->rules;
        $rules[] = [['organizationTaxId'], 'validateOrganizationTaxId', 'skipOnEmpty' => true];
        $event->rules = $rules;
    }

    /**
     * @return void
     */
    public function validateOrganizationTaxId(): void
    {
        if (!Plugin::getInstance()->getSettings()->validateBusinessTaxIdAsVatId) {
            return;
        }

        // Do we have a valid VAT ID in our cache?
        $validOrganizationTaxId = Craft::$app->getCache()->exists('commerce:validVatId:' . $this->owner->organizationTaxId);

        // If we do not have a valid VAT ID in cache, see if we can get one from the API
        if (!$validOrganizationTaxId) {
            $validOrganizationTaxId = $this->_validateVatNumber($this->owner->organizationTaxId);
        }

        if ($validOrganizationTaxId) {
            Craft::$app->getCache()->set('commerce:validVatId:' . $this->owner->organizationTaxId, '1');
        }

        // Clean up if the API returned false and the item was still in cache
        if (!$validOrganizationTaxId) {
            Craft::$app->getCache()->delete('commerce:validVatId:' . $this->owner->organizationTaxId);
            $this->owner->addError('organizationTaxId', Craft::t('commerce', 'Invalid VAT ID.'));
        }
    }

    /**
     * @param string $businessVatId
     * @return bool
     */
    private function _validateVatNumber(string $businessVatId): bool
    {
        try {
            $validators = Plugin::getInstance()->getTaxes()->getEngine()->getValidators();
            $valid = false;
            foreach ($validators as $validator) {
                if ($validator->validateExistence($businessVatId)) {
                    $valid = true;
                    break;
                }
            }
            return $valid;
        } catch (Exception $e) {
            Craft::error('Communication with Tax ID validators failed: ' . $e->getMessage(), __METHOD__);

            return false;
        }
    }

    /**
     * @return Validator
     */
    private function _getVatValidator(): Validator
    {
        if (!isset($this->_vatValidator)) {
            $this->_vatValidator = new Validator();
        }

        return $this->_vatValidator;
    }
}
