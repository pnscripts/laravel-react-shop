<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('Invoice :number', ['number' => $invoice->number]) }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f4f5; color: #18181b; font: 14px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 820px; margin: 24px auto; background: #fff; padding: 48px; border-radius: 8px; }
        h1 { margin: 0 0 4px; font-size: 26px; }
        .muted { color: #71717a; }
        .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin: 32px 0; }
        .parties h2 { margin: 0 0 6px; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #71717a; }
        .parties p { margin: 0; white-space: pre-line; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 6px; text-align: left; border-bottom: 1px solid #e4e4e7; vertical-align: top; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #71717a; }
        .num { text-align: right; white-space: nowrap; }
        .totals { margin-left: auto; width: 320px; margin-top: 16px; }
        .totals td { border: 0; padding: 4px 6px; }
        .totals .grand td { border-top: 2px solid #18181b; font-weight: 700; font-size: 16px; padding-top: 8px; }
        footer { margin-top: 40px; font-size: 12px; white-space: pre-line; }
        .print { position: fixed; right: 16px; top: 16px; padding: 8px 14px; border: 0; border-radius: 6px; background: #18181b; color: #fff; cursor: pointer; }
        @media (max-width: 640px) { main { margin: 0; padding: 20px; border-radius: 0; } .parties { grid-template-columns: 1fr; } .totals { width: 100%; } }
        @media print { body { background: #fff; } main { margin: 0; padding: 0; max-width: none; } .print { display: none; } }
    </style>
</head>
<body>
<button class="print" type="button" onclick="window.print()">{{ __('Print') }}</button>
<main>
    <h1>{{ __('Invoice :number', ['number' => $invoice->number]) }}</h1>
    <div class="muted">{{ __('Date: :date', ['date' => $date]) }} · {{ __('Order :number', ['number' => $invoice->order?->number]) }}</div>

    <section class="parties">
        <div>
            <h2>{{ __('Seller') }}</h2>
            <p><strong>{{ $invoice->seller['name'] }}</strong>
@if ($invoice->seller['address']){{ $invoice->seller['address'] }}
@endif
@if ($invoice->seller['tax_number']){{ __('Tax number: :number', ['number' => $invoice->seller['tax_number']]) }}
@endif
@if ($invoice->seller['email']){{ $invoice->seller['email'] }}
@endif
{{ $invoice->seller['phone'] }}</p>
        </div>
        <div>
            <h2>{{ __('Bill to') }}</h2>
            <p>{{ implode("\n", $invoice->buyer['lines']) }}
{{ $invoice->buyer['email'] }}</p>
        </div>
    </section>

    <table>
        <thead>
        <tr>
            <th>{{ __('Item') }}</th>
            <th class="num">{{ __('Qty') }}</th>
            <th class="num">{{ __('Unit price') }}</th>
            <th class="num">{{ __('Tax') }}</th>
            <th class="num">{{ __('Total') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($invoice->lines as $line)
            <tr>
                <td>{{ $line['title'] }}@if ($line['sku'])<div class="muted">{{ $line['sku'] }}</div>@endif</td>
                <td class="num">{{ $line['quantity'] }}</td>
                <td class="num">{{ $money($line['unit']) }}</td>
                <td class="num">{{ $line['tax'] > 0 ? $money($line['tax']) : '—' }}</td>
                <td class="num">{{ $money($line['total']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>{{ __('Subtotal') }}</td><td class="num">{{ $money($invoice->totals['subtotal']) }}</td></tr>
        @foreach ($invoice->totals['lines'] as $line)
            <tr class="muted">
                <td>{{ $line['included'] ? __(':label (included)', ['label' => $line['label']]) : $line['label'] }}</td>
                <td class="num">{{ $money($line['amount']) }}</td>
            </tr>
        @endforeach
        <tr class="grand"><td>{{ __('Total') }}</td><td class="num">{{ $money($invoice->totals['total']) }}</td></tr>
    </table>

    @if ($invoice->seller['footer'])
        <footer class="muted">{{ $invoice->seller['footer'] }}</footer>
    @endif
</main>
</body>
</html>
