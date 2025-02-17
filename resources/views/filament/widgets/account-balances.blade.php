<div class="p-4 rounded-lg shadow bg-white dark:bg-gray-800">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead>

            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Region</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gold</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Silver</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Topup</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
            @foreach ($balances as $type => $typeBalances)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap align-middle text-gray-900 dark:text-gray-100" rowspan="2">{{ $type }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">Active</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">${{ number_format($typeBalances['active']['gold'], 2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">${{ number_format($typeBalances['active']['silver'], 2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                    @foreach($typeBalances['active']['topup'] as $regionId => $amount)
                        <div>${{ number_format($amount, 2) }}</div>
                    @endforeach
                </td>
            </tr>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">Not Active</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">${{ number_format($typeBalances['inactive']['gold'], 2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">${{ number_format($typeBalances['inactive']['silver'], 2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                    @foreach($typeBalances['inactive']['topup'] as $regionId => $amount)
                        <div>${{ number_format($amount, 2) }}</div>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
