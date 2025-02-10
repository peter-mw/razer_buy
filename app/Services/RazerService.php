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


    public function buyProduct(ProductToBuy $productToBuy)
    {
        // Buy products using the account and product ID
        $workdir = $this->getWorkdir();
        $account = $this->account;


        $command = [
            $workdir . '/razerG.exe',
            '-email=' . escapeshellarg($account->email),
            '-setupKey=' . escapeshellarg($account->otp_seed),
            '-password=' . escapeshellarg($account->password),
            '-clientIDlogin=' . escapeshellarg($account->client_id_login),
            '-serviceCode=' . escapeshellarg($account->service_code),
            '-permalink=' . escapeshellarg($account->service_code),
        ];
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

            $data = $this->formatOutput($output);

            $return = [
                'gold' => $data['Premium Gold'] ?? 0,
                'silver' => $data['Silver Balance'] ?? 0,
            ];

            return $return;
        } else {
            throw new \RuntimeException("Unable to start the process.");
        }
    }

    private function formatOutput($output)
    {
        $return = [];
        $output = str_replace(["\r\n", "\r", "\n"], ',', $output);
        $data = explode(',', $output);
        $data = array_map('trim', $data);

        foreach ($data as $item) {
            $item_data = explode(':', $item);
            $item_data = array_map('trim', $item_data);

            if (count($item_data) >= 2) {
                $return[$item_data[0]] = $item_data[1];
            }

        }

        return $return;
    }
}
