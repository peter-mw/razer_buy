<?php

namespace App\Jobs;

use App\Models\Code;
use App\Models\PendingTransaction;
use App\Models\Product;
use App\Models\SystemLog;
use App\Models\Transaction;
use App\Services\RazerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RescueTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $transactionId
    )
    {
    }
    public function middleware()
    {
        return [(new WithoutOverlapping('RescueTransactionJob'.$this->transactionId))->dontRelease()];
    }
    public function handle(): void
    {
        // Find the pending transaction with its relationships
        $pendingTransaction = PendingTransaction::with(['product', 'account'])
            ->where('transaction_id', $this->transactionId)
            ->firstOrFail();

        // Create initial system log
        SystemLog::create([
            'source' => 'RescueTransactionJob',
            'account_id' => $pendingTransaction->account_id,
            'status' => 'processing',
            'command' => 'rescue_transaction',
            'params' => [
                'transaction_id' => $this->transactionId,
                'pending_transaction_id' => $pendingTransaction->id,
            ],
        ]);

        if (!$pendingTransaction->product_id) {
            SystemLog::create([
                'source' => 'RescueTransactionJob',
                'account_id' => $pendingTransaction->account_id,
                'status' => 'error',
                'command' => 'rescue_transaction',
                'params' => [
                    'transaction_id' => $this->transactionId,
                    'error' => 'Associated product not found',
                ],
            ]);
            $pendingTransaction->update([
                'status' => 'failed',
                'error_message' => 'Associated product not found'
            ]);
            return;
        }

        // Get the account and create RazerService instance
        $account = $pendingTransaction->account;
        $service = new RazerService($account);

        try {
            // Get transaction details
            $orderDetails = $service->getTransactionDetails($this->transactionId);

            if (empty($orderDetails)) {
                SystemLog::create([
                    'source' => 'RescueTransactionJob',
                    'account_id' => $account->id,
                    'status' => 'error',
                    'command' => 'rescue_transaction',
                    'params' => [
                        'transaction_id' => $this->transactionId,
                        'error' => 'Transaction details not found',
                    ],
                ]);
                $pendingTransaction->update([
                    'status' => 'failed',
                    'error_message' => 'Transaction details not found'
                ]);
                return;
            }

            $orderDetail = array_pop($orderDetails);

            // Check if we have the required data
            if (!isset($orderDetail['Code'])) {
                SystemLog::create([
                    'source' => 'RescueTransactionJob',
                    'account_id' => $account->id,
                    'status' => 'error',
                    'command' => 'rescue_transaction',
                    'params' => [
                        'transaction_id' => $this->transactionId,
                        'error' => 'Transaction code not found in response',
                    ],
                ]);
                $pendingTransaction->update([
                    'status' => 'failed',
                    'error_message' => 'Transaction code not found in response'
                ]);
                return;
            }
            $product = Product::find($pendingTransaction->product_id);


            // Create or update transaction record
            Transaction::updateOrCreate(
                [
                    'transaction_id' => $orderDetail['SN'],
                    'account_id' => $account->id,
                ],
                [
                    'amount' => $orderDetail['Amount'],
                    'product_id' => $pendingTransaction->product_id,
                    'transaction_date' => date('Y-m-d H:i:s', strtotime($orderDetail['TransactionDate'])),
                    'order_id' => $pendingTransaction->product_id
                ]
            );
            // Create or update code record
            Code::updateOrCreate(
                [
                    'serial_number' => $orderDetail['SN'],
                    'account_id' => $account->id,
                ],
                [
                    'code' => $orderDetail['Code'],
                    'product_id' => $pendingTransaction->product_id,
                    'product_name' => $product->product_name ?? null,
                    'product_edition' => $product->product_edition ?? null,
                    'buy_date' => date('Y-m-d H:i:s', strtotime($orderDetail['TransactionDate'])),
                    'buy_value' => $orderDetail['Amount'],
                    'order_id' => $pendingTransaction->product_id
                ]
            );

            // Update account balance
            $account->update([
                'ballance_gold' => $account->ballance_gold - floatval($orderDetail['Amount'])
            ]);

            // Update pending transaction status
            $pendingTransaction->update([
                'status' => 'completed',
                'amount' => $orderDetail['Amount'],
                'transaction_date' => date('Y-m-d H:i:s', strtotime($orderDetail['TransactionDate']))
            ]);

            SystemLog::create([
                'source' => 'RescueTransactionJob',
                'account_id' => $account->id,
                'status' => 'success',
                'command' => 'rescue_transaction',
                'params' => [
                    'transaction_id' => $this->transactionId,
                    'amount' => $orderDetail['Amount'],
                    'code' => $orderDetail['Code'],
                    'serial_number' => $orderDetail['SN'],
                ],
            ]);


        } catch (\Exception $e) {

            SystemLog::create([
                'source' => 'RescueTransactionJob',
                'account_id' => $account->id,
                'status' => 'error',
                'command' => 'rescue_transaction',
                'params' => [
                    'transaction_id' => $this->transactionId,
                    'error' => 'Rescue attempt failed: ' . $e->getMessage(),
                ],
            ]);
            $pendingTransaction->update([
                'status' => 'failed',
                'error_message' => 'Rescue attempt failed: ' . $e->getMessage()
            ]);
        }
    }
}
