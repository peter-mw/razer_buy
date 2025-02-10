<?php

namespace App\Services;

use App\Models\Account;
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


    public function buyProducts($productId, $quantity)
    {
        // Buy products using the account and product ID

    }

    public function getAccountBallance(): array
    {
        $workdir = $this->getWorkdir();
        $account = $this->account;

        $command = [
            $workdir . '/razer-check-balance.exe',
            '-email=' . $account->email,
            '-password=' . $account->password,
            '-clientIDlogin=' . $account->client_id_login,
            '-serviceCode=' . $account->service_code
        ];

        $cmd = implode(' ', $command);
        $output = shell_exec($cmd);

        $data = $this->formatOutput($output);


        $return = [
            'gold' => $data['Premium Gold'] ?? 0,
            'silver' => $data['Silver Balance'] ?? 0,
        ];


        return $return;
    }


    private function formatOutput($output)
    {
        $return = [];
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
