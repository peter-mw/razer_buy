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

        $files = [
            $this->checkBalanceBin,
            $this->checkTransactionBin,
            $this->fetchCodesBin,
            $this->razerGBin,
            $this->topUpsBin,
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
                'command' => $cmd,
                'params' => $params,
                'response' => $format,
                'status' => 'success'
            ]);


        } else {
            SystemLog::create([
                'source' => 'RazerService::buyProduct',
                'command' => $cmd,
                'params' => $params,
                'response' => $output,
                'status' => 'error'
            ]);
        }


        return $format;
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
                './' . $this->topUpsBin,
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
                'command' => $cmd,
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            return [];
        }

        file_put_contents($workdir . '/topups_log.txt', $output);

        $data_items = $this->formatOutputTopUps($output);

        SystemLog::create([
            'source' => 'RazerService::fetchTopUps',
            'command' => $cmd,
            'params' => $params,
            'response' => $data_items,
            'status' => 'success'
        ]);

        return $data_items;
    }

    public function fetchAllCodesCached(): array
    {
        $cache = Cache::remember('razer_codes_' . $this->account->id, 60000, function () {
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

        $checkBalance = $this->getAccountBallance();
        $topups = $this->fetchTopUps();

        //  $codes = $this->fetchAllCodes();

        //  dd($codes);

        if ($checkBalance['gold'] == 0 && $checkBalance['silver'] == 0) {
            $isValid = false;
        }

        if ($topups == null) {
            $isValid = false;
        }

        $topupsCount = count($topups);

        if ($isValid) {
            return [
                'status' => 'success',
                'message' => 'Account is valid, balance: ' . $checkBalance['gold'] . ' gold, ' . $checkBalance['silver'] . ' silver, ' . $topupsCount . ' topups'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Account is invalid, balance: ' . $checkBalance['gold'] . ' gold, ' . $checkBalance['silver'] . ' silver, ' . $topupsCount . ' topups'
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

    public function formatOutputTopUps($output)
    {

        $pattern = '/Product:\s+(.*?)\s+Transaction:\s+(\S+)\s+Amount:\s+(\d+)\s+Timestamp:\s+([\d\-:\. ]+\d{7})\s+\+\d{4}\s+\+\d{4}\s+TransactionDate:\s+([\d\-:\. ]+\d{7})\s+\+\d{4}\s+\+\d{4}/';
        preg_match_all($pattern, $output, $matches, PREG_SET_ORDER);


        $result = [];
        foreach ($matches as $match) {
            $item = [
                'product' => $match[1] ?? '',
                'transaction' => $match[2] ?? '',
                'amount' => $match[3] ?? '',
                'timestamp' => $match[4] ?? '',
                'transaction_date' => $match[5] ?? '',
            ];
            if ($item['transaction_date']) {
                $str = explode('.', $item['transaction_date']);
                $item['transaction_date'] = $str[0];
            }

            $result[] = $item;

        }

        return $result;
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
