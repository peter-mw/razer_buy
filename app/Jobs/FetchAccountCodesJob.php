<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Code;
use App\Models\CodesWithMissingProduct;
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

    /*  public function middleware()
      {
          return [(new WithoutOverlapping('FetchAccountCodesJob1' . $this->accountId))->releaseAfter(60)];
      }*/

    public function handle(): void
    {

        $log = SystemLog::create([
            'source' => 'FetchAccountCodesJob::handle',
            'account_id' => $this->accountId,
            'status' => 'processing'

        ]);
        $log->save();


        $account = Account::findOrFail($this->accountId);
        $service = new RazerService($account);


        // Fetch all codes for the account

         $codes = $service->fetchAllCodes();

        //@ todo remove this
      //  $codes = $service->fetchAllCodesCached();


        $foundCodes = [];
        $processedCodes = [];
        if (!empty($codes)) {
            // Track if we found any new codes
            $hasNewCodes = false;
            $firstNewCode = null;


            // First pass - check for new codes
            foreach ($codes as $codeData) {
                if (!isset($codeData['Code']) || !isset($codeData['SN'])) {
                    continue;
                }


                $code = $codeData['Code'];
                $serialNumber = $codeData['SN'];

                // Check if code already exists
                $existingCode = Code::where('code', $code)
                    ->where('serial_number', $serialNumber)
                    ->first();

                if (!$existingCode) {
                    $hasNewCodes = true;
                    if (!$firstNewCode) {
                        $firstNewCode = $codeData;
                    }
                    $processedCodes[] = $codeData;
                } else {
                    $foundCodes[] = $existingCode;
                }
            }


            if ($foundCodes) {

                foreach ($foundCodes as $existingCode) {

                    $codeData = $existingCode->toArray();

                    // Check if transaction exists for this code
                    /*   $transaction = Transaction::where('account_id', $existingCode->account_id)
                           ->where('product_id', $existingCode->product_id)
                           ->where('order_id', $existingCode->order_id)
                           ->where('transaction_ref', $existingCode->product_name)
                           ->where('amount', $existingCode->buy_value)
                           ->first();*/


                    $transaction = Transaction::where('transaction_id', $codeData['transaction_id'])
                        ->first();

                    if ($transaction) {
                        $transaction->update([
                            'order_id' => $existingCode->order_id,
                            'account_id' => $existingCode->account_id,
                            'product_id' => $existingCode->product_id,
                            'transaction_date' => $existingCode->buy_date,
                            'amount' => $existingCode->buy_value,
                        ]);


                    }

                    //'transaction_id' => $codeData['transaction_id']

                    if (!$transaction) {
                        // Find matching code data from fetched codes


                        /*$codeData = rray:14 [▼ // app/Jobs/FetchAccountCodesJob.php:97
"id" => 1875
"account_id" => 11
"order_id" => 137
"product_id" => 14486
"code" => "MGG1NJ65K4JN"
"serial_number" => "M001111051739107801907314011609"
"product_name" => "Yalla Ludo - USD 2 Diamonds"
"product_edition" => null
"buy_date" => "2025-02-10T07:48:06.000000Z"
"buy_value" => "2.07"
"created_at" => "2025-02-15T12:37:01.000000Z"
"updated_at" => "2025-02-15T12:37:01.000000Z"
"transaction_ref" => null
"transaction_id" => "122GOWX6B249861538E13"
]*/

                        if ($codeData) {
                            // Create transaction for existing code
                            $transactionData = [
                                'account_id' => $existingCode->account_id,
                                'amount' => $codeData['buy_value'],
                                'product_id' => $existingCode->product_id,
                                'transaction_date' => $codeData['buy_date'],
                                'transaction_id' => $codeData['transaction_id'],
                                'order_id' => $existingCode->order_id,
                                'transaction_ref' => $codeData['product_name']
                            ];

                            try {
                                $transaction = Transaction::insert($transactionData);

                            } catch (\Exception $e) {
                                Log::error('Failed to create transaction for existing code: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }


            // Only process codes if we found new ones
            if ($hasNewCodes) {
                $log->update([
                    'status' => 'has_new_codes'
                ]);

                // Group codes by product
                $codesByProduct = collect($processedCodes)->groupBy('Product');

                // Process each product group
                foreach ($codesByProduct as $productName => $productCodes) {
                    // Find or create product
                    $product = Product::where('product_name', $productName)
                        ->first();

                    if (!$product) {

                        $productsAll = Product::whereNotNull('product_names')->get();

                        if ($productsAll) {
                            foreach ($productsAll as $productAll) {
                                $productNames = $productAll->product_names;

                                if ($productNames) {
                                    foreach ($productNames as $productNames) {
                                        if ($productNames['name'] == $productName) {
                                            $product = $productAll;
                                        }
                                    }
                                }
                            }
                        }
                    }

               //    dump($product, $productName, $productCodes);

                    if (!$product) {
                        // Create entry in codes with missing products table
                        foreach ($productCodes as $codeData) {
                            $checkIfCodesWithMissingProductExist = CodesWithMissingProduct::where('code', $codeData['Code'])
                                ->where('serial_number', $codeData['SN'])
                                ->first();
                            if ($checkIfCodesWithMissingProductExist) {
                                continue;
                            }


                            $code = $codeData['Code'];
                            $serialNumber = $codeData['SN'];
                            $amount = floatval($codeData['Amount']);
                            $amount = number_format($amount, 2, '.', '');
                            $transaction_id = $codeData['ID'] ?? '';
                            $buyDate = date('Y-m-d H:i:s', strtotime($codeData['TransactionDate']));

                            // Get account type from the account
                            $accountType = $account->account_type;

                            // Try to find a product with matching name
                            $matchingProduct = Product::where('product_name', $productName)
                                ->orWhereJsonContains('product_slugs', ['account_type' => $accountType])
                                ->orWhereJsonContains('product_names', ['account_type' => $accountType])
                                ->first();

                            // Get the appropriate name or slug
                            $productSlug = 'unknown';
                            if ($matchingProduct) {
                                // First try to get region-specific name
                                if (!empty($matchingProduct->product_names)) {
                                    $names = collect($matchingProduct->product_names);
                                    $regionName = $names->firstWhere('account_type', $accountType);
                                    if ($regionName && isset($regionName['name'])) {
                                        $productSlug = $regionName['name'];
                                    }
                                } // Fallback to region-specific slug
                                elseif (!empty($matchingProduct->product_slugs)) {
                                    $slugs = collect($matchingProduct->product_slugs);
                                    $regionSlug = $slugs->firstWhere('account_type', $accountType);
                                    if ($regionSlug && isset($regionSlug['slug'])) {
                                        $productSlug = $regionSlug['slug'];
                                    }
                                } else {
                                    $productSlug = $matchingProduct->product_slug ?? 'unknown';
                                }
                            }

                            CodesWithMissingProduct::create([
                                'account_id' => $account->id,
                                'code' => $code,
                                'serial_number' => $serialNumber,
                                'product_id' => null,
                                'product_name' => $productName,
                                'product_slug' => $productSlug,
                                'account_type' => $accountType,
                                'product_edition' => 'unknown',
                                'product_buy_value' => $amount,
                                'product_face_value' => $amount,
                                'buy_date' => $buyDate,
                                'buy_value' => $amount,
                                'transaction_ref' => null,
                                'transaction_id' => $transaction_id
                            ]);
                        }


                    } else {


                        // Get region-specific product name if available
                        $productName = $product->product_name;
                        $accountType = $account->account_type;

                        if ($accountType) {
                            // First try to get region-specific name
                            if (!empty($product->product_names)) {
                                $names = collect($product->product_names);
                                $regionName = $names->firstWhere('account_type', $accountType);
                                if ($regionName && isset($regionName['name'])) {
                                    $productName = $regionName['name'];
                                }
                            } // Fallback to region-specific slug
                            elseif (!empty($product->product_slugs)) {
                                $slugs = collect($product->product_slugs);
                                $regionSlug = $slugs->firstWhere('account_type', $accountType);
                                if ($regionSlug && isset($regionSlug['slug'])) {
                                    $productName = $regionSlug['slug'];
                                }
                            }
                        }

                        // Create order for this product group
                        $newOrder = PurchaseOrders::create([
                            'product_id' => $product->id,
                            'product_name' => $productName,
                            'account_type' => $accountType,
                            'buy_value' => $product->product_buy_value,
                            'product_face_value' => $product->product_face_value,
                            'quantity' => count($productCodes),
                            'order_status' => 'processing',
                            'account_id' => $account->id
                        ]);

                        $productCodes = $productCodes->toArray();
                        $quantityProcessed = count($productCodes);
                        // Process codes for this product

                        $log->update([
                            'status' => 'processing_codes'
                        ]);

                        Log::info('Processing codes for product: ' . $productName . ' - ' . $product->id);

                        foreach ($productCodes as $codeItem => $codeDataItem) {

                            $quantityProcessed--;
                            $this->processCode($account, $codeDataItem, $newOrder, $product);
                        }
                        $log->update([
                            'status' => 'processing_codes_completed'
                        ]);
                        $newOrder->update([
                            'order_status' => 'completed',
                            'quantity' => $quantityProcessed,
                        ]);
                    }
                 }


            }


        }
        $log->update([
            'status' => 'success',
            'params' => [
                'account_id' => $account->id,
                'codes' => $codes,
            ],
        ]);


    }

    protected function processCode(Account $account, array $codeData, PurchaseOrders $order, Product $product): void
    {
        // Extract data from code response
        $code = $codeData['Code'];
        $serialNumber = $codeData['SN'];
        $productName = $codeData['Product'];
        $amount = floatval($codeData['Amount']);
        $amount = number_format($amount, 2, '.', '');
        $transaction_id = $codeData['ID'] ?? '';
        $buyDate = date('Y-m-d H:i:s', strtotime($codeData['TransactionDate']));


        //check if exist
        $codeExist = Code::where('code', $code)
            ->where('serial_number', $serialNumber)
            ->first();

        //delete from CodesWithMissingProduct
        CodesWithMissingProduct::where('code', $code)
            ->where('serial_number', $serialNumber)
            ->delete();



        if ($codeExist) {
            if ($codeExist->account_id != $order->account_id) {
                $codeExist->update([
                    'account_id' => $order->account_id,
                    'order_id' => $order->id
                ]);
            }

        }

        if (!$codeExist) {


            $data = [
                'account_id' => $account->id,
                'code' => $code,
                'transaction_id' => $transaction_id,
                'serial_number' => $serialNumber,
                'product_id' => $product->id,
                'product_name' => $productName,
                'buy_date' => $buyDate,
                'buy_value' => $amount,
                'order_id' => $order->id
            ];

            try {
                $code = Code::create($data);
                $code->save();
            } catch (\Exception $e) {
                Log::error('Failed to create code: ' . $e->getMessage());
            }


            $transaction = Transaction::where('transaction_id', $transaction_id)->first();


            if ($transaction) {

                $transaction->update([
                    'order_id' => $order->id,
                    'account_id' => $account->id,
                ]);

            } else {

                $transactionData = [
                    'account_id' => $account->id,
                    'amount' => $amount,
                    'product_id' => $product->id,
                    'transaction_date' => $buyDate,
                    'transaction_id' => $transaction_id,
                    'order_id' => $order->id,
                    'transaction_ref' => $code->id
                ];


                try {
                    $transaction = Transaction::insert($transactionData);
                } catch (\Exception $e) {

                    Log::error('Failed to create transaction for existing code: ' . $e->getMessage());
                }


            }


        }
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
