<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\fieldlayoutelements;

use craft\base\ElementInterface;
use craft\base\Field;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use yii\base\InvalidArgumentException;

/**
 * ProductTitleField represents a Title field that can be included within a product type’s product field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class ProductTitleField extends TitleField
{
    /**
     * @inheritdoc
     */
    protected function selectorInnerHtml(): string
    {
        return
            Html::tag('span', '', [
                'class' => ['fld-product-title-field-icon', 'fld-field-hidden', 'hidden'],
            ]) .
            parent::selectorInnerHtml();
    }

    /**
     * @inheritdoc
     */
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        if (!$element instanceof Product) {
            throw new \InvalidArgumentException(sprintf('%s can only be used in product field layouts.', __CLASS__));
        }

        return $element->getType()->productTitleTranslationMethod !== Field::TRANSLATION_METHOD_NONE;
    }

    /**
     * @inheritdoc
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Product) {
            throw new \InvalidArgumentException(sprintf('%s can only be used in product field layouts.', __CLASS__));
        }

        return ElementHelper::translationDescription($element->getType()->productTitleTranslationMethod);
    }

    /**
     * @inheritdoc
     */
    public function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Product) {
            throw new InvalidArgumentException('ProductTitleField can only be used in product field layouts.');
        }

        if (!$element->getType()->hasProductTitleField && !$element->hasErrors('title')) {
            return null;
        }

        return parent::inputHtml($element, $static);
    }
}
