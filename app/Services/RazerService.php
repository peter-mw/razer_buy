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

        $output = shell_exec($cmd);
        if ($output === null) {
            SystemLog::create([
                'source' => 'RazerService::fetchAllCodes',
                'params' => $params,
                'response' => ['error' => 'Command execution failed'],
                'status' => 'error'
            ]);
            throw new \RuntimeException("Command execution failed");
        }

        file_put_contents($workdir . '/fetch_codes_log.txt', $output);

        $data_items = $this->formatOutput($output);

        dd($data_items);

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
                    if ($item_data[0] == 'Timestamp' || $item_data[0] == 'TransactionDate') {
                        $item_data_first = array_shift($item_data);

                        $str =  implode(':', $item_data);
                        $str = explode('.', $str);
                        //TransactionDate
                        $item_data_first = array_shift($str);


                        $return[$item_data_first] = $item_data_first;

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
