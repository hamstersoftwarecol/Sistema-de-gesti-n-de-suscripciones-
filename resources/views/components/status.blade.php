@props(['value', 'type' => 'subscription'])
@php
    $map = [
        'subscription' => ['trial' => 'blue', 'active' => 'green', 'past_due' => 'amber', 'paused' => 'purple', 'cancelled' => 'red', 'expired' => 'gray'],
        'invoice' => ['draft' => 'gray', 'sent' => 'blue', 'partial' => 'amber', 'paid' => 'green', 'overdue' => 'red', 'cancelled' => 'gray'],
        'payment' => ['completed' => 'green', 'pending' => 'amber', 'failed' => 'red', 'refunded' => 'purple'],
        'customer' => ['lead' => 'blue', 'active' => 'green', 'inactive' => 'gray'],
        'priority' => ['low' => 'gray', 'medium' => 'blue', 'high' => 'amber', 'urgent' => 'red'],
        'email' => ['sent' => 'green', 'failed' => 'red'],
        'bool' => ['1' => 'green', '0' => 'gray'],
    ];
    $labels = [
        'subscription' => fn () => \App\Models\Subscription::statusOptions(),
        'invoice' => fn () => \App\Models\Invoice::statusOptions(),
        'payment' => fn () => \App\Models\Payment::statusOptions(),
        'customer' => fn () => \App\Models\Customer::statusOptions(),
        'priority' => fn () => \App\Models\KanbanCard::priorityOptions(),
        'email' => fn () => ['sent' => __('Sent'), 'failed' => __('Failed')],
        'bool' => fn () => ['1' => __('Yes'), '0' => __('No')],
    ];
    $key = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    $color = $map[$type][$key] ?? 'gray';
    $label = ($labels[$type] ?? fn () => [])()[$key] ?? \Illuminate\Support\Str::headline($key);
    $classes = [
        'green' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'blue' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'red' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        'purple' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
        'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-700/60 dark:text-gray-300',
    ][$color];
@endphp
<span {{ $attributes->merge(['class' => "badge {$classes}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>{{ $label }}
</span>
