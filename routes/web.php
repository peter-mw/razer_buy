<?php

use App\Services\RazerService;
use Illuminate\Support\Facades\Route;

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

   // $accountID = 11;

    $topups = [];
    $account = \App\Models\Account::find($accountID);
  //  $accountID = 11;
    $razerService = new RazerService($account);
 //   $topups = $razerService->fetchTopUps();
   // $topups = $razerService->fetchAllCodes();

    $job = new \App\Jobs\FetchAccountCodesJob($accountID);

    $job->handle();
    dump($topups);

});


Route::get('/aaa22', function () {

    $output= ' ID: 122GOXW2166W8FC78123A , Product: Yalla Ludo - USD 50 Diamonds , Code: 222HJN3QQRKD, SN: M011911161739304001569614058786, Amount: 51.850000, Timestamp: 2026-02-13, TransactionDate: 2025-02-12 18:39:44.1696727 +0000 +0000
ID: 122GOXW212TI0D1317FF8 , Product: Yalla Ludo - USD 50 Diamonds , Code: 41PKM3L6JR2F, SN: M111810041739304001569514058785, Amount: 51.850000, Timestamp: 2026-02-13, TransactionDate: 2025-02-12 18:39:38.8327933 +0000 +0000
ID: 122GOXW20Z74F3A92F247 , Product: Yalla Ludo - USD 50 Diamonds , Code: QG24K6L466JT, SN: M100180021739304001569514058783, Amount: 51.850000, Timestamp: 2026-02-13, TransactionDate: 2025-02-12 18:39:34.0700532 +0000 +0000
ID: 122GOXVVAWFG42138EBE4 , Product: Yalla Ludo - USD 50 Diamonds , Code: 24552LJMRJQH, SN: M09101014173930040203114057807, Amount: 51.850000, Timestamp: 2026-02-13, TransactionDate: 2025-02-12 17:26:39.7840951 +0000 +0000
ID: 122GOXVV8CK8E709191CF , Product: Yalla Ludo - USD 50 Diamonds , Code: 6K4PNJ6HGJ3V, SN: M109910031739300402030514057795, Amount: 51.850000, Timestamp: 2026-02-13, TransactionDate: 2025-02-12 17:25:20.6964325 +0000 +0000
ID: 122GOXVV6I2X341837925 , Product: Yalla Ludo - USD 50 Diamonds , Code: 2HPP6LJPMH1C, SN: M001110041739300402029814057782, Amount: 51.850000, Timestamp: 2026-02-13, TransactionDate: 2025-02-12 17:24:34.514411 +0000 +0000
ID: 122GOXVV1OXD6E4218FD6 , Product: Yalla Ludo - USD 50 Diamonds , Code: 1KJNJQG21JMF, SN: M111199051739300402028614057743, Amount: 51.850000, Timestamp: 2026-02-13, TransactionDate: 2025-02-12 17:22:10.1270268 +0000 +0000
ID: 122GOXVKET1G17C0811EA , Product: Yalla Ludo - USD 50 Diamonds , Code: KK21HNMLP5KH, SN: M119008131739295001532614055775, Amount: 51.850000, Timestamp: 2026-02-12, TransactionDate: 2025-02-12 15:43:46.1991505 +0000 +0000
ID: 122GOXVJ36T53C6C34DC5 , Product: Yalla Ludo - USD 25 Diamonds , Code: P222QG2G4M2G, SN: M811101151739293202154114055535, Amount: 25.930000, Timestamp: 2026-02-12, TransactionDate: 2025-02-12 15:21:24.5584482 +0000 +0000
ID: 122GOXVC8D2XBADC58776 , Product: Yalla Ludo - USD 50 Diamonds , Code: Q4M1KJGH2JKX, SN: M010001031739287802115614054447, Amount: 51.850000, Timestamp: 2026-02-12, TransactionDate: 2025-02-12 14:06:08.6798199 +0000 +0000
ID: 122GOXUPFHS717E3D8068 , Product: Yalla Ludo - USD 25 Diamonds , Code: QQNPPH1RJ4JU, SN: M19000812173927700178214051538, Amount: 25.930000, Timestamp: 2026-02-12, TransactionDate: 2025-02-12 10:23:10.2005067 +0000 +0000
ID: 122GOWYID15K3D06396DB , Product: Yalla Ludo - USD 2 Diamonds , Code: QJ6R51H12NPX, SN: M11111006173914200219714020816, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:55:20.3792057 +0000 +0000
ID: 122GOWYICSMJ8E22149F4 , Product: Yalla Ludo - USD 2 Diamonds , Code: G2NL6Q2K3P6B, SN: M000010131739142002196914020814, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:55:09.2987206 +0000 +0000
ID: 122GOWYIBP4T2C2AE9B23 , Product: Yalla Ludo - USD 2 Diamonds , Code: MM23QG22LM6Q, SN: M100811141739140201892514020809, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:58.1059554 +0000 +0000
ID: 122GOWYIBI0AA8890A625 , Product: Yalla Ludo - USD 2 Diamonds , Code: L5LLQ21GR4JD, SN: M00010114173914020189214020802, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:48.8868597 +0000 +0000
ID: 122GOWYIBA6L45CFDE113 , Product: Yalla Ludo - USD 2 Diamonds , Code: 4JK2RRPNLRGL, SN: M011091041739140201887614020794, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:38.7413916 +0000 +0000
ID: 122GOWYIB2AGC46E1ADF4 , Product: Yalla Ludo - USD 2 Diamonds , Code: 1Q3R33261PJC, SN: M111101171739140201887514020791, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:28.5139202 +0000 +0000
ID: 122GOWYIATHJ4A29B0EAE , Product: Yalla Ludo - USD 2 Diamonds , Code: QNJQ54K5LHP5, SN: M001100031739140201887414020788, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:17.1070337 +0000 +0000
ID: 122GOWYIALY7360E6135A , Product: Yalla Ludo - USD 2 Diamonds , Code: 34H46PPPGQJ2, SN: M011018031739140201885214020782, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:07.3621171 +0000 +0000
ID: 122GOWYI9IIJEAA0489F0 , Product: Yalla Ludo - USD 2 Diamonds , Code: 1L24P423QQQK, SN: M911111171739140201885114020779, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:53:57.5500227 +0000 +0000
ID: 122GOWYI9AKO59339B385 , Product: Yalla Ludo - USD 2 Diamonds , Code: JGH3LK1JR6M3, SN: M00900001173914020188514020776, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:53:45.9619231 +0000 +0000
ID: 122GOWYHN03C9B885F3C2 , Product: Yalla Ludo - USD 25 Diamonds , Code: K6GQJ24MQG4P, SN: M100010141739140201835914020592, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:43:05.9599781 +0000 +0000
ID: 122GOWYHLXZ9C773BBBB5 , Product: Yalla Ludo - USD 25 Diamonds , Code: 2L6GM5G13JL3, SN: M018110141739140201835714020587, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:56.549023 +0000 +0000
ID: 122GOWYHLQ9FF6024AF5A , Product: Yalla Ludo - USD 25 Diamonds , Code: 5N33N54N134C, SN: M010010031739140201835614020585, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:46.5405931 +0000 +0000
ID: 122GOWYHLIDD4E3604861 , Product: Yalla Ludo - USD 25 Diamonds , Code: 2N23HG4NJMMM, SN: M100111161739140201835314020578, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:36.7135485 +0000 +0000
ID: 122GOWYHL9TG47FD6E42D , Product: Yalla Ludo - USD 25 Diamonds , Code: LRPPRRN2Q32L, SN: M100011151739140201835214020575, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:25.231847 +0000 +0000
ID: 122GOWYHL1IN34AA201CD , Product: Yalla Ludo - USD 25 Diamonds , Code: LLHMLG541N66, SN: M100119151739140201834514020563, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:14.4864243 +0000 +0000
ID: 122GOWYHKREW7881DE94D , Product: Yalla Ludo - USD 25 Diamonds , Code: 1N5H4N21152D, SN: M000180121739140201834414020561, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:01.4800845 +0000 +0000
ID: 122GOWYHJN7X5019F6EDE , Product: Yalla Ludo - USD 25 Diamonds , Code: 21NL642P51KJ, SN: M101111171739140201834114020556, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:49.2703297 +0000 +0000
ID: 122GOWYHJFFV133A18EBC , Product: Yalla Ludo - USD 25 Diamonds , Code: J5N5N243615N, SN: M000901021739140201833914020551, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:39.1808584 +0000 +0000
ID: 122GOWYHJ8EG406F04F62 , Product: Yalla Ludo - USD 25 Diamonds , Code: HNLMNHL63QM1, SN: M101111961739140201833714020547, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:30.0783197 +0000 +0000
ID: 122GOWYHJ10D40A4892FE , Product: Yalla Ludo - USD 25 Diamonds , Code: 1GQH1N2M3KRD, SN: M119001151739140201833514020543, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:20.4743533 +0000 +0000
ID: 122GOWYHITNL59529D26E , Product: Yalla Ludo - USD 25 Diamonds , Code: LNNRNH2PL3H1, SN: M000108121739140201833414020540, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:10.9542581 +0000 +0000
ID: 122GOWYHIMD417CBE4E74 , Product: Yalla Ludo - USD 25 Diamonds , Code: 15QG6QPGMH6K, SN: M011000141739140201833214020536, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:01.5651476 +0000 +0000
ID: 122GOWYHHK04AC0CE8133 , Product: Yalla Ludo - USD 25 Diamonds , Code: G4156L5N1Q3X, SN: M101810141739140201832914020529, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:51.8168159 +0000 +0000
ID: 122GOWYHHCEZ8391B14D2 , Product: Yalla Ludo - USD 25 Diamonds , Code: LH4H2GJQ1QNE, SN: M180010021739140201832814020526, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:41.9982816 +0000 +0000
ID: 122GOWYHH4BQ06BA29042 , Product: Yalla Ludo - USD 25 Diamonds , Code: 6Q2GLMR61RK4, SN: M100000021739140201832714020524, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:31.4909747 +0000 +0000
ID: 122GOWYHGWELA9315AD77 , Product: Yalla Ludo - USD 25 Diamonds , Code: N3M5PMP6M128, SN: M000111041739140201832514020518, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:21.2435698 +0000 +0000
ID: 122GOWYHGOQH87E735249 , Product: Yalla Ludo - USD 25 Diamonds , Code: GR543KJJHMK3, SN: M011001041739140201832214020512, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:11.277688 +0000 +0000
ID: 122GOWYHGGZA69F34C434 , Product: Yalla Ludo - USD 25 Diamonds , Code: H6G4PLH55J1A, SN: M100001141739140201832114020509, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:01.2245819 +0000 +0000
ID: 122GOWYHFC4IDE1B1A0FD , Product: Yalla Ludo - USD 25 Diamonds , Code: QPL233H2HMNV, SN: M001101151739140201831714020500, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:39:50.0668884 +0000 +0000
ID: 122GOWGQE2PL08E9DAA82 , Product: Yalla Ludo - USD 2 Diamonds , Code: 234RR33HL3R1, SN: M00001913173899080192813994061, Amount: 2.070000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:06:14.8000286 +0000 +0000
ID: 122GOWGQBRW27A7CF2477 , Product: Yalla Ludo - USD 10 Diamonds , Code: HHJH5KQP4GJT, SN: M000010131738990801927613994053, Amount: 10.370000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:05:07.484608 +0000 +0000
ID: 122GOWGQ9HEDE098EA7E5 , Product: Yalla Ludo - USD 10 Diamonds , Code: J5265HJJPJLT, SN: M00000102173899080192713994042, Amount: 10.370000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:04:00.5656386 +0000 +0000
ID: 122GOWGQ6DX7F64663B31 , Product: Yalla Ludo - USD 10 Diamonds , Code: 6QK455NQJGN9, SN: M000001021738990801926813994037, Amount: 10.370000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:02:56.0959854 +0000 +0000
ID: 122GOWGQ41PW9A63EBEFF , Product: Yalla Ludo - USD 50 Diamonds , Code: L52MML36KRHA, SN: M000110141738990801926713994035, Amount: 51.850000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:01:46.9422659 +0000 +0000
ID: 122GOWGQ1VD9A9F9C878E , Product: Yalla Ludo - USD 100 Diamonds , Code: JL3HR3PNHLJ7, SN: M911181041738990801926613994033, Amount: 103.700000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:00:45.3998112 +0000 +0000
ID: 122GOWGNLY8I87D11E87F , Product: Yalla Ludo - USD 100 Diamonds , Code: RMQJPRPPGNL1, SN: M110100151738990801925513994018, Amount: 103.700000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 07:59:43.4133074 +0000 +0000
ID: 122GOWGNJJOCC37DFA04C , Product: Yalla Ludo - USD 100 Diamonds , Code: N5PK14NR6N44, SN: M010819131738990801925313994014, Amount: 103.700000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 07:58:31.2164556 +0000 +0000
ID: 122GOWGNHDCI4AD6F828B , Product: Yalla Ludo - USD 100 Diamonds , Code: L6LNHRH3RLJH, SN: M111108151738990801924813994008, Amount: 103.700000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 07:57:29.7399137 +0000 +0000
ID: 122GOVJPM3TM022DDABFA , Product: PUBG 60 UC , Code: CyB6cyd42M22B1f8C5, SN: 4488e97caf7f11c4b57560e9e310e8, Amount: 0.990000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:39:26.0513305 +0000 +0000
ID: 122GOVJP18M31026A8C32 , Product: Yalla Ludo - USD 10 Diamonds , Code: 6H4G61M5QJ45, SN: M881111141738837802741913970600, Amount: 10.370000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:29:52.4925278 +0000 +0000
ID: 122GOVJOZ0PU731F4B466 , Product: Yalla Ludo - USD 10 Diamonds , Code: 2NLQ5RJ4K1HJ, SN: M011180031738837802741613970593, Amount: 10.370000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:28:48.9549848 +0000 +0000
ID: 122GOVJOWU7460E22F421 , Product: Yalla Ludo - USD 100 Diamonds , Code: 2HKL6LKGHK4C, SN: M001001141738837802741313970583, Amount: 103.700000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:27:47.1749758 +0000 +0000
ID: 122GOVJOUL0832E7B4435 , Product: Yalla Ludo - USD 100 Diamonds , Code: NNL6Q1J5LQH0, SN: M01018114173883780274113970574, Amount: 103.700000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:26:41.962121 +0000 +0000
ID: 122GOVJOSDC9CFAADDCBD , Product: Yalla Ludo - USD 100 Diamonds , Code: MN4HJHLQJL2N, SN: M100101151738837802740613970563, Amount: 103.700000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:25:39.7028218 +0000 +0000
ID: 122GOVJOQ5M7B9AC9361D , Product: Yalla Ludo - USD 100 Diamonds , Code: 2MNMG3L4G4JU, SN: M100001031738837802736313970552, Amount: 103.700000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:24:35.3976277 +0000 +0000
ID: 122GOVJONQSI910D9819B , Product: Yalla Ludo - USD 100 Diamonds , Code: L4JN1GHMJ6K1, SN: M011091041738837802736113970546, Amount: 103.700000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:23:22.8588671 +0000 +0000
ID: 122GOV40TVKB033924DCC , Product: Yalla Ludo - USD 10 Diamonds , Code: PL2Q36MRPM4K, SN: M111180151738776602848113959753, Amount: 10.370000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:53:05.5864064 +0000 +0000
ID: 122GOV40RQMN12D6D7686 , Product: Yalla Ludo - USD 10 Diamonds , Code: 63HL641GM5JD, SN: M011111061738776602847213959728, Amount: 10.370000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:52:05.8737187 +0000 +0000
ID: 122GOV40OKXW5FB06ABD4 , Product: Yalla Ludo - USD 10 Diamonds , Code: 22HJ6LJ3NNMX, SN: M110100041738776602846913959719, Amount: 10.370000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:50:58.5523254 +0000 +0000
ID: 122GOV40MCYH31EFF9C85 , Product: Yalla Ludo - USD 10 Diamonds , Code: P2MK1L5QRMKV, SN: M000111041738774803241213959704, Amount: 10.370000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:49:54.8499803 +0000 +0000
ID: 122GOV40K3R002D678C4E , Product: Yalla Ludo - USD 50 Diamonds , Code: Q2HN3H3N253C, SN: M111191061738774803237313959680, Amount: 51.850000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:48:49.6083961 +0000 +0000
ID: 122GOV40HU4881ED7B2E4 , Product: Yalla Ludo - USD 100 Diamonds , Code: L5Q6K36224H9, SN: M001100141738774803233913959662, Amount: 103.700000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:47:43.8375049 +0000 +0000
ID: 122GOV40FO4G94359A240 , Product: Yalla Ludo - USD 100 Diamonds , Code: 6H6KHH5NP2LN, SN: M011111171738774803232913959655, Amount: 103.700000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:46:42.7502441 +0000 +0000
ID: 122GOV40DHJMC9629E55C , Product: Yalla Ludo - USD 100 Diamonds , Code: L6MJJ243P6MQ, SN: M10010003173877480323213959642, Amount: 103.700000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:45:40.9040601 +0000 +0000
ID: 122GOV40B6YMADC0E53DB , Product: Yalla Ludo - USD 100 Diamonds , Code: KGRRPM3JM5MA, SN: M001001031738774803231513959634, Amount: 103.700000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:44:33.8772295 +0000 +0000
';


    //fetchAllCodes
    $accountID = 6;
    $account = \App\Models\Account::find($accountID);

    $service = new \App\Services\RazerService($account);
  // $codes = $service->fetchAllCodes();
   // $codes = $service->fetchTopUps();
 //$codes = $service->formatOutputTopUps($output);
 $codes = $service->formatOutput($output);
    $accountID = 6;
//dd($codes);

    $job = new \App\Jobs\SyncAccountTopupsJob($accountID);
    $buyProduct = $job->handle();
    dd($buyProduct);
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
