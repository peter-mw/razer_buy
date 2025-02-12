<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{ orderDetails: null }" x-init="$watch('$wire.data.order_details', value => { orderDetails = value ? JSON.parse(value) : null })">
        <div x-show="!orderDetails" class="text-gray-500">
            Configure your order to see the summary.
        </div>
        <template x-if="orderDetails">
            <div class="p-4 bg-gray-50 rounded-lg text-gray-700">
                <!-- Product Information -->
                <div class="space-y-1 mb-4">
                    <div class="font-medium text-sm text-gray-600">Product Information:</div>
                    <div>• Product: <span x-text="orderDetails.product.name"></span></div>
                    <div>• Buy Value: $<span x-text="new Intl.NumberFormat().format(orderDetails.product.buy_value)"></span></div>
                    <div>• Face Value: $<span x-text="new Intl.NumberFormat().format(orderDetails.product.face_value)"></span></div>
                </div>

                <!-- Accounts Information -->
                <div class="space-y-1 mb-4">
                    <div class="font-medium text-sm text-gray-600">Selected Accounts:</div>
                    <template x-for="account in orderDetails.accounts" :key="account.id">
                        <div class="ml-2">
                            • <span x-text="account.name"></span>: <span x-text="account.quantity"></span> units
                            (<span class="text-gray-500">Balance: $<span x-text="new Intl.NumberFormat().format(account.balance)"></span></span>)
                        </div>
                    </template>
                </div>

                <!-- Total Summary -->
                <div class="space-y-1 pt-3 border-t border-gray-200">
                    <div class="font-medium text-sm text-gray-600">Order Summary:</div>
                    <div>• Total Quantity: <span x-text="orderDetails.total.quantity"></span> units</div>
                    <div>• Total Cost: $<span x-text="new Intl.NumberFormat().format(orderDetails.total.cost)"></span></div>
                </div>

                <!-- Warnings -->
                <template x-if="orderDetails.warnings.length > 0">
                    <div class="mt-4 space-y-2">
                        <div class="font-medium text-sm text-red-600">Warnings:</div>
                        <template x-for="warning in orderDetails.warnings" :key="warning">
                            <div class="text-red-600">⚠️ <span x-text="warning"></span></div>
                        </template>
                    </div>
                </template>

            </div>
        </template>
    </div>
</x-dynamic-component>
