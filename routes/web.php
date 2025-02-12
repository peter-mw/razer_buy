<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/aaa', function () {

    //fetchAllCodes
    $accountID = 6;
    $account = \App\Models\Account::find($accountID);

    $service = new \App\Services\RazerService($account);
   $codes = $service->fetchAllCodes();

dd($codes);


});



Route::get('/aaa111', function () {

    //fetchAllCodes
    $accountID = 6;
    $productID = '14239';
    $orderId = '75';

    // $account = \App\Models\Account::find(6);
    $account = \App\Models\Account::find($accountID);
    $service = new \App\Services\RazerService($account);

    $account = \App\Models\Account::find($accountID);
    //$account = \App\Models\Account::find(1);

    $service = new \App\Services\RazerService($account);
    //$codes = $service->fetchAllCodes();
    //dd($codes);
    //   $job = new \App\Jobs\FetchAccountCodesJob($account->id);
    //  $job->handle();


    $productTobuy = \App\Models\PurchaseOrders::find($orderId);

    //$buyProduct = $service->buyProduct($productTobuy);

    $job = new \App\Jobs\ProcessBuyJob($productTobuy->id, 1);
    $buyProduct = $job->handle();
    dd($buyProduct);

});

Route::get('/aa11', function () {

    //fetchAllCodes

    $account = \App\Models\Account::find(6);
    $account = \App\Models\Account::find(21);
    //$account = \App\Models\Account::find(1);
    $productTobuy = \App\Models\PurchaseOrders::find(2);

    $service = new \App\Services\RazerService($account);
    //$codes = $service->fetchAllCodes();
    //dd($codes);
    $job = new \App\Jobs\FetchAccountCodesJob($account->id);
    $job->handle();


    // dd($codes);

});

Route::get('/aaa11', function () {

    $outpt = 'Product ID: 14484
Permalink: yalla-ludo
Generating 2 paid links...
Error loading credentials, performing login...
New credentials saved successfully.
Product: Yalla Ludo - USD 5 Diamonds , Code: PG212QRQH5H9, SN: M111108041739136601820314019819, Amount: 5.190000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:04:00.4288986 +0000 +0000
Product: Yalla Ludo - USD 5 Diamonds , Code: 55RPMMHJ6Q3R, SN: M111811161739136601820514019824, Amount: 5.190000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:04:10.9338402 +0000 +0000';


    $outpt = 'Error loading credentials, performing login...
New credentials saved successfully.
2025/02/11 13:15:42 Order confirmed: 122GOXEBJUYM605D0F6D6
';

    $outpt = '
    Product: Yalla Ludo - USD 25 Diamonds , Code: JM232MKK36NT, SN: M010000131739203201689214033809, Amount: 25.930000, Timestamp: 2026-02-11, TransactionDate: 2025-02-11 11:30:15.9067876 +0000 +0000
';

    $account = \App\Models\Account::find(2);
    $productTobuy = \App\Models\PurchaseOrders::find(2);
    //dd($account);
    $order_id = '122GOXECFHFP08FDF6DC8';
    $service = new \App\Services\RazerService($account);

    $ballance = $service->getAccountBallance();
    #dump($ballance);
    $orderOutput = 'Error loading credentials, performing login...
New credentials saved successfully.
2025/02/11 13:55:08 Order confirmed: 122GOXEDWTAQ00A68EA3E
2025/02/11 13:55:16 Order confirmed: 122GOXEDWYHTCDE667D59



';
    $orderOutput = 'Error loading credentials, performing login...
2025/02/11 16:47:00 Order confirmed: 122GOXEV9O445657259EC
2025/02/11 16:47:05 Order confirmed: 122GOXEVAMTVF270D5EF5
2025/02/11 16:47:10 Order confirmed: 122GOXEVAQMH52455E9A3
2025/02/11 16:47:15 Order confirmed: 122GOXEVAUUH2CCCC6CF5
2025/02/11 16:47:19 Order confirmed: 122GOXEVAYI3E8C56AE77
';

    $order_id = '122GOXEVAYI3E8C56AE77';
    dump($service->formatOutputOrder($orderOutput));
    //  dump($service->formatOutput($outpt));
    dd($service->getTransactionDetails($order_id));
    dd($service->getTransactionDetails($order_id));
    $productID = '14484';

    $buyProduct = $service->buyProduct($productTobuy);

    dd($buyProduct);

    //$service->getOrder();

});
