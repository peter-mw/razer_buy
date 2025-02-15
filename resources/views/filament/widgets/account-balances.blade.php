<div class="p-4 bg-white rounded-lg shadow">
    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Region</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gold</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Silver</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            {{-- Global Rows --}}
            <tr>
                <td class="px-6 py-4 whitespace-nowrap align-middle" rowspan="2">Global</td>
                <td class="px-6 py-4 whitespace-nowrap">Active</td>
                <td class="px-6 py-4 whitespace-nowrap">${{ number_format($balances['global']['active']['gold'], 2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">${{ number_format($balances['global']['active']['silver'], 2) }}</td>
            </tr>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">Not Active</td>
                <td class="px-6 py-4 whitespace-nowrap">${{ number_format($balances['global']['inactive']['gold'], 2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">${{ number_format($balances['global']['inactive']['silver'], 2) }}</td>
            </tr>

            {{-- USA Rows --}}
            <tr>
                <td class="px-6 py-4 whitespace-nowrap align-middle" rowspan="2">USA</td>
                <td class="px-6 py-4 whitespace-nowrap">Active</td>
                <td class="px-6 py-4 whitespace-nowrap">${{ number_format($balances['usa']['active']['gold'], 2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">${{ number_format($balances['usa']['active']['silver'], 2) }}</td>
            </tr>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">Not Active</td>
                <td class="px-6 py-4 whitespace-nowrap">${{ number_format($balances['usa']['inactive']['gold'], 2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">${{ number_format($balances['usa']['inactive']['silver'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>
