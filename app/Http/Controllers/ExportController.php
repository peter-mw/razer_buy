<?php

namespace App\Http\Controllers;

use App\Models\Code;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use SplTempFileObject;

class ExportController extends Controller
{
    public function exportRemoteCrm(Request $request)
    {
        $discount = $request->get('discount', 0);
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = Code::with(['product', 'account']);

        if ($fromDate && $toDate) {
            $query->whereBetween('buy_date', [$fromDate, $toDate]);
        }

        if ($request->has('account_id')) {
            $query->where('account_id', $request->get('account_id'));
        }

        if ($request->has('product_name')) {
            $query->where('product_name', $request->get('product_name'));
        }

        if ($request->has('serial_number')) {
            $query->where('serial_number', $request->get('serial_number'));
        }
        $query->orderBy('product_name', 'asc');
        $codes = $query->get();


        /*  'account_id',
        'code',
        'serial_number',
        'product_id',
        'product_name',
        'product_edition',
        'buy_date',
        'buy_value',
        'order_id',
        'transaction_ref',
        'transaction_id'*/

        $csv = Writer::createFromFileObject(new SplTempFileObject());

        // Add headers
        $csv->insertOne([
            'Product Name',
            'Cost',
            'Currency',
            'Source',

            'Serial',
            'Number',
            'Cvv',

            'Pin',
            'Expiration',
            //'TransactionId',
            //   'RazerProductId',
            //   'RazerAccountId'

        ]);

        // Add data
        foreach ($codes as $code) {

            $buyValue = $code->buy_value * (1 - $discount / 100);
            $buyValue = number_format($buyValue, 2, '.', '');


            $csv->insertOne([
                $code->product?->remote_crm_product_name ?? $code->product?->product_name ?? '',
                $buyValue,
                'USD',
                $code->account?->name ?? '',

                $code->serial_number,
                '',
                '',


                $code->code,
                '',
                // $code->transaction_id,
                // $code->product_id,
                // $code->account_id

            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="remote-crm-export-' . now() . '.csv"',
        ];

        return response($csv->getContent(), 200, $headers);
    }
}
