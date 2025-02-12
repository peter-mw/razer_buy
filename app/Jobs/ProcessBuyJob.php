<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Code;
use App\Models\PendingTransaction;
use App\Models\PurchaseOrders;
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
        return [(new WithoutOverlapping('ProcessBuyJob'.$this->purchaseOrderId))->dontRelease()];
    }

    public function handle(): void
    {
        $purchaseOrder = PurchaseOrders::findOrFail($this->purchaseOrderId);

        $purchaseOrder->update(['order_status' => 'processing']);

        // If account_id is set, use that specific account
        if ($purchaseOrder->account_id) {
            $accounts = Account::where('id', $purchaseOrder->account_id)
                ->where('is_active', true)
                ->get();
        } else {
            // Otherwise get active accounts with matching account type and positive daily limit
            $accounts = Account::where('limit_amount_per_day', '>', 0)
                ->where('account_type', $purchaseOrder->account_type)
                ->where('is_active', true)
                ->get();
        }

        $eligibleAccount = null;
        $ready = [];


        $eligibleAccount = $accounts->first();


        $service = new \App\Services\RazerService($eligibleAccount);
        $account = $eligibleAccount;

        $ballance = $service->getAccountBallance();

        $ballanceResponse = [
            'gold' => $ballance['gold'] ?? 0, // Replace with actual API data
            'silver' => $ballance['silver'] ?? 0, // Replace with actual API data
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


        }


        $remainingQuantity = $purchaseOrder->quantity;

        // Process in chunks of 5
        $ordersCompleted = [];
        $buyProductsResults = $service->buyProduct($purchaseOrder, $remainingQuantity);


        if (isset($buyProductsResults['orders'])) {
            $ordersCompleted = array_merge($ordersCompleted, $buyProductsResults['orders']);
        }

        if (empty($ordersCompleted)) {
            $purchaseOrder->update([
                'order_status' => 'failed'
            ]);
        }

        foreach ($ordersCompleted as $orderTransactionId) {

            sleep(2);
            try {
                $orderDetails = $service->getTransactionDetails($orderTransactionId);

            } catch (\Exception $e) {
                sleep(3);
                //  Log::error('Error while getTransactionDetails: ' . $orderTransactionId);
                try {
                    $orderDetails = $service->getTransactionDetails($orderTransactionId);
                } catch (\Exception $e) {

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


                Log::error('Error while getTransactionDetails: ' . $orderTransactionId);
                continue;
            }


            $orderDetail = array_pop($orderDetails);


            /*array:6 [▼ // app\Jobs\ProcessBuyJob.php:120
              "Product" => "Yalla Ludo - USD 5 Diamonds"
              "Code" => "PPHN51L35GRR"
              "SN" => "M01911015173920860221514035972"
              "Amount" => "5.190000"
              "Timestamp" => "2026-02-11"
              "TransactionDate" => "2025-02-11 12:25:56.2533503"
            ]*/
            if (!isset($orderDetail['Code'])) {

                continue;
            }

            $item = [
                'code' => $orderDetail['Code'],
                'serial_number' => $orderDetail['SN'],
                'amount' => $orderDetail['Amount'],
                'buy_date' => date('Y-m-d H:i:s', strtotime($orderDetail['TransactionDate'])),
                'transaction_id' => $orderDetail['SN'] // Using SN as transaction ID since it's unique
            ];
            $ready[] = $item;

        }


        //dd($ready);

        /*




          foreach ($buyProductsResults as $buyProducts) {
                if (!isset($buyProducts['Code'])) {
                    continue;
                }
                $ready[] = [
                    'code' => $buyProducts['Code'],
                    'serial_number' => $buyProducts['SN'],
                    'amount' => $buyProducts['Amount'],
                    'buy_date' => date('Y-m-d H:i:s', strtotime($buyProducts['TransactionDate'])),
                    'transaction_id' => $buyProducts['SN'] // Using SN as transaction ID since it's unique
                ];
            }
*/


        $totalAmount = 0;

        Log::info('Ready: ' . json_encode($ready));
        Log::info('Purchase Order: ' . json_encode($purchaseOrder));


        /*$ready = array:1 [▼ // app\Jobs\ProcessBuyJob.php:248
  0 => array:5 [▼
    "code" => "RIYXDMMPDK3XWJCNY3"
    "serial_number" => "ROX010-310125--05548"
    "amount" => "10.080000"
    "buy_date" => "2025-02-11 20:02:29"
    "transaction_id" => "ROX010-310125--05548"
  ]
]*/
        // Create transaction and code for each item
        foreach ($ready as $item) {
            // Create transaction
            $transaction = Transaction::create([
                'account_id' => $account->id,
                'amount' => $item['amount'],
                'product_id' => $purchaseOrder->product_id,
                'transaction_date' => $item['buy_date'],
                'transaction_id' => $item['transaction_id'],
                'order_id' => $purchaseOrder->id
            ]);
            $transaction->save();
            // Create code
            $code = Code::create([
                'account_id' => $account->id,
                'code' => $item['code'],
                'serial_number' => $item['serial_number'],
                'product_id' => $purchaseOrder->product_id,
                'product_name' => $purchaseOrder->product_name,
                'product_edition' => $purchaseOrder->product_edition,
                'buy_date' => $item['buy_date'],
                'buy_value' => $item['amount'],
                'order_id' => $purchaseOrder->id
            ]);
            $code->save();
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
        } else {
            // Update purchase order status to failed
            $purchaseOrder->update([
                'order_status' => 'failed'
            ]);
        }


        // Send database notification
        $purchaseOrder->notify(new PurchaseOrderCompleted($purchaseOrder));


    }

}
