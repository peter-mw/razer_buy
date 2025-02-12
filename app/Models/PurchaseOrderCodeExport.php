<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderCodeExport extends Model
{
    protected $table = 'codes';

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrders::class, 'order_id');
    }

    public static function getWords($record): array
    {
        return [
            'id' => $record->id,
            'account.name' => $record->account?->name,
            'code' => $record->code,
            'serial_number' => $record->serial_number,
            'product.id' => $record->product?->id,
            'product_name' => $record->product_name,
            'product_edition' => $record->product_edition,
            'buy_date' => $record->buy_date,
            'buy_value' => $record->buy_value,
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at,
        ];
    }
}
