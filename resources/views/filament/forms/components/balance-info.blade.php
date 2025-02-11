<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{ balanceCheck: $wire.$entangle('{{ $getStatePath() }}') }">
        <div x-show="!balanceCheck" class="text-gray-500">
            Select a product, quantity, and account to see balance information.
        </div>
        <template x-if="balanceCheck">
            <div
                class="p-4 rounded-lg"
                :class="JSON.parse(balanceCheck).has_sufficient_balance ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
            >
                <div class="font-medium mb-2">Order Summary:</div>
                <div class="space-y-1">
                    <template x-if="!JSON.parse(balanceCheck).has_sufficient_balance">
                          <div>
                            <div>• Account Balance: $<span x-text="new Intl.NumberFormat().format(JSON.parse(balanceCheck).account_balance)"></span></div>
                            <div>• Quantity: <span x-text="JSON.parse(balanceCheck).quantity"></span></div>
                            <div>• Buy Value: $<span x-text="new Intl.NumberFormat().format(JSON.parse(balanceCheck).buy_value)"></span></div>
                          </div>
                    </template>

                    <div clas="total-cost">• Total Cost: $<span x-text="new Intl.NumberFormat().format(JSON.parse(balanceCheck).total_cost)"></span></div>
                </div>

                <template x-if="!JSON.parse(balanceCheck).has_sufficient_balance">
                    <div class="mt-4 font-medium text-red-700">
                        ⚠️ WARNING: Insufficient balance!<br>
                        Additional funds needed: $<span x-text="new Intl.NumberFormat().format(JSON.parse(balanceCheck).total_cost - JSON.parse(balanceCheck).account_balance)"></span>
                    </div>
                </template>
                <template x-if="JSON.parse(balanceCheck).has_sufficient_balance">
                    <div class="mt-4 font-medium text-green-700">
                        ✅ Sufficient balance available
                    </div>
                </template>
            </div>
        </template>
    </div>
</x-dynamic-component>
