<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/aaa', function () {

    $account = \App\Models\Account::find(3);
    //dd($account);

    $service = new \App\Services\RazerService($account);

    $ballance = $service->getAccountBallance();

    dump($ballance);

    //$service->getOrder();

});
