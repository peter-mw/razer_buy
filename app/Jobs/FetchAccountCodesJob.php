<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Code;
use App\Models\PendingTransaction;
use App\Models\Product;
use App\Models\PurchaseOrders;
use App\Models\SystemLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RazerService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FetchAccountCodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $accountId
    )
    {
    }

    public function middleware()
    {
        return [(new WithoutOverlapping('FetchAccountCodesJob' . $this->accountId))->dontRelease()];
    }

    public function handle(): void
    {
        $account = Account::findOrFail($this->accountId);
        $service = new RazerService($account);

        // Create initial system log
        SystemLog::create([
            'source' => 'FetchAccountCodesJob',
            'account_id' => $account->id,
            'status' => 'processing',
            'command' => 'fetch_codes',
            'params' => ['account_id' => $account->id],
        ]);

        // Fetch all codes for the account
        try {
            $codes = $service->fetchAllCodes();
            if (!empty($codes)) {
                // Track if we found any new codes
                $hasNewCodes = false;
                $firstNewCode = null;
                $processedCodes = [];

                // First pass - check for new codes
                foreach ($codes as $codeData) {
                    $code = $codeData['Code'];
                    $serialNumber = $codeData['SN'];

                    // Check if code already exists
                    $existingCode = Code::where('code', $code)
                        ->orWhere('serial_number', $serialNumber)
                        ->first();

                    if (!$existingCode) {
                        $hasNewCodes = true;
                        if (!$firstNewCode) {
                            $firstNewCode = $codeData;
                        }
                        $processedCodes[] = $codeData;
                    }
                }

                // Only create order and process codes if we found new ones
                if ($hasNewCodes && $firstNewCode) {
                    $productName = $firstNewCode['Product'];
                    $product = Product::where('product_name', 'LIKE', '%' . $productName . '%')->first();

                    if (!$product) {
                        $product = Product::create([
                            'product_name' => $productName,
                            'product_slug' => Str::slug($productName),
                            'account_type' => 'unknown',
                            'product_edition' => 'unknown',
                            'product_buy_value' => 0,
                            'product_face_value' => 0,
                        ]);
                        $product->save();
                    }

                    $newOrder = PurchaseOrders::create([
                        'product_id' => $product->id,
                        'product_name' => $product->product_name,
                        'account_type' => $product->account_type,
                        'product_edition' => $product->product_edition,
                        'buy_value' => $product->product_buy_value,
                        'product_face_value' => $product->product_face_value,
                        'quantity' =>0,
                        'order_status' => 'completed',
                        'account_id' => $account->id
                    ]);

                    // Process only the new codes
                    foreach ($processedCodes as $codeData) {
                        $this->processCode($account, $codeData, $newOrder);
                    }

                    // Log successful code processing
                    SystemLog::create([
                        'source' => 'FetchAccountCodesJob',
                        'account_id' => $account->id,
                        'status' => 'success',
                        'command' => 'process_codes',
                        'params' => [
                            'account_id' => $account->id,
                            'new_codes_count' => count($processedCodes),
                            'product_name' => $product->product_name,
                        ],
                    ]);
                }


                // Update account balance after processing
                $this->updateAccountBalance($account, $service);
            }
        } catch (\Exception $e) {
            SystemLog::create([
                'source' => 'FetchAccountCodesJob',
                'account_id' => $account->id,
                'status' => 'error',
                'command' => 'fetch_codes',
                'params' => [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ],
            ]);
            Log::error('Failed to fetch codes: ' . $e->getMessage());
            return;
        }




    }

    protected function processCode(Account $account, array $codeData, PurchaseOrders $order): void
    {
        // Extract data from code response
        $code = $codeData['Code'];
        $serialNumber = $codeData['SN'];
        $productName = $codeData['Product'];
        $amount = floatval($codeData['Amount']);
        $buyDate = date('Y-m-d H:i:s', strtotime($codeData['TransactionDate']));


        // Find matching product by name
        $product = Product::where('product_name', 'LIKE', '%' . $productName . '%')->first();

        if (!$product) {
            Log::warning("Product not found for code: {$code}, product name: {$productName}");
            return;
        }

        // Create transaction and code records for the shared order
        $transaction = Transaction::create([
            'account_id' => $account->id,
            'amount' => $amount,
            'product_id' => $product->id,
            'transaction_date' => $buyDate,
            'transaction_id' => 'sync-' . $code,
            'order_id' => $order->id
        ]);

        Code::create([
            'account_id' => $account->id,
            'code' => $code,
            'serial_number' => $serialNumber,
            'product_id' => $product->id,
            'product_name' => $productName,
            'buy_date' => $buyDate,
            'buy_value' => $amount,
            'order_id' => $order->id
        ]);
    }

    public function updateAccountBalance(Account $account, RazerService $service): void
    {
        try {
            $balance = $service->getAccountBallance();

            $account->update([
                'ballance_gold' => $balance['gold'] ?? 0,
                'ballance_silver' => $balance['silver'] ?? 0,
                'last_ballance_update_at' => now(),
                'last_ballance_update_status' => 'success',
            ]);

            // Create balance history record
            $account->balanceHistories()->create([
                'balance_gold' => $balance['gold'] ?? 0,
                'balance_silver' => $balance['silver'] ?? 0,
                'balance_update_time' => now(),
            ]);
        } catch (\Exception $e) {
            SystemLog::create([
                'source' => 'FetchAccountCodesJob',
                'account_id' => $account->id,
                'status' => 'error',
                'command' => 'update_balance',
                'params' => [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ],
            ]);
            Log::error('Failed to update account balance: ' . $e->getMessage());
            $account->update([
                'last_ballance_update_at' => now(),
                'last_ballance_update_status' => 'error',
            ]);
        }
    }
}
