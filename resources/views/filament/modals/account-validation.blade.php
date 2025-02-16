<div class="space-y-4">
    <div class="flex items-center space-x-2">
        <span class="font-medium">Status:</span>
        <span @class([
            'px-2 py-1 rounded-full text-sm',
            'bg-success-500/10 text-success-700' => $status,
            'bg-danger-500/10 text-danger-700' => !$status,
        ])>
            {{ $status ? 'Valid' : 'Invalid' }}
        </span>
    </div>

    <div class="flex items-center space-x-2">
        <span class="font-medium">Account:</span>
        <span>{{ $account->name }} ({{ $account->email }})</span>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="font-medium">Gold Balance:</span>
            <span class="ml-2">{{ number_format($goldBalance, 2) }}</span>
        </div>
        <div>
            <span class="font-medium">Silver Balance:</span>
            <span class="ml-2">{{ number_format($silverBalance, 2) }}</span>
        </div>
    </div>

    <div class="mt-4 p-4 rounded-lg @if($status) bg-success-500/10 @else bg-danger-500/10 @endif">
        <p class="text-sm">{{ $message }}</p>
    </div>
</div>
