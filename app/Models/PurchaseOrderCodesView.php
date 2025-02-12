<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Sushi\Sushi;

class PurchaseOrderCodesView extends Model
{
    use Sushi;

    public function getRows()
    {
        return DB::table('purchase_orders')
            ->join('codes', 'purchase_orders.id', '=', 'codes.order_id')
            ->leftJoin('accounts', 'codes.account_id', '=', 'accounts.id')
            ->leftJoin('products', 'codes.product_id', '=', 'products.id')
            ->select([
                'codes.id as code_id',
                'accounts.name as account_name',
                'codes.code',
                'codes.serial_number',
                'products.id as product_id',
                'codes.product_name',
                'codes.product_edition',
                'codes.buy_date',
                'codes.buy_value',
                'codes.created_at',
                'codes.updated_at'
            ])
            ->get()
            ->toArray();
    }
}
