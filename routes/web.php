<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return view('welcome');
});




Route::get('/aaa', function () {
    $accountID = 8;
    $accountID = 57;
    $accountID = 11;
    $accountID = 5;
    // $accountID = 23;
    // $accountID = 20;
    // $accountID = 43;

  $accountID = 8;
  $accountID = 3;
  $accountID = 5;
  $accountID = 36;

    $topups = [];
    $account = \App\Models\Account::find($accountID);

    //  $accountID = 11;
    $razerService = new \App\Services\RazerService($account);
    $topups = $razerService->getAccountBallance();
    //$codes = $razerService->fetchAllCodes();
   // $detail = $razerService->getAllAccountDetails();
    dd($topups);

 //  $topups = $razerService->fetchTopUps();
//dd($topups);
     $job = new \App\Jobs\FetchAccountCodesJob($accountID);

    $job->handle();

    dump('done');
return 'done';
    $topups = $razerService->fetchTopUps();
    $ballance = $razerService->getAccountBallance();
  //  $topups = $razerService->fetchAllCodes();
     $codes = $razerService->fetchAllCodes();

    $codesSum = collect($codes)->sum('Amount');
    $topupsSum = collect($topups)->sum('amount');

    $trancasctionsLocal  = \App\Models\Transaction::where('account_id',$accountID)->get();
    $trancasctionsLocalSum = collect($trancasctionsLocal)->sum('amount');

    //
    $info = [
        'account_id' => $accountID,
        'ballance' => $ballance,
        'codes' => $codes,
        'topups' => $topups,
        'trancasctionsLocal' => $trancasctionsLocal,
        'trancasctionsLocalSum' => $trancasctionsLocalSum,
        'codesSum' => $codesSum,
        'topupsSum' => $topupsSum,
    ];

    dd($info);
    dump($topups);

});


Route::middleware(['auth'])->group(function () {
    Route::get('/export/remote-crm', [ExportController::class, 'exportRemoteCrm'])->name('export.remote-crm');
Route::get('/export/codes', [ExportController::class, 'exportCodes'])->name('export.codes');
});
