<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 24px 28px 120px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #374151; }
        h1 { font-size: 18px; color: #111827; margin: 12px 0 4px; padding: 0; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { border: none; vertical-align: top; padding: 0; }
        .org-name { font-size: 16px; font-weight: bold; margin: 0; padding: 0; text-transform: uppercase; }
        .org-addr { font-size: 10px; margin: 2px 0 0; padding: 0; color: #6b7280; }
        .semi-bold { font-weight: 600; font-family: DejaVu Sans, sans-serif; }
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
        .signatures {
            position: fixed;
            bottom: 0px;
            left: 0;
            right: 0;
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }
        .signatures td { width: 50%; vertical-align: top; padding: 0; }
        .signatures td.left { padding-right: 28px; }
        .signatures td.right { padding-left: 28px; }
        .sign-line { border-bottom: 1px solid #111827; height: 42px; margin-bottom: 6px; }
        .sign-caption { font-size: 10px; color: #6b7280; margin: 0; }
        .sign-name { font-size: 11px; font-weight: 600; color: #111827; margin: 2px 0 0; text-transform: uppercase; }
    </style>
</head>
<body>
    @php
        $issuerAddress = trim(implode(', ', array_filter([
            $merchant?->address,
            $merchant?->city,
            $merchant?->province,
            $merchant?->postal_code,
        ])));
        $issuerContact = collect([$merchant?->phone, $merchant?->email])->filter()->implode(' · ');
        $billToLocation = \App\Models\Location::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
        $billToAddress = $billToLocation?->formattedAddress() ?: '—';
    @endphp
    <table class="header-table">
        <tr>
            @if (!empty($logoSrc))
                <td style="width: 90px;">
                    <img src="{{ $logoSrc }}" width="75" height="67" alt="{{ $merchant?->name ?? 'Merchant' }}">
                </td>
            @endif
            <td>
                <p class="org-name">{{ $merchant?->name ?? '—' }}</p>
                @if (filled($invoice->uen))
                    <p class="org-addr">UEN : {{ $invoice->uen }}</p>
                @endif
                @if ($issuerAddress !== '')
                    <p class="org-addr">Address: {{ Str::limit($issuerAddress, 110, '...') }}</p>
                @endif
                @if ($merchant?->phone !== '')
                    <p class="org-addr">Tel. No.: {{ $merchant?->phone }}</p>
                @endif
                @if ($merchant?->email !== '')
                    <p class="org-addr">Email: {{ $merchant?->email }}</p>
                @endif
                @if ($merchant?->website !== '')
                    <p class="org-addr">Website: {{ $merchant?->website }}</p>
                @endif
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
                <p>
                    <strong style="text-transform:uppercase;">{{ $merchant?->name ?? '—' }}</strong>
                    <br />
                    <span class="org-addr">{{ $merchant?->merchant_code ?? '—' }}</span>
                    <br />
                    @if ($merchant?->address !== '')
                        <span class="org-addr">{{ $merchant?->address }}</span>
                    @endif
                </p>
                <p style="margin-top: 8px;">
                    <strong>Bank:</strong> {{ $invoice->bank_account['bank_name'] ?? '—' }}
                    <br />
                    <strong>Account name:</strong> {{ $invoice->bank_account['account_name'] ?? '—' }}
                    <br />
                    <strong>Account number:</strong> {{ $invoice->bank_account['account_number'] ?? '—' }}
                </p>
            </td>
            <td>
                <p class="label">Bill to</p>
                <p>
                    <strong style="text-transform:uppercase;">HOPE INITIATIVE ALLIANCE</strong>
                    <br />
                    <span class="org-addr" style="font-family: DejaVu Sans, sans-serif;">{{ $billToLocation?->name ?? '—' }}</span>
                    <br />
                    <span class="org-addr">{{ $billToAddress }}</span>
                </p>
            </td>
        </tr>
    </table>

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

    <table class="signatures">
        <tr>
            <td class="left">
                <div class="sign-line"></div>
                <p class="sign-caption" style="text-align: center; font-family: DejaVu Sans, sans-serif;">Name & Signature</p>
                {{-- <p class="sign-name">{{ $merchant?->name ?? '—' }}</p> --}}
            </td>
            <td>&nbsp;</td>
            <td class="right">
                <div class="sign-line"></div>
                <p class="sign-caption" style="text-align: center; font-family: DejaVu Sans, sans-serif;">Company Stamp / Date</p>
                {{-- <p class="sign-name">Hope Initiative Alliance</p> --}}
            </td>
        </tr>
    </table>
</body>
</html>