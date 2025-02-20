<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Models\AccountTopup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use SplTempFileObject;

class ExportController extends Controller
{
    public function exportCodes(Request $request)
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = Code::with(['product', 'account']);

        if ($fromDate && $toDate) {
            $query->whereBetween('buy_date', [$fromDate, $toDate]);
        }

        $codes = $query->get();

        $csv = Writer::createFromFileObject(new SplTempFileObject());

        // Add headers
        $csv->insertOne([
            'ID',
            'Account',
            'Order ID',
            'Code',
            'Serial Number',
            'Product Name',
            'Remote CRM Product Name',
            'Buy Date',
            'Buy Value',
            'Created At',
            'Updated At'
        ]);

        // Add data
        foreach ($codes as $code) {
            $csv->insertOne([
                $code->id,
                $code->account?->name ?? '',
                $code->order?->id ?? '',
                $code->code,
                $code->serial_number,
                $code->product?->product_name ?? '',
                $code->product?->remote_crm_product_name ?? '',
                $code->buy_date,
                $code->buy_value,
                $code->created_at,
                $code->updated_at
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="codes-export-' . now() . '.csv"',
        ];

        return response($csv->getContent(), 200, $headers);
    }

    public function exportAccountTopups(Request $request)
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = AccountTopup::with(['account']);

        if ($fromDate && $toDate) {
            $query->whereBetween('date', [$fromDate, $toDate]);
        }

        if ($request->has('account_id')) {
            $query->where('account_id', $request->get('account_id'));
        }

        $topups = $query->get();

        $csv = Writer::createFromFileObject(new SplTempFileObject());

        // Add headers
        $csv->insertOne([
            'Account ID',
            'Account Name',
            'Vendor',
            'Transaction ID',
            'Transaction Ref',
            'Topup Amount',
            'Topup Time',
            'Date',
            'Created At',
            'Updated At'
        ]);

        // Add data
        foreach ($topups as $topup) {
            $csv->insertOne([
                $topup->account?->id ?? '',
                $topup->account?->name ?? '',
                $topup->account?->vendor ?? '',
                $topup->transaction_id,
                $topup->transaction_ref,
                $topup->topup_amount,
                $topup->topup_time,
                $topup->date,
                $topup->created_at,
                $topup->updated_at
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="account-topups-export-' . now() . '.csv"',
        ];

        return response($csv->getContent(), 200, $headers);
    }

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
