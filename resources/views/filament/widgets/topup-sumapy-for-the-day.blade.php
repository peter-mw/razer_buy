
@php
use Illuminate\Support\Facades\DB;

$topups = \App\Models\AccountTopup::query()
    ->join('accounts', 'account_topups.account_id', '=', 'accounts.id')
    ->select([
        'accounts.vendor',
        DB::raw('DATE(account_topups.date) as date'),
        DB::raw('COUNT(*) as total_topups'),
        DB::raw('SUM(account_topups.topup_amount) as total_amount'),
        DB::raw('COUNT(DISTINCT account_topups.account_id) as unique_accounts')
    ])
    ->where('account_topups.date', $getRecord()->date)
    ->groupBy('accounts.vendor', DB::raw('DATE(account_topups.date)'))
    ->get();
@endphp

<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700" style="min-width: 350px;">
        @foreach($topups as $topup)
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $topup->vendor }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($topup->date)->format('Y-m-d') }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">${{ number_format($topup->total_amount, 2) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $topup->total_topups }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $topup->unique_accounts }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
