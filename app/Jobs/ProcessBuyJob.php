<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Code;
use App\Models\PendingTransaction;
use App\Models\Product;
use App\Models\PurchaseOrders;
use App\Models\SystemLog;
use App\Models\Transaction;
use App\Notifications\PurchaseOrderCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use App\Models\User;

class ProcessBuyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $purchaseOrderId,
        protected int $quantity
    )
    {
    }

    public function middleware()
    {
        return [(new WithoutOverlapping('ProcessBuyJob' . $this->purchaseOrderId))->dontRelease()];
    }

    public function handle(): void
    {
        $purchaseOrder = PurchaseOrders::findOrFail($this->purchaseOrderId);

        $purchaseOrder->update(['order_status' => 'processing']);

        // Create initial system log
        $log = SystemLog::create([
            'source' => 'ProcessBuyJob',
            'account_id' => $purchaseOrder->account_id,
            'status' => 'processing',
            'command' => 'process_buy',
            'params' => [
                'purchase_order_id' => $purchaseOrder->id,
                'quantity' => $this->quantity,
            ],
        ]);

        // If account_id is set, use that specific account
        if ($purchaseOrder->account_id) {
            $accounts = Account::where('id', $purchaseOrder->account_id)
                ->where('is_active', true)
                ->get();
        } else {
            // Otherwise get active accounts with matching account type, region_id and positive daily limit
            $accounts = Account::select('accounts.*')
                ->join('account_types', 'accounts.account_type', '=', 'account_types.code')
                ->where('accounts.limit_amount_per_day', '>', 0)
                ->where('accounts.account_type', $purchaseOrder->account_type)
                ->where('accounts.is_active', true)
                ->where('account_types.region_id', function ($query) use ($purchaseOrder) {
                    $query->select('region_id')
                        ->from('account_types')
                        ->where('code', $purchaseOrder->account_type)
                        ->first();
                })
                ->get();
        }

        $eligibleAccount = null;
        $ready = [];

        $eligibleAccount = $accounts->first();

        $service = new \App\Services\RazerService($eligibleAccount);
        $account = $eligibleAccount;

        $ballance = $service->getAccountBallance();

        $ballanceResponse = [
            'gold' => $ballance['gold'] ?? 0,
            'silver' => $ballance['silver'] ?? 0,
        ];

        $account->update([
            'ballance_gold' => $ballanceResponse['gold'],
            'ballance_silver' => $ballanceResponse['silver'],
            'last_ballance_update_at' => now(),
            'last_ballance_update_status' => 'success',
        ]);

        $balanceChanged = floatval($ballanceResponse['gold']) !== floatval($account->ballance_gold);
        if ($balanceChanged) {
            $account->balanceHistories()->create([
                'balance_gold' => $ballanceResponse['gold'],
                'balance_silver' => $ballanceResponse['silver'],
                'balance_update_time' => $account->last_ballance_update_at ?? now(),
            ]);
        }

        if ($ballanceResponse['gold'] < $purchaseOrder->buy_value) {
            $purchaseOrder->update(['order_status' => 'not_enough_balance']);

            $log->update([
                'status' => 'error',
                'command' => 'process_buy',
                'params' => [
                    'purchase_order_id' => $purchaseOrder->id,
                    'error' => 'Not enough balance',
                ],
            ]);
        }

        $remainingQuantity = $purchaseOrder->quantity;

        $ordersCompleted = [];
        $buyProductsResults = $service->buyProduct($purchaseOrder, $remainingQuantity);

        if (isset($buyProductsResults['orders'])) {
            $ordersCompleted = array_merge($ordersCompleted, $buyProductsResults['orders']);
        }

        if (empty($ordersCompleted)) {
            $purchaseOrder->update([
                'order_status' => 'failed'
            ]);

            // Increment failed attempts counter and set timestamp
            $account->increment('failed_to_purchase_attempts');
            $account->update([
                'failed_to_purchase_timestamp' => now()
            ]);

            $log->update([
                'status' => 'error',
                'command' => 'process_buy',
                'params' => [
                    'purchase_order_id' => $purchaseOrder->id,
                    'error' => 'No orders completed',
                    'failed_attempts' => $account->failed_to_purchase_attempts,
                ],
            ]);
        }

        foreach ($ordersCompleted as $orderTransactionId) {
            sleep(2);
            try {
                $orderDetails = $service->getTransactionDetails($orderTransactionId);
            } catch (\Exception $e) {
                $purchaseOrder->update([
                    'order_status' => 'failed_get_transaction_details',
                ]);

                SystemLog::create([
                    'source' => 'ProcessBuyJob',
                    'account_id' => $account->id,
                    'status' => 'error',
                    'command' => 'get_transaction_details',
                    'params' => [
                        'account_id' => $account->id,
                        'transaction_id' => $orderTransactionId,
                        'error' => $e->getMessage(),
                    ],
                ]);

                sleep(3);
                try {
                    $orderDetails = $service->getTransactionDetails($orderTransactionId);
                } catch (\Exception $e) {
                    $purchaseOrder->update([
                        'order_status' => 'failed_get_transaction_details',
                    ]);
                    continue;
                }

                // Save to pending transactions
                PendingTransaction::create([
                    'account_id' => $account->id,
                    'product_id' => $purchaseOrder->product_id,
                    'transaction_id' => $orderTransactionId,
                    'status' => 'pending',
                    'error_message' => 'Failed to retrieve transaction details: ' . $e->getMessage(),
                    'transaction_date' => now(),
                ]);
            }

            if (empty($orderDetails)) {
                $purchaseOrder->update([
                    'order_status' => 'failed'
                ]);
                continue;
            }

            $orderDetail = array_pop($orderDetails);

            if (!isset($orderDetail['Code'])) {
                continue;
            }

            $item = [
                'code' => $orderDetail['Code'],
                'serial_number' => $orderDetail['SN'],
                'amount' => $orderDetail['Amount'],
                'buy_date' => date('Y-m-d H:i:s', strtotime($orderDetail['TransactionDate'])),
                'transaction_id' => $orderDetail['SN'],
                'product_name' => $orderDetail['Product'] ?? null
            ];
            $ready[] = $item;
        }

        $totalAmount = 0;

        Log::info('Ready: ' . json_encode($ready));
        Log::info('Purchase Order: ' . json_encode($purchaseOrder));

        // Get the product for region-specific slug lookup
        $product = Product::find($purchaseOrder->product_id);
        $accountType = $account->account_type;

        // Get region-specific product name if available
        $productName = $product->product_name;
        if ($accountType) {
            if (!empty($product->product_slugs)) {
                $slugs = collect($product->product_slugs);
                $regionSlug = $slugs->firstWhere('account_type', $accountType);
                if ($regionSlug && isset($regionSlug['slug'])) {
                    $productName = $regionSlug['slug'];
                }
            } else {
                $productName = $product->product_slug ?? $product->product_name;
            }
        }

        // Create transaction and code for each item
        foreach ($ready as $item) {
            $transaction = Transaction::where('transaction_id', $item['transaction_id'])->first();
            if (!$transaction) {
                $transaction = Transaction::create([
                    'account_id' => $account->id,
                    'amount' => $item['amount'],
                    'product_id' => $purchaseOrder->product_id,
                    'transaction_date' => $item['buy_date'],
                    'transaction_id' => $item['transaction_id'],
                    'order_id' => $purchaseOrder->id
                ]);
                $transaction->save();
            }

            $code = Code::where('code', $item['code'])
                ->where('serial_number', $item['serial_number'])
                ->first();
            if (!$code) {
                // Create code using region-specific product name
                $code = Code::create([
                    'account_id' => $account->id,
                    'code' => $item['code'],
                    'serial_number' => $item['serial_number'],
                    'product_id' => $purchaseOrder->product_id,
                    'product_name' => $productName,
                    'product_edition' => $purchaseOrder->product_edition,
                    'buy_date' => $item['buy_date'],
                    'buy_value' => $item['amount'],
                    'order_id' => $purchaseOrder->id
                ]);
                $code->save();
            }
            $totalAmount += $item['amount'];
        }

        if ($ready) {
            // Update account balance with total amount
            $account->update([
                'ballance_gold' => $account->ballance_gold - $totalAmount
            ]);

            // Update product quantity and status
            $purchaseOrder->update([
                'quantity' => $purchaseOrder->quantity - count($ready),
                'order_status' => 'completed'
            ]);

            $log->update([
                'status' => 'success',
                'command' => 'process_buy',
                'response' => $ready,
                'params' => [
                    'purchase_order_id' => $purchaseOrder->id,
                    'quantity' => count($ready),
                ],
            ]);
        } else {
            // Update purchase order status to failed
            $purchaseOrder->update([
                'order_status' => 'failed'
            ]);

            SystemLog::create([
                'source' => 'ProcessBuyJob',
                'account_id' => $account->id,
                'status' => 'error',
                'command' => 'process_buy',
                'params' => [
                    'account_id' => $account->id,
                    'purchase_order_id' => $purchaseOrder->id,
                    'error' => 'No codes processed',
                ],
            ]);
        }

    }
}
