<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/aaa', function () {

    $outpt = 'Product ID: 14484
Permalink: yalla-ludo
Generating 2 paid links...
Error loading credentials, performing login...
New credentials saved successfully.
Product: Yalla Ludo - USD 5 Diamonds , Code: PG212QRQH5H9, SN: M111108041739136601820314019819, Amount: 5.190000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:04:00.4288986 +0000 +0000
Product: Yalla Ludo - USD 5 Diamonds , Code: 55RPMMHJ6Q3R, SN: M111811161739136601820514019824, Amount: 5.190000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:04:10.9338402 +0000 +0000';


    $account = \App\Models\Account::find(3);
    $productTobuy = \App\Models\ProductToBuy::find(1);
    //dd($account);

    $service = new \App\Services\RazerService($account);

    $ballance = $service->getAccountBallance();
    dump($ballance);

    dd($service->formatOutput($outpt));
    $productID = '14484';

    $buyProduct = $service->buyProduct($productTobuy);

    dd($buyProduct);

    //$service->getOrder();

});
