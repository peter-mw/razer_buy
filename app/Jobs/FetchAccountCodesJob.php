<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Code;
use App\Models\PendingTransaction;
use App\Models\Product;
use App\Models\PurchaseOrders;
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
        return [(new WithoutOverlapping('FetchAccountCodesJob'.$this->accountId))->dontRelease()];
    }
    public function handle(): void
    {
        $account = Account::findOrFail($this->accountId);
        $service = new RazerService($account);

        // Fetch all codes for the account
        $codes = $service->fetchAllCodes();



        if (!empty($codes)) {
            // Create a single order for all codes
            $firstCode = $codes[0];
            $productName = $firstCode['Product'];


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

                'quantity' => 0,
                'order_status' => 'completed',
                'account_id' => $account->id
            ]);

            foreach ($codes as $codeData) {
                $this->processCode($account, $codeData, $newOrder);
            }
        }

        // Update account balance after processing
        $this->updateAccountBalance($account, $service);
    }

    protected function processCode(Account $account, array $codeData, PurchaseOrders $order): void
    {

        // Extract data from code response
        $code = $codeData['Code'];
        $serialNumber = $codeData['SN'];
        $productName = $codeData['Product'];
        $amount = floatval($codeData['Amount']);
        $buyDate = date('Y-m-d H:i:s', strtotime($codeData['TransactionDate']));

        // Check if code already exists
        $existingCode = Code::where('code', $code)
            ->orWhere('serial_number', $serialNumber)
            ->first();

        if ($existingCode) {
            return; // Skip if code already exists
        }


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
            'transaction_id' => 'unknown-' . $code,
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

    protected function updateAccountBalance(Account $account, RazerService $service): void
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
            Log::error('Failed to update account balance: ' . $e->getMessage());
            $account->update([
                'last_ballance_update_at' => now(),
                'last_ballance_update_status' => 'error',
            ]);
        }
    }
}
