<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Product;
use App\Models\PurchaseOrders;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Cache;

class RazerService
{
    public ?Account $account;
    private string $checkBalanceBin;
    private string $checkTransactionBin;
    private string $fetchCodesBin;
    private string $razerGBin;
    private string $topUpsBin;
    private string $accountReloaderBin;

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
        $dir = storage_path('app/razer/');
        if (!is_dir($dir)) {
            mkdir_recursive($dir);
        }

        $dir = storage_path('app/razer/' . $this->account->id);
        if (!is_dir($dir)) {
            mkdir_recursive($dir);
        }
        return normalize_path($dir, true);
    }

    public function isWindows(): bool
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
            return true;
        }
        return false;
    }

    public function setUp()
    {
        $workdir = $this->getWorkdir();
        $binFolder = base_path('bin');

        // Set binary names based on OS
        $ext = $this->isWindows() ? '.exe' : '_linux';
        $this->checkBalanceBin = 'razer-check-balance' . $ext;
        $this->checkTransactionBin = 'razer-check-transaction' . $ext;
        $this->fetchCodesBin = 'razer-fetchcodes' . $ext;
        $this->razerGBin = 'razerG' . $ext;
        $this->topUpsBin = 'razer-topups' . $ext;
        $this->accountReloaderBin = 'razer-account-reloader' . $ext;

        $files = [
            $this->checkBalanceBin,
            $this->checkTransactionBin,
            $this->fetchCodesBin,
            $this->razerGBin,
            $this->topUpsBin,
            $this->accountReloaderBin,
        ];

        foreach ($files as $file) {
            $source = $binFolder . '/' . $file;
            $dest = $workdir . '/' . $file;
            if (!file_exists($dest)) {
                copy($source, $dest);
                if (!$this->isWindows() and !is_executable($dest)) {
                    chmod($dest, 0755);
                }
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

    public function getAllAccountDetails()
    {
        $topups = $this->fetchTopUps();
        $ballance = $this->getAccountBallance();
        //  $topups = $razerService->fetchAllCodes();
        $codes = $this->fetchAllCodes();
        $accountID = $this->account->id;
        $codesSum = collect($codes)->sum('Amount');
        $topupsSum = collect($topups)->sum('amount');

        $trancasctionsLocal = \App\Models\Transaction::where('account_id', $this->account->id)->get();
        $trancasctionsLocalSum = collect($trancasctionsLocal)->sum('amount');

        //
        $info = [
            'account_id' => $accountID,
            'ballance' => $ballance,
            'codes' => $codes,
            'topups' => $topups,
            'transactions_local_count' => count($trancasctionsLocal),
            'transactions_local_sum' => $trancasctionsLocalSum,
            'codes_sum' => $codesSum,
            'topups_sum' => $topupsSum,
        ];

        return $info;
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
            normalize_path($workdir . '/' . $this->checkTransactionBin, false),
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
                'account_id' => $account->id ?? null,

                'command' => $cmd,
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            return [];
            //   throw new \RuntimeException("Command execution failed");
        }

        file_put_contents($workdir . '/transaction_log.txt', $output);

        if (str_contains($output, 'Error unmarshalling response: invalid character')) {
            SystemLog::create([
                'source' => 'RazerService::getTransactionDetails',
                'account_id' => $account->id ?? null,
                'command' => $cmd,
                'params' => $params,
                'response' => ['error' => 'Error unmarshalling response: invalid character'],
                'status' => 'error'
            ]);
            // throw new \RuntimeException("Error unmarshalling response: invalid character");
        }

        $format = $this->formatOutput($output);

        SystemLog::create([
            'source' => 'RazerService::getTransactionDetails',
            'account_id' => $account->id ?? null,
            'command' => $cmd,
            'params' => $params,
            'response' => $format,
            'status' => 'success'
        ]);

        return $format;
    }

    public function buyProduct(PurchaseOrders $purchaseOrder, $quantity = 1)
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;
        $productData = Product::where('id', $purchaseOrder->product_id)->first();

        $slug = $productData->product_slug;


        $params = [
            'setupKey' => $account->otp_seed,
            'email' => $account->email,
            'password' => $account->password,
            'clientIDlogin' => $account->client_id_login,
            'serviceCode' => $account->service_code,
            'productId' => $productData->id,
            'permalink' => $slug,
            'count' => $quantity,
        ];

        // Get region_id from account type
        $region_id = \App\Models\AccountType::where('code', $purchaseOrder->account_type)
            ->value('region_id') ?? 2; // Default to 2 if not found

        $cmd = sprintf(
            '"%s" -setupKey=%s -email=%s -password=%s -clientIDlogin=%s -serviceCode=%s -productId=%s -permalink=%s -regionId=%s -count=%s 2>&1',
            normalize_path($workdir . '/' . $this->razerGBin, false),
            escapeshellarg($account->otp_seed),
            escapeshellarg($account->email),
            escapeshellarg($account->password),
            escapeshellarg($account->client_id_login),
            escapeshellarg($account->service_code),
            escapeshellarg($purchaseOrder->product_id),
            escapeshellarg($slug),
            escapeshellarg($region_id),
            escapeshellarg($quantity)
        );


        file_put_contents($workdir . '/buy_cmd.txt', $cmd);
        chdir($workdir);

        $output = shell_exec($cmd);


        if ($output === null) {
            SystemLog::create([
                'source' => 'RazerService::buyProduct',
                'account_id' => $account->id ?? null,
                'command' => $cmd,
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
        }

        file_put_contents($workdir . '/buy_log.txt', $output);

        if (str_contains($output, 'Error unmarshalling response: invalid character')) {
            SystemLog::create([
                'source' => 'RazerService::buyProduct',
                'command' => $cmd,
                'params' => $params,
                'response' => ['error' => 'Error unmarshalling response: invalid character'],
                'status' => 'error'
            ]);


        }

        $format = $this->formatOutputOrder($output);


        if (isset($format['orders'])) {
            SystemLog::create([
                'source' => 'RazerService::buyProduct',
                'account_id' => $account->id ?? null,
                'command' => $cmd,
                'params' => $params,
                'response' => $format,
                'status' => 'success'
            ]);


        } else {
            SystemLog::create([
                'source' => 'RazerService::buyProduct',
                'account_id' => $account->id ?? null,
                'command' => $cmd,
                'params' => $params,
                'response' => $output,
                'status' => 'error'
            ]);
        }


        return $format;
    }

    public function fetchTopUpsCached(): array
    {

        $cache = Cache::remember('razer_topups_cache_2' . $this->account->id, 60000, function () {
            return $this->fetchTopUps();
        });
        return $cache;
    }

    public function fetchTopUps(): array
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;

        $this->getAccountBallance();
        $creds = $workdir . '/balance_credentials.txt';

        if (!is_file($creds)) {
            return [];
        }

        copy($creds, $workdir . '/credentials.txt');


        $params = [
            'email' => $account->email,
            'password' => $account->password,
            'clientIDlogin' => $account->client_id_login,
            'serviceCode' => $account->service_code,
        ];

        if ($this->isWindows()) {
            $cmd = sprintf(
                '"%s" -email=%s -password=%s -clientIDlogin=%s -serviceCode=%s 2>&1',
                normalize_path($workdir . '/' . $this->topUpsBin, false),
                escapeshellarg($account->email),
                escapeshellarg($account->password),
                escapeshellarg($account->client_id_login),
                escapeshellarg($account->service_code)
            );
        } else {
            $cmd = sprintf(
                'cd %s && %s -email=%s -password=%s -clientIDlogin=%s -serviceCode=%s 2>&1',
                normalize_path($workdir, true),
                normalize_path($workdir . $this->topUpsBin, false),
                escapeshellarg($account->email),
                escapeshellarg($account->password),
                escapeshellarg($account->client_id_login),
                escapeshellarg($account->service_code)
            );
        }


        chdir($workdir);

        file_put_contents($workdir . '/topups_cmd.txt', $cmd);
        $output = shell_exec($cmd);

        if ($output === null) {
            SystemLog::create([
                'source' => 'RazerService::fetchTopUps',
                'account_id' => $account->id ?? null,
                'command' => $cmd,
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            return [];
        }

        file_put_contents($workdir . '/topups_log.txt', $output);

        $data_items = $this->formatOutputTopUps($output);

        //filter only status = 1

        $data_items = array_filter($data_items, function ($item) {
            return isset($item['status']) and intval($item['status']) == 1;
        });
        // filter only whn product name does not coninain Refund:
        $data_items = array_filter($data_items, function ($item) {
            return strpos($item['product'], 'Refund:') === false;
        });

        SystemLog::create([
            'source' => 'RazerService::fetchTopUps',
            'account_id' => $account->id ?? null,
            'command' => $cmd,
            'params' => $params,
            'response' => $data_items,
            'status' => 'success'
        ]);

        return $data_items;
    }

    public function fetchAllCodesCached(): array
    {
        $cache = Cache::remember('razer_codes_212131122122' . $this->account->id, 60000, function () {
            return $this->fetchAllCodes();
        });
        return $cache;
    }

    public function fetchAllCodes(): array
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;


        $this->getAccountBallance();
        $creds = $workdir . '/balance_credentials.txt';
        copy($creds, $workdir . '/credentials.txt');

        $params = [
            'email' => $account->email,
            'password' => $account->password,
            'clientIDlogin' => $account->client_id_login,
            'serviceCode' => $account->service_code,
        ];

        $cmd = sprintf(
            '"%s" -email=%s -password=%s -clientIDlogin=%s -serviceCode=%s 2>&1',
            normalize_path($workdir . '/' . $this->fetchCodesBin, false),
            escapeshellarg($account->email),
            escapeshellarg($account->password),
            escapeshellarg($account->client_id_login),
            escapeshellarg($account->service_code)
        );
        chdir($workdir);

        file_put_contents($workdir . '/fetch_codes_cmd.txt', $cmd);
        $output = '';
        $output = shell_exec($cmd);
        if ($output === null) {
            SystemLog::create([
                'source' => 'RazerService::fetchAllCodes',
                'command' => $cmd,
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            return [];
        }

        file_put_contents($workdir . '/fetch_codes_log.txt', $output);


        $data_items = $this->formatOutput($output);


        $return = $data_items;


        SystemLog::create([
            'source' => 'RazerService::fetchAllCodes',
            'account_id' => $account->id ?? null,
            'command' => $cmd,
            'params' => $params,
            'response' => $return,
            'status' => 'success'
        ]);

        return $return;
    }

    public function validateAccount(): array
    {
        $isValid = true;
        $workdir = $this->getWorkdir();
        $account = $this->account;
        $this->getAccountBallance();
        $creds = $workdir . '/balance_credentials.txt';

        if (!is_file($creds)) {
            return [];
        }

        @copy($creds, $workdir . '/credentials.txt');

        $checkBalance = $this->getAccountBallance();
        $topups = $this->fetchTopUps();

        //  $codes = $this->fetchAllCodes();

        //  dd($codes);

        if ($checkBalance['gold'] == 0 && $checkBalance['silver'] == 0) {
            $isValid = false;
        }

        $regionId = $checkBalance['region_id'] ?? 0;
        $limit = $checkBalance['limit'] ?? 0;
        $tier = $checkBalance['tier'] ?? 0;

        if ($topups == null) {
            // $isValid = false;
        }

        $topupsCount = count($topups);

        if ($isValid) {
            return [
                'status' => 'success',
                'isValid' => true,
                'gold' => $checkBalance['gold'],
                'silver' => $checkBalance['silver'],
                'topups' => $topupsCount,
                'region_id' => $regionId,
                'limit' => $limit,
                'tier' => $tier,
                'message' => 'Account is valid, balance: ' . $checkBalance['gold'] . ' gold, ' . $checkBalance['silver'] . ' silver, ' . $topupsCount . ' topups' . ' region_id: ' . $regionId . ' limit: ' . $limit . ' tier: ' . $tier . ' region_id: ' . $regionId . ' limit: ' . $limit . ' tier: ' . $tier
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Account is invalid, balance: ' . $checkBalance['gold'] . ' gold, ' . $checkBalance['silver'] . ' silver, ' . $topupsCount . ' topups' . ' region_id: ' . $regionId . ' limit: ' . $limit . ' tier: ' . $tier . ' region_id: ' . $regionId . ' limit: ' . $limit . ' tier: ' . $tier
            ];
        }

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
            normalize_path($workdir . '/' . $this->checkBalanceBin, false),
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
                'account_id' => $account->id ?? null,
                'command' => $cmd,
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            return ['gold' => 0, 'silver' => 0];
        }

        file_put_contents($workdir . '/balance_log.txt', $output);

        $data_items = $this->formatOutput($output);
        $gold = 0;
        $silver = 0;
        $limit = 0;
        $region_id = 0;
        $tier = 0;
        if ($data_items) {
            foreach ($data_items as $data_item) {
                if (isset($data_item['Total Gold'])) {
                    $gold = $data_item['Total Gold'];
                }
                if (isset($data_item['Silver Balance'])) {
                    $silver = $data_item['Silver Balance'];
                }

                if (isset($data_item['Limit'])) {
                    $limit = $data_item['Limit'];
                }
                if (isset($data_item['Region ID'])) {
                    $region_id = $data_item['Region ID'];
                }
                if (isset($data_item['Tier'])) {
                    $tier = $data_item['Tier'];
                }


                /*"""
Premium Gold: 0.050000, Bonus Gold: 0, Total Gold: 0.050000, Limit: 600, Currency Code: USD, Tier: 2, Region ID: 12
Silver Balance: 1500000, Next Expiring Bonus Silver: 15245, Next Expiring Bonus Silver DateTime: 2026-02-28 23:59:59.997 +0000 UTC, Silver Balance: 1524*/
            }
        }

        $return = [
            'gold' => $gold,
            'silver' => $silver,
            'limit' => $limit,
            'region_id' => $region_id,
            'tier' => $tier,
        ];

        SystemLog::create([
            'source' => 'RazerService::getAccountBallance',
            'account_id' => $account->id ?? null,
            'command' => $cmd,
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

    public function formatOutputTopReload($output)
    {
        // Extract JSON from the log output
        if (preg_match('/Response: ({.+})/s', $output, $matches)) {
            $jsonStr = $matches[1];
            $data = json_decode($jsonStr, true);

            if ($data) {
                /*2025/03/02 13:31:57 Response: {"id":103107844,"transactionNumber":"012GQ37NGZLV22DCD21D4","goldTransactionTypeId":1,"walletId":12103331,"regionId":12,"description":"Razer Gold PIN","transactionStatusId":1,"paymentStatusId":1,"paymentChannelId":51,"currencyCode":"USD","gold":5,"premiumGold":5,"bonusGold":0,"bonusExpirationPeriod":0,"ipAddress":"5.181.81.5","countryCode":"BG","userAgent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36","token":"81e2bbd212ee47e6956487431c93079fb232c253","transactionSourceId":0,"otpTypeId":null,"otpDateTime":null,"otpMobileNumber":null,"transactionDateTime":"2025-03-02T13:31:56.6777774+00:00","redirectUrl":null,"returnUrl":null,"paymentAmount":5,"paymentMethod":"Razer Gold PIN","surcharge":null,"promotions":[]}
*/
                return [
                    [
                        'id' => $data['id'] ?? null,
                        'transactionNumber' => $data['transactionNumber'] ?? null,
                        'goldTransactionTypeId' => $data['goldTransactionTypeId'] ?? null,
                        'walletId' => $data['walletId'] ?? null,
                        'regionId' => $data['regionId'] ?? null,
                        'description' => $data['description'] ?? null,
                        'transactionStatusId' => $data['transactionStatusId'] ?? null,
                        'paymentStatusId' => $data['paymentStatusId'] ?? null,
                        'paymentChannelId' => $data['paymentChannelId'] ?? null,
                        'currencyCode' => $data['currencyCode'] ?? null,
                        'gold' => $data['gold'] ?? null,
                        'premiumGold' => $data['premiumGold'] ?? null,
                        'bonusGold' => $data['bonusGold'] ?? null,
                        'bonusExpirationPeriod' => $data['bonusExpirationPeriod'] ?? null,
                        'ipAddress' => $data['ipAddress'] ?? null,
                        'countryCode' => $data['countryCode'] ?? null,
                        'userAgent' => $data['userAgent'] ?? null,
                        'token' => $data['token'] ?? null,
                        'transactionSourceId' => $data['transactionSourceId'] ?? null,
                        'otpTypeId' => $data['otpTypeId'] ?? null,
                        'otpDateTime' => $data['otpDateTime'] ?? null,
                        'otpMobileNumber' => $data['otpMobileNumber'] ?? null,
                        'transactionDateTime' => $data['transactionDateTime'] ?? null,
                        'redirectUrl' => $data['redirectUrl'] ?? null,
                        'returnUrl' => $data['returnUrl'] ?? null,
                        'paymentAmount' => $data['paymentAmount'] ?? null,
                        'paymentMethod' => $data['paymentMethod'] ?? null,
                        'surcharge' => $data['surcharge'] ?? null,
                    ]
                ];
            }
        }

        return [];
    }

    public function formatOutputTopUps($output)
    {
        $lines = explode("\n", $output);
        $result = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $item = [];

            // Extract Product
            if (preg_match('/Product:\s+(.*?)\s+(?:Refund:|Transaction:)/', $line, $match)) {
                $item['product'] = trim($match[1]);
            }

            // Extract Transaction
            if (preg_match('/Transaction:\s+(\S+)/', $line, $match)) {
                $item['transaction'] = $match[1];
            }

            if (preg_match('/Status:\s+(\S+)/', $line, $match)) {
                $item['status'] = $match[1];
            }

            // Extract Amount
            if (preg_match('/Amount:\s+([\d\.]+)/', $line, $match)) {
                $item['amount'] = $match[1];
            }

            // Extract Timestamp
            if (preg_match('/Timestamp:\s+([\d\-:\. ]+)/', $line, $match)) {
                $timestamp = explode('.', $match[1])[0];
                $item['timestamp'] = trim($timestamp);
            }

            // Extract TransactionDate
            if (preg_match('/TransactionDate:\s+([\d\-:\. ]+)/', $line, $match)) {
                $transactionDate = explode('.', $match[1])[0];
                $item['transaction_date'] = trim($transactionDate);
            }

            if (!empty($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    public function reloadAccount(string $code): array
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;

        $params = [
            'email' => $account->email,
            'password' => $account->password,
            'clientIDlogin' => $account->client_id_login,
            'serviceCode' => $account->service_code,
            'code' => $code,
        ];

        // Get region_id from account type
        $region_id = \App\Models\AccountType::where('code', $account->account_type)
            ->value('region_id') ?? 2; // Default to 2 if not found

        $cmd = sprintf(
            '"%s" -email=%s -password=%s -clientIDlogin=%s -serviceCode=%s -regionId=%s -code=%s 2>&1',
            normalize_path($workdir . '/' . $this->accountReloaderBin, false),
            escapeshellarg($account->email),
            escapeshellarg($account->password),
            escapeshellarg($account->client_id_login),
            escapeshellarg($account->service_code),
            escapeshellarg($region_id),
            escapeshellarg($code)
        );

        file_put_contents($workdir . '/reload_cmd.txt', $cmd);
        chdir($workdir);

        $output = shell_exec($cmd);

        if ($output === null) {
            SystemLog::create([
                'source' => 'RazerService::reloadAccount',
                'account_id' => $account->id ?? null,
                'command' => $cmd,
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            return ['status' => 'error', 'message' => 'Command execution failed'];
        }

        file_put_contents($workdir . '/reload_log.txt', $output);

        $format = $this->formatOutputTopReload($output);
        $error = $format && !empty($format['transactionNumber']) ? false : true;
        SystemLog::create([
            'source' => 'RazerService::reloadAccount',
            'account_id' => $account->id ?? null,
            'command' => $cmd,
            'params' => $params,
            'response' => $format,
            'status' => $error ? 'error' : 'success'
        ]);

        return [
            'status' => $error ? 'error' : 'success',
            'data' => $format
        ];
    }

    public function formatOutput($output)
    {


        $lines = explode("\n", $output);
        $return_lines = [];

        foreach ($lines as $line) {
            $line = str_replace(["+0000 +0000"], '', $line);
            $data = explode(', ', $line);

            $data = array_map('trim', $data);
            $return = [];
            foreach ($data as $item) {

                $item_data = explode(':', $item);
                $item_data = array_map('trim', $item_data);

                if (count($item_data) >= 2) {
                    if ($item_data[0] == 'TransactionDate') {
                        $item_data_first = reset($item_data);

                        $str = implode(':', $item_data);
                        $str = explode('TransactionDate:', $str);
                        if (isset($str[1])) {
                            $str = explode('.', $str[1]);
                            $str = $str[0];
                            $item_data_first = $str;
                        }
                        //TransactionDate


                        $return[$item_data[0]] = $item_data_first;

                    } else {
                        $return[$item_data[0]] = $item_data[1];
                    }
                }
            }
            //ss  $return = array_map('trim', $return);
            $return_lines[] = $return;
        }


        $return_lines = array_filter($return_lines);

        return $return_lines;
    }
}
