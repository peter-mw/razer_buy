<?php

namespace App\Services;

use App\Models\Account;
use App\Models\PurchaseOrders;
use App\Models\SystemLog;

class RazerService
{
    public ?Account $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
        $this->setUp();
    }

    public function __destruct()
    {
        //   $this->cleaunUp();
    }

    public function getWorkdir(): string
    {
        $dir = storage_path('app/razer/' . $this->account->id);
        if (!file_exists($dir)) {
            mkdir_recursive($dir);
        }
        return normalize_path($dir, true);
    }

    public function setUp()
    {
        $workdir = $this->getWorkdir();

        //copy from bin folder
        $binFolder = base_path('bin');
        $files = [
            'razer-check-balance.exe',
            'razerG.exe',
            'razer-check-transaction.exe',
            'razer-fetchcodes.exe',
        ];

        foreach ($files as $file) {
            $source = $binFolder . '/' . $file;
            $dest = $workdir . '/' . $file;
            if (!file_exists($dest)) {
                copy($source, $dest);
            }
        }
    }

    public function cleaunUp()
    {
        $workdir = $this->getWorkdir();
        if (is_dir($workdir)) {
            rmdir_recursive($workdir);
        }
    }

    public function getTransactionDetails($transactionID)
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;

        $params = [
            'email' => $account->email,
            'password' => $account->password,
            'clientIDlogin' => $account->client_id_login,
            'serviceCode' => $account->service_code,
            'transactionID' => $transactionID,
        ];

        $cmd = sprintf(
            '"%s" -email=%s -password=%s -clientIDlogin=%s -serviceCode=%s -transactionID=%s 2>&1',
            normalize_path($workdir . '/razer-check-transaction.exe', false),
            escapeshellarg($account->email),
            escapeshellarg($account->password),
            escapeshellarg($account->client_id_login),
            trim($account->service_code),
            escapeshellarg($transactionID)
        );

        file_put_contents($workdir . '/transaction_cmd.txt', $cmd);
        chdir($workdir);


        $output = shell_exec($cmd);


        if ($output === null) {
            SystemLog::create([
                'source' => 'RazerService::getTransactionDetails',
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            throw new \RuntimeException("Command execution failed");
        }

        file_put_contents($workdir . '/transaction_log.txt', $output);

        if (str_contains($output, 'Error unmarshalling response: invalid character')) {
            SystemLog::create([
                'source' => 'RazerService::getTransactionDetails',
                'params' => $params,
                'response' => ['error' => 'Error unmarshalling response: invalid character'],
                'status' => 'error'
            ]);
            throw new \RuntimeException("Error unmarshalling response: invalid character");
        }

        $format = $this->formatOutput($output);

        SystemLog::create([
            'source' => 'RazerService::getTransactionDetails',
            'params' => $params,
            'response' => $format,
            'status' => 'success'
        ]);

        return $format;
    }

    public function buyProduct(PurchaseOrders $productToBuy, $quantity = 1)
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;

        $params = [
            'setupKey' => $account->otp_seed,
            'email' => $account->email,
            'password' => $account->password,
            'clientIDlogin' => $account->client_id_login,
            'serviceCode' => $account->service_code,
            'productId' => $productToBuy->product_id,
            'permalink' => $productToBuy->product_name,
            'count' => $quantity,
        ];

        $region_id = 2;
        if ($productToBuy->account_type == 'usa') {
            $region_id = 12;
        }

        $cmd = sprintf(
            '"%s" -setupKey=%s -email=%s -password=%s -clientIDlogin=%s -serviceCode=%s -productId=%d -permalink=%s -regionId=%s -count=%s 2>&1',
            normalize_path($workdir . '/razerG.exe', false),
            escapeshellarg($account->otp_seed),
            escapeshellarg($account->email),
            escapeshellarg($account->password),
            escapeshellarg($account->client_id_login),
            escapeshellarg($account->service_code),
            $productToBuy->product_id,
            escapeshellarg($productToBuy->product_name),
            escapeshellarg($region_id),
            escapeshellarg($quantity)
        );
//dd($cmd);

        // $executalbe = normalize_path($workdir . '/razerG.exe', false);


        file_put_contents($workdir . '/buy_cmd.txt', $cmd);
        chdir($workdir);
        $output = shell_exec($cmd);




        if ($output === null) {
            SystemLog::create([
                'source' => 'RazerService::buyProduct',
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            throw new \RuntimeException("Command execution failed");
        }

        file_put_contents($workdir . '/buy_log.txt', $output);

        if (str_contains($output, 'Error unmarshalling response: invalid character')) {
            SystemLog::create([
                'source' => 'RazerService::buyProduct',
                'params' => $params,
                'response' => ['error' => 'Error unmarshalling response: invalid character'],
                'status' => 'error'
            ]);
            throw new \RuntimeException("Error unmarshalling response: invalid character");
        }

        $format = $this->formatOutputOrder($output);


        if (isset($format['orders'])) {
            SystemLog::create([
                'source' => 'RazerService::buyProduct',
                'params' => $params,
                'response' => $format,
                'status' => 'success'
            ]);


        } else {
            SystemLog::create([
                'source' => 'RazerService::buyProduct',
                'params' => $params,
                'response' => $output,
                'status' => 'error'
            ]);
        }


        return $format;
    }

    public function fetchAllCodes(): array
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;

        $params = [
            'email' => $account->email,
            'password' => $account->password,
            'clientIDlogin' => $account->client_id_login,
            'serviceCode' => $account->service_code,
        ];

        $cmd = sprintf(
            '"%s" -email=%s -password=%s -clientIDlogin=%s -serviceCode=%s 2>&1',
            normalize_path($workdir . '/razer-fetchcodes.exe', false),
            escapeshellarg($account->email),
            escapeshellarg($account->password),
            escapeshellarg($account->client_id_login),
            escapeshellarg($account->service_code)
        );
        chdir($workdir);

        file_put_contents($workdir . '/fetch_codes_cmd.txt', $cmd);
        $output = '';
        //$output = shell_exec($cmd);
        if ($output === null) {
            SystemLog::create([
                'source' => 'RazerService::fetchAllCodes',
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            return [];
        }

       // file_put_contents($workdir . '/fetch_codes_log.txt', $output);

        $output = 'Product: Yalla Ludo - USD 25 Diamonds , Code: QQNPPH1RJ4JU, SN: M19000812173927700178214051538, Amount: 25.930000, Timestamp: 2026-02-12, TransactionDate: 2025-02-12 10:23:10.2005067 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: QJ6R51H12NPX, SN: M11111006173914200219714020816, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:55:20.3792057 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: G2NL6Q2K3P6B, SN: M000010131739142002196914020814, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:55:09.2987206 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: MM23QG22LM6Q, SN: M100811141739140201892514020809, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:58.1059554 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: L5LLQ21GR4JD, SN: M00010114173914020189214020802, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:48.8868597 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: 4JK2RRPNLRGL, SN: M011091041739140201887614020794, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:38.7413916 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: 1Q3R33261PJC, SN: M111101171739140201887514020791, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:28.5139202 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: QNJQ54K5LHP5, SN: M001100031739140201887414020788, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:17.1070337 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: 34H46PPPGQJ2, SN: M011018031739140201885214020782, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:54:07.3621171 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: 1L24P423QQQK, SN: M911111171739140201885114020779, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:53:57.5500227 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: JGH3LK1JR6M3, SN: M00900001173914020188514020776, Amount: 2.070000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:53:45.9619231 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: K6GQJ24MQG4P, SN: M100010141739140201835914020592, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:43:05.9599781 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: 2L6GM5G13JL3, SN: M018110141739140201835714020587, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:56.549023 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: 5N33N54N134C, SN: M010010031739140201835614020585, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:46.5405931 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: 2N23HG4NJMMM, SN: M100111161739140201835314020578, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:36.7135485 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: LRPPRRN2Q32L, SN: M100011151739140201835214020575, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:25.231847 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: LLHMLG541N66, SN: M100119151739140201834514020563, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:14.4864243 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: 1N5H4N21152D, SN: M000180121739140201834414020561, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:42:01.4800845 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: 21NL642P51KJ, SN: M101111171739140201834114020556, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:49.2703297 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: J5N5N243615N, SN: M000901021739140201833914020551, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:39.1808584 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: HNLMNHL63QM1, SN: M101111961739140201833714020547, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:30.0783197 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: 1GQH1N2M3KRD, SN: M119001151739140201833514020543, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:20.4743533 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: LNNRNH2PL3H1, SN: M000108121739140201833414020540, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:10.9542581 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: 15QG6QPGMH6K, SN: M011000141739140201833214020536, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:41:01.5651476 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: G4156L5N1Q3X, SN: M101810141739140201832914020529, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:51.8168159 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: LH4H2GJQ1QNE, SN: M180010021739140201832814020526, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:41.9982816 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: 6Q2GLMR61RK4, SN: M100000021739140201832714020524, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:31.4909747 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: N3M5PMP6M128, SN: M000111041739140201832514020518, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:21.2435698 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: GR543KJJHMK3, SN: M011001041739140201832214020512, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:11.277688 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: H6G4PLH55J1A, SN: M100001141739140201832114020509, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:40:01.2245819 +0000 +0000
Product: Yalla Ludo - USD 25 Diamonds , Code: QPL233H2HMNV, SN: M001101151739140201831714020500, Amount: 25.930000, Timestamp: 2026-02-10, TransactionDate: 2025-02-10 15:39:50.0668884 +0000 +0000
Product: Yalla Ludo - USD 2 Diamonds , Code: 234RR33HL3R1, SN: M00001913173899080192813994061, Amount: 2.070000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:06:14.8000286 +0000 +0000
Product: Yalla Ludo - USD 10 Diamonds , Code: HHJH5KQP4GJT, SN: M000010131738990801927613994053, Amount: 10.370000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:05:07.484608 +0000 +0000
Product: Yalla Ludo - USD 10 Diamonds , Code: J5265HJJPJLT, SN: M00000102173899080192713994042, Amount: 10.370000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:04:00.5656386 +0000 +0000
Product: Yalla Ludo - USD 10 Diamonds , Code: 6QK455NQJGN9, SN: M000001021738990801926813994037, Amount: 10.370000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:02:56.0959854 +0000 +0000
Product: Yalla Ludo - USD 50 Diamonds , Code: L52MML36KRHA, SN: M000110141738990801926713994035, Amount: 51.850000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:01:46.9422659 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: JL3HR3PNHLJ7, SN: M911181041738990801926613994033, Amount: 103.700000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 08:00:45.3998112 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: RMQJPRPPGNL1, SN: M110100151738990801925513994018, Amount: 103.700000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 07:59:43.4133074 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: N5PK14NR6N44, SN: M010819131738990801925313994014, Amount: 103.700000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 07:58:31.2164556 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: L6LNHRH3RLJH, SN: M111108151738990801924813994008, Amount: 103.700000, Timestamp: 2026-02-09, TransactionDate: 2025-02-09 07:57:29.7399137 +0000 +0000
Product: PUBG 60 UC , Code: CyB6cyd42M22B1f8C5, SN: 4488e97caf7f11c4b57560e9e310e8, Amount: 0.990000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:39:26.0513305 +0000 +0000
Product: Yalla Ludo - USD 10 Diamonds , Code: 6H4G61M5QJ45, SN: M881111141738837802741913970600, Amount: 10.370000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:29:52.4925278 +0000 +0000
Product: Yalla Ludo - USD 10 Diamonds , Code: 2NLQ5RJ4K1HJ, SN: M011180031738837802741613970593, Amount: 10.370000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:28:48.9549848 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: 2HKL6LKGHK4C, SN: M001001141738837802741313970583, Amount: 103.700000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:27:47.1749758 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: NNL6Q1J5LQH0, SN: M01018114173883780274113970574, Amount: 103.700000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:26:41.962121 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: MN4HJHLQJL2N, SN: M100101151738837802740613970563, Amount: 103.700000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:25:39.7028218 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: 2MNMG3L4G4JU, SN: M100001031738837802736313970552, Amount: 103.700000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:24:35.3976277 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: L4JN1GHMJ6K1, SN: M011091041738837802736113970546, Amount: 103.700000, Timestamp: 2026-02-07, TransactionDate: 2025-02-07 08:23:22.8588671 +0000 +0000
Product: Yalla Ludo - USD 10 Diamonds , Code: PL2Q36MRPM4K, SN: M111180151738776602848113959753, Amount: 10.370000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:53:05.5864064 +0000 +0000
Product: Yalla Ludo - USD 10 Diamonds , Code: 63HL641GM5JD, SN: M011111061738776602847213959728, Amount: 10.370000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:52:05.8737187 +0000 +0000
Product: Yalla Ludo - USD 10 Diamonds , Code: 22HJ6LJ3NNMX, SN: M110100041738776602846913959719, Amount: 10.370000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:50:58.5523254 +0000 +0000
Product: Yalla Ludo - USD 10 Diamonds , Code: P2MK1L5QRMKV, SN: M000111041738774803241213959704, Amount: 10.370000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:49:54.8499803 +0000 +0000
Product: Yalla Ludo - USD 50 Diamonds , Code: Q2HN3H3N253C, SN: M111191061738774803237313959680, Amount: 51.850000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:48:49.6083961 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: L5Q6K36224H9, SN: M001100141738774803233913959662, Amount: 103.700000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:47:43.8375049 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: 6H6KHH5NP2LN, SN: M011111171738774803232913959655, Amount: 103.700000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:46:42.7502441 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: L6MJJ243P6MQ, SN: M10010003173877480323213959642, Amount: 103.700000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:45:40.9040601 +0000 +0000
Product: Yalla Ludo - USD 100 Diamonds , Code: KGRRPM3JM5MA, SN: M001001031738774803231513959634, Amount: 103.700000, Timestamp: 2026-02-06, TransactionDate: 2025-02-06 13:44:33.8772295 +0000 +0000
';


        $data_items = $this->formatOutput($output);

        $return = $data_items;
        SystemLog::create([
            'source' => 'RazerService::fetchAllCodes',
            'params' => $params,
            'response' => json_encode($return),
            'status' => 'success'
        ]);

        return $return;
    }


    public function getAccountBallance(): array
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;

        $params = [
            'email' => $account->email,
            'password' => $account->password,
            'clientIDlogin' => $account->client_id_login,
            'serviceCode' => $account->service_code,
        ];

        $cmd = sprintf(
            '"%s" -email=%s -password=%s -clientIDlogin=%s -serviceCode=%s 2>&1',
            normalize_path($workdir . '/razer-check-balance.exe', false),
            escapeshellarg($account->email),
            escapeshellarg($account->password),
            escapeshellarg($account->client_id_login),
            escapeshellarg($account->service_code)
        );
        chdir($workdir);

        file_put_contents($workdir . '/buy_ballance.txt', $cmd);

        $output = shell_exec($cmd);
        if ($output === null) {
            SystemLog::create([
                'source' => 'RazerService::getAccountBallance',
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            throw new \RuntimeException("Command execution failed");
        }

        file_put_contents($workdir . '/balance_log.txt', $output);

        $data_items = $this->formatOutput($output);
        $gold = 0;
        $silver = 0;
        if ($data_items) {
            foreach ($data_items as $data_item) {
                if (isset($data_item['Total Gold'])) {
                    $gold = $data_item['Total Gold'];
                }
                if (isset($data_item['Silver Balance'])) {
                    $silver = $data_item['Silver Balance'];
                }
            }
        }

        $return = [
            'gold' => $gold,
            'silver' => $silver,
        ];

        SystemLog::create([
            'source' => 'RazerService::getAccountBallance',
            'params' => $params,
            'response' => $return,
            'status' => 'success'
        ]);

        return $return;
    }

    public function formatOutputOrder($output)
    {
        $alllines = explode("\n", $output);
        $return_lines = [];
        foreach ($alllines as $line) {
            $lines = explode("Order confirmed: ", $line);
            if (!isset($return_lines['orders'])) {
                $return_lines['orders'] = [];
            }

            if (isset($lines[1])) {
                $return_lines['orders'][] = $lines[1];
            }
        }
        return $return_lines;
    }

    public function formatOutput($output)
    {


        $lines = explode("\n", $output);
        $return_lines = [];

        foreach ($lines as $line) {
            $line = str_replace(["+0000 +0000"], '', $line);
            $data = explode(',', $line);

            $data = array_map('trim', $data);
            $return = [];
            foreach ($data as $item) {
                $item_data = explode(': ', $item);
                $item_data = array_map('trim', $item_data);

                if (count($item_data) >= 2) {
                    if ($item_data[0] == 'TransactionDate') {
                         $item_data_first = reset($item_data);

                         $str = implode(': ', $item_data);
                         $str = explode('TransactionDate: ', $str);
                         if(isset($str[1])) {
                             $str = explode('.', $str[1]);
                             $str = $str[0];
                             $item_data_first = $str;
                         }
                        //TransactionDate
                        //$item_data_first = $str[0];


                        $return[$item_data[0]] = $item_data_first;

                    } else {
                        $return[$item_data[0]] = $item_data[1];
                    }
                }
            }
            $return_lines[] = $return;
        }
        $return_lines = array_filter($return_lines);

        return $return_lines;
    }
}
