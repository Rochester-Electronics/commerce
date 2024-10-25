<?php

namespace craft\commerce\elements\conditions\orders;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\conditions\BaseSelectConditionRule;
use craft\base\ElementInterface;
use craft\commerce\elements\db\OrderQuery;
use craft\commerce\elements\Order;
use craft\commerce\Plugin;
use craft\elements\conditions\ElementConditionRuleInterface;
use yii\db\QueryInterface;

/**
 * Payment Gateway condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class PaymentGatewayConditionRule extends BaseSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('commerce', 'Payment Gateway');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['gatewayId'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return Plugin::getInstance()->getGateways()->getAllGateways()->mapWithKeys(function($gateway) {
            return [$gateway->id => $gateway->name];
        })->all();
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var OrderQuery $query */
        $query->gatewayId($this->value);
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Order $element */
        return $this->matchValue($element->gatewayId);
    }
}
