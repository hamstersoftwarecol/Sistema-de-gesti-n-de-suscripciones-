@php
    $company = [
        'name' => setting('company_name', config('app.name')),
        'address' => setting('company_address'),
        'email' => setting('company_email'),
        'phone' => setting('company_phone'),
        'tax_id' => setting('company_tax_id'),
        'website' => setting('company_website'),
    ];
    $statusLabels = \App\Models\Invoice::statusOptions();
    $printable = $printable ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Invoice') }} {{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 0; }
        .page { padding: 36px 40px; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .brand { font-size: 20px; font-weight: bold; color: #4338ca; }
        .muted { color: #6b7280; }
        .title { font-size: 26px; font-weight: bold; text-transform: uppercase; color: #4338ca; letter-spacing: 1px; text-align: right; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; background: #eef2ff; color: #4338ca; }
        .badge.paid { background: #d1fae5; color: #047857; }
        .badge.overdue { background: #fee2e2; color: #b91c1c; }
        .section { margin-top: 28px; }
        .label { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
        .items th { background: #4338ca; color: #fff; text-align: left; padding: 8px; font-size: 11px; }
        .items td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .items tr:nth-child(even) td { background: #f9fafb; }
        .right { text-align: right; }
        .totals { width: 280px; margin-left: auto; margin-top: 16px; }
        .totals td { padding: 4px 0; }
        .totals .grand td { border-top: 2px solid #4338ca; font-size: 15px; font-weight: bold; padding-top: 8px; }
        .footer { margin-top: 36px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #6b7280; text-align: center; }
        .print-bar { padding: 12px; background: #f3f4f6; text-align: center; }
        .print-bar button { background: #4338ca; color: #fff; border: 0; padding: 8px 18px; border-radius: 6px; font-size: 14px; cursor: pointer; }
        @media print { .print-bar { display: none; } .page { padding: 0; } }
    </style>
</head>
<body>
    @if ($printable)
        <div class="print-bar"><button onclick="window.print()">{{ __('Print') }}</button></div>
    @endif
    <div class="page">
        <table class="header">
            <tr>
                <td style="width: 55%">
                    <div class="brand">{{ $company['name'] }}</div>
                    @foreach (['address', 'email', 'phone', 'website'] as $field)
                        @if ($company[$field]) <div class="muted">{{ $company[$field] }}</div> @endif
                    @endforeach
                    @if ($company['tax_id']) <div class="muted">{{ __('Tax ID') }}: {{ $company['tax_id'] }}</div> @endif
                </td>
                <td>
                    <div class="title">{{ __('Invoice') }}</div>
                    <div class="right" style="font-size: 14px; font-weight: bold;">{{ $invoice->number }}</div>
                    <div class="right" style="margin-top: 6px;"><span class="badge {{ $invoice->status }}">{{ $statusLabels[$invoice->status] ?? $invoice->status }}</span></div>
                </td>
            </tr>
        </table>

        <table class="section">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <div class="label">{{ __('Bill to') }}</div>
                    <div style="font-weight: bold; font-size: 13px;">{{ $invoice->customer?->displayName() }}</div>
                    @if ($invoice->customer?->tax_id) <div class="muted">{{ __('Tax ID') }}: {{ $invoice->customer->tax_id }}</div> @endif
                    <div class="muted">{{ collect([$invoice->customer?->address, $invoice->customer?->city, $invoice->customer?->state, $invoice->customer?->country])->filter()->implode(', ') }}</div>
                    <div class="muted">{{ $invoice->customer?->email }}</div>
                </td>
                <td style="vertical-align: top;" class="right">
                    <div><span class="muted">{{ __('Issue date') }}:</span> <strong>{{ fdate($invoice->issue_date) }}</strong></div>
                    <div><span class="muted">{{ __('Due date') }}:</span> <strong>{{ fdate($invoice->due_date) }}</strong></div>
                    @if ($invoice->subscription)
                        <div><span class="muted">{{ __('Subscription') }}:</span> <strong>{{ $invoice->subscription->reference }}</strong></div>
                    @endif
                    <div><span class="muted">{{ __('Currency') }}:</span> <strong>{{ $invoice->currency?->code }}</strong></div>
                </td>
            </tr>
        </table>

        <table class="items section">
            <thead>
                <tr>
                    <th style="width: 50%">{{ __('Description') }}</th>
                    <th class="right">{{ __('Qty') }}</th>
                    <th class="right">{{ __('Price') }}</th>
                    <th class="right">{{ __('Disc.') }}</th>
                    <th class="right">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                        <td class="right">{{ $invoice->format($item->unit_price) }}</td>
                        <td class="right">{{ (float) $item->discount ? (float) $item->discount.'%' : '—' }}</td>
                        <td class="right">{{ $invoice->format($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr><td class="muted">{{ __('Subtotal') }}</td><td class="right">{{ $invoice->format($invoice->subtotal) }}</td></tr>
            @if ((float) $invoice->discount_total)
                <tr><td class="muted">{{ __('Discounts') }}</td><td class="right">-{{ $invoice->format($invoice->discount_total) }}</td></tr>
            @endif
            <tr><td class="muted">{{ __('Tax') }} ({{ (float) $invoice->tax_rate }}%)</td><td class="right">{{ $invoice->format($invoice->tax_total) }}</td></tr>
            <tr class="grand"><td>{{ __('Total') }}</td><td class="right">{{ $invoice->format($invoice->total) }}</td></tr>
            <tr><td class="muted">{{ __('Paid') }}</td><td class="right">{{ $invoice->format($invoice->amount_paid) }}</td></tr>
            <tr><td><strong>{{ __('Balance due') }}</strong></td><td class="right"><strong>{{ $invoice->format($invoice->balance()) }}</strong></td></tr>
        </table>

        @if ($invoice->notes)
            <div class="section"><div class="label">{{ __('Notes') }}</div><div>{!! nl2br(e($invoice->notes)) !!}</div></div>
        @endif
        @if ($invoice->terms)
            <div class="section"><div class="label">{{ __('Terms & conditions') }}</div><div class="muted">{!! nl2br(e($invoice->terms)) !!}</div></div>
        @endif

        <div class="footer">{{ __('Thank you for your business!') }} · {{ $company['name'] }}</div>
    </div>
</body>
</html>
