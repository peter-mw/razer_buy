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

        protected int $productId,
        protected int $quantity
    )
    {
    }

    public function handle(): void
    {
        $purchaseOrder = PurchaseOrders::findOrFail($this->productId);



        $purchaseOrder->update(['order_status' => 'processing']);
        
        // Send processing status notification to all users
        foreach (User::all() as $user) {
            Notification::make()
                ->title('Purchase Order Started')
                ->body("Purchase order #{$purchaseOrder->id} for {$purchaseOrder->product_name} is now processing")
                ->info()
                    ->duration(10)
                    ->sendToDatabase($user);
        }

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
        $actualQuantity = 0;
        $ready = [];
        foreach ($accounts as $acc) {
            // Calculate today's spent amount
            $todaySpent = Transaction::where('account_id', $acc->id)
                ->whereDate('transaction_date', now())
                ->sum('amount');
            $remainingDailyLimit = $acc->limit_amount_per_day - $todaySpent;

            // Calculate remaining daily limit
            if ($acc->limit_amount_per_day > 0 and $todaySpent > 0) {
                if ($remainingDailyLimit <= 0) {
                    continue;
                }
            }


            if ($acc->ballance_gold == 0) {
                continue;
            }


            // Calculate maximum quantity possible based on balance and daily limit
            // $maxQuantityByBalance = floor($acc->ballance_gold / $product->buy_value);
            //  $maxQuantityByDailyLimit = floor($remainingDailyLimit / $product->buy_value);

            // Calculate the actual quantity considering all constraints
            $possibleQuantity = $purchaseOrder->quantity;

            if ($possibleQuantity > 0) {
                $eligibleAccount = $acc;
                $actualQuantity = $possibleQuantity;
                break;
            }
        }

        if (!$eligibleAccount || $actualQuantity <= 0) {
            // Update status to failed if no eligible account found
            $purchaseOrder->update(['order_status' => 'failed']);
            
            // Send failure notification to all users
            foreach (User::all() as $user) {
                Notification::make()
                    ->title('Purchase Order Failed')
                    ->body("Purchase order #{$purchaseOrder->id} failed: No eligible account found with sufficient balance and daily limit, or product is out of stock")
                    ->danger()
                    ->duration(10)
                    ->sendToDatabase($user);
            }
                
            throw new \Exception("No eligible account found with sufficient balance and daily limit, or product is out of stock");
        }


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
            
            // Send balance update notification to all users
            foreach (User::all() as $user) {
                Notification::make()
                    ->title('Account Balance Updated')
                    ->body("Account {$account->name} balance updated: Gold {$ballanceResponse['gold']}, Silver {$ballanceResponse['silver']}")
                    ->info()
                    ->duration(10)
                    ->sendToDatabase($user);
            }
        }


        if ($ballanceResponse['gold'] < $purchaseOrder->buy_value) {
            $purchaseOrder->update(['order_status' => 'failed']);
            
            // Send failure notification to all users
            foreach (User::all() as $user) {
                Notification::make()
                    ->title('Purchase Order Failed')
                    ->body("Purchase order #{$purchaseOrder->id} failed: Not enough gold to buy the product")
                    ->danger()
                    ->duration(10)
                    ->sendToDatabase($user);
            }
                
            throw new \Exception("Not enough gold to buy the product");
        }


        $remainingQuantity = $actualQuantity;

        // Process in chunks of 5
        $ordersCompleted = [];
        while ($remainingQuantity > 0) {
            $chunkSize = min(200, $remainingQuantity);
            $purchaseOrder->update([
                'order_status' => 'buying',
            ]);
            
            // Send buying status notification to all users
            foreach (User::all() as $user) {
                Notification::make()
                    ->title('Purchase Order Update')
                    ->body("Purchase order #{$purchaseOrder->id} is now buying products")
                    ->info()
                    ->duration(10)
                    ->sendToDatabase($user);
            }
            $buyProductsResults = $service->buyProduct($purchaseOrder, $chunkSize);


            if (empty($buyProductsResults)) {
                // Send warning notification about failed purchase attempt to all users
                foreach (User::all() as $user) {
                    Notification::make()
                        ->title('Purchase Attempt Failed')
                        ->body("Failed to buy products for purchase order #{$purchaseOrder->id} (chunk size: {$chunkSize})")
                        ->warning()
                    ->duration(10)
                    ->sendToDatabase($user);
                }
                continue;
            }
            if (isset($buyProductsResults['orders'])) {
                $ordersCompleted = array_merge($ordersCompleted, $buyProductsResults['orders']);
            }
            /*  sleep(3);
            $format = $this->getTransactionDetails($format['order_id']);
            dd($format);*/


            $remainingQuantity -= $chunkSize;
        }

        $purchaseOrder->update([
            'order_status' => 'fetching'
        ]);
        
        // Send fetching status notification to all users
        foreach (User::all() as $user) {
            Notification::make()
                ->title('Purchase Order Update')
                ->body("Purchase order #{$purchaseOrder->id} is now fetching transaction details")
                ->info()
                    ->duration(10)
                    ->sendToDatabase($user);
        }
        foreach ($ordersCompleted as $orderTransactionId) {

            sleep(2);
            try {
                $orderDetails = $service->getTransactionDetails($orderTransactionId);

            } catch (\Exception $e) {
                sleep(3);
                Log::error('Error while getTransactionDetails: ' . $orderTransactionId);
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
                    'error_message' => 'Failed to retrieve transaction details after attempts: ' . $e->getMessage(),
                    'transaction_date' => now(),
                ]);

                // Send pending transaction notification to all users
                foreach (User::all() as $user) {
                    Notification::make()
                        ->title('Transaction Pending')
                        ->body("Transaction {$orderTransactionId} for purchase order #{$purchaseOrder->id} has been saved to pending transactions")
                        ->warning()
                    ->duration(10)
                    ->sendToDatabase($user);
                }


                continue;
            }

            if (empty($orderDetails)) {

                $purchaseOrder->update([
                    'order_status' => 'failed'
                ]);
                
                // Send failure notification to all users
                foreach (User::all() as $user) {
                    Notification::make()
                        ->title('Purchase Order Failed')
                        ->body("Purchase order #{$purchaseOrder->id} failed: Error retrieving transaction details")
                        ->danger()
                    ->duration(10)
                    ->sendToDatabase($user);
                }
                    
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
                // Send warning notification about missing code to all users
                foreach (User::all() as $user) {
                    Notification::make()
                        ->title('Code Missing')
                        ->body("Transaction {$orderTransactionId} for purchase order #{$purchaseOrder->id} did not contain a code")
                        ->warning()
                    ->duration(10)
                    ->sendToDatabase($user);
                }
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

        // Update account balance with total amount
        $account->update([
            'ballance_gold' => $account->ballance_gold - $totalAmount
        ]);

        // Send final balance update notification to all users
        foreach (User::all() as $user) {
            Notification::make()
                ->title('Final Balance Update')
                ->body("Account {$account->name} balance reduced by {$totalAmount} gold after completing purchase order #{$purchaseOrder->id}")
                ->info()
                    ->duration(10)
                    ->sendToDatabase($user);
        }

        // Update product quantity and status
        $purchaseOrder->update([
            'quantity' => $purchaseOrder->quantity - count($ready),
            'order_status' => 'completed'
        ]);

        // Send database notification
        $purchaseOrder->notify(new PurchaseOrderCompleted($purchaseOrder));
        
        // Send completion notification to all users
        foreach (User::all() as $user) {
            Notification::make()
                ->title('Purchase Order Completed')
                ->body("Purchase order #{$purchaseOrder->id} for {$purchaseOrder->product_name} has been completed")
                ->success()
                    ->duration(10)
                    ->sendToDatabase($user);
        }
    }

}
