<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ProductToBuy;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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

    public function getWorkdir()
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


    public function buyProduct(ProductToBuy $productToBuy,$quantity=1)
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;

        $command = [
            normalize_path($workdir . '/razerG.exe', false),
            '-setupKey=' . escapeshellarg($account->otp_seed),
            '-email=' . escapeshellarg($account->email),
            '-password=' . escapeshellarg($account->password),
            '-clientIDlogin=' . escapeshellarg($account->client_id_login),
            '-serviceCode=' . escapeshellarg($account->service_code),
            '-productId=' . escapeshellarg($productToBuy->product_id),
            '-permalink=' . escapeshellarg($productToBuy->product_name),
            '-count=' . escapeshellarg($quantity),
        ];

        $cmd = implode(' ', $command);

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
            2 => ["pipe", "w"]   // stderr is a pipe that the child will write to
        ];


        $process = proc_open($cmd, $descriptorspec, $pipes, $workdir, null);

        if (is_resource($process)) {
            // Close the stdin pipe since we don't need to send any input
            fclose($pipes[0]);

            // Read the output from stdout
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // Read the error output from stderr
            $errorOutput = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // Close the process and get the exit code
            $return_value = proc_close($process);

            if ($return_value !== 0) {
                throw new \RuntimeException("Command failed with error: " . $errorOutput);
            }


            file_put_contents($workdir . '/buy_log.txt', $output);


            return $this->formatOutput($output);
        } else {
            throw new \RuntimeException("Unable to start the process.");
        }
    }

    public function getAccountBallance(): array
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;

        $command = [
            normalize_path($workdir . '/razer-check-balance.exe', false),
            '-email=' . escapeshellarg($account->email),
            '-password=' . escapeshellarg($account->password),
            '-clientIDlogin=' . escapeshellarg($account->client_id_login),
            '-serviceCode=' . escapeshellarg($account->service_code),
        ];

        $cmd = implode(' ', $command);

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
            2 => ["pipe", "w"]   // stderr is a pipe that the child will write to
        ];

        $process = proc_open($cmd, $descriptorspec, $pipes, $workdir, null);

        if (is_resource($process)) {
            // Close the stdin pipe since we don't need to send any input
            fclose($pipes[0]);

            // Read the output from stdout
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // Read the error output from stderr
            $errorOutput = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // Close the process and get the exit code
            $return_value = proc_close($process);

            if ($return_value !== 0) {
                throw new \RuntimeException("Command failed with error: " . $errorOutput);
            }
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
            file_put_contents($workdir . '/balance_log.txt', $output);
            return $return;
        } else {
            throw new \RuntimeException("Unable to start the process.");
        }
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
                    if ($item_data[0] == 'Timestamp' || $item_data[0] == 'TransactionDate') {
                        $item_data_first = array_shift($item_data);
                        $return[$item_data_first] = implode(':', $item_data);
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
