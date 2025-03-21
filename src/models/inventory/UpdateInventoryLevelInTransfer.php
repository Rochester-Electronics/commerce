<?php

namespace craft\commerce\models\inventory;

use craft\base\Model;
use craft\commerce\base\InventoryItemTrait;
use craft\commerce\base\InventoryLocationTrait;
use craft\commerce\enums\InventoryTransactionType;
use craft\commerce\enums\InventoryUpdateQuantityType;

/**
 * Update (Set and Adjust) Inventory Quantity model
 *
 * @since 5.0
 */
class UpdateInventoryLevelInTransfer extends Model
{
    use InventoryItemTrait, InventoryLocationTrait;

    /**
     * The type is the set of InventoryTransactionType values, plus the `onHand` type.
     * @var string The inventory update type.
     */
    public string $type;

    /**
     * Whether the update should be associated with a transfer.
     * @var int|null
     */
    public ?int $transferId = null;

    /**
     * @var InventoryUpdateQuantityType The action to perform on the inventory.
     */
    public InventoryUpdateQuantityType $updateAction;

    /**
     * @var int The quantity to update.
     */
    public int $quantity;

    /**
     * @var string A note about the inventory update.
     */
    public string $note = '';

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['updateAction', 'quantity', 'inventoryLocationId', 'inventoryId', 'type'], 'required'],
            [['note'], 'string'],
            [['type'], 'in', 'range' => [...InventoryTransactionType::incoming(), 'onHand']],
            [['updateAction'], 'in', 'range' => InventoryUpdateQuantityType::values()],
        ]);
    }
}
