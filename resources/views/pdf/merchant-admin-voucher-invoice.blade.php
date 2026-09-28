<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #374151; }
        h1 { font-size: 18px; color: #111827; margin: 12px 0 4px; padding: 0; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { border: none; vertical-align: top; padding: 0; }
        .org-name { font-size: 16px; font-weight: bold; margin: 0; padding: 0; }
        .org-addr { font-size: 10px; margin: 2px 0 0; padding: 0; color: #6b7280; }
        .meta { margin: 8px 0 16px; color: #4b5563; font-size: 11px; }
        .meta p { margin: 2px 0; padding: 0; }
        .parties { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .parties td { width: 50%; vertical-align: top; padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; margin: 0 0 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { padding: 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        table.data th { background: #f9fafb; font-weight: 600; color: #374151; }
        .num { text-align: right; }
        .total { font-weight: 700; font-size: 13px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 90px;">
                @if (!empty($logoSrc))
                    <img src="{{ $logoSrc }}" width="75" height="67" alt="Hope Village">
                @endif
            </td>
            <td>
                <p class="org-name">Hope Village Kaki Bukit Recreation Centre</p>
                <p class="org-addr">7 Kaki Bukit Ave 3, #01-110, Singapore 415814</p>
            </td>
        </tr>
    </table>
    <br />
    <br />
    <h1>Invoice {{ $invoice->invoice_number }}</h1>
    <div class="meta">
        <p><strong>Invoice date:</strong> {{ $invoice->generated_at?->format('d M Y') }}</p>
        <p><strong>Prepared by:</strong> {{ $invoice->generatedBy?->name ?? '—' }}</p>
    </div>

    <table class="parties">
        <tr>
            <td>
                <p class="label">From</p>
                <p><strong>{{ $merchant?->name ?? '—' }}</strong></p>
                <p>{{ $merchant?->merchant_code ?? '—' }}</p>
                <p>{{ $merchant?->address ?: '—' }}</p>
                <p style="margin-top: 8px;"><strong>Bank:</strong> {{ $invoice->bank_account['bank_name'] ?? '—' }}</p>
                <p><strong>Account name:</strong> {{ $invoice->bank_account['account_name'] ?? '—' }}</p>
                <p><strong>Account number:</strong> {{ $invoice->bank_account['account_number'] ?? '—' }}</p>
            </td>
            <td>
                <p class="label">Bill to</p>
                <p><strong>Hope Village Kaki Bukit Recreation Centre</strong></p>
                <p>7 Kaki Bukit Ave 3, #01-110, Singapore 415814</p>
            </td>
        </tr>
    </table>

    {{-- <p><strong>Voucher:</strong> {{ $invoice->voucher_name }} ({{ $invoice->voucher_code }})</p> --}}
    <p><strong>Date covered:</strong> {{ $invoice->validityLabel() }}</p>
    <br />
    <table class="data">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Total Redeemed</th>
                <th class="num">Cost per voucher</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->voucher_name }}</td>
                <td class="num">{{ number_format($invoice->redeemed_count) }}</td>
                <td class="num">SGD {{ number_format((float) $invoice->cost_per_voucher, 2) }}</td>
                <td class="num">SGD {{ number_format((float) $invoice->amount, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="num total">Total due</td>
                <td class="num total">SGD {{ number_format((float) $invoice->amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
