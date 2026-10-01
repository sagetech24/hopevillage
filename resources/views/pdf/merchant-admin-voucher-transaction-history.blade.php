<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction History - {{ $voucher->name ?? 'Admin Voucher' }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #374151; }
        h1 { font-size: 16px; color: #111827; margin: 12px 0 10px; padding: 0; }
        h2 { font-size: 12px; color: #4b5563; margin: 16px 0 8px; font-weight: 600; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { border: none; vertical-align: middle; padding: 0; }
        .org-name { font-size: 16px; font-weight: bold; margin: 0; padding: 0; }
        .org-addr { font-size: 10px; margin: 2px 0 0; padding: 0; }
        .meta { margin-bottom: 12px; color: #6b7280; font-size: 10px; }
        .meta p { margin: 2px 0; padding: 0; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 8px; }
        table.data th, table.data td {
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.data th { background: #f9fafb; font-weight: 600; color: #374151; }
        .num { text-align: right; }
        .total { font-weight: 600; font-size: 14px; }
        .no-data { padding: 24px; text-align: center; color: #9ca3af; }
        .chunk { page-break-inside: auto; }
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
                <p class="org-addr">Address: 7 Kaki Bukit Ave 3, #01-110, Singapore 415814</p>
            </td>
        </tr>
    </table>

    <h1>Admin Voucher Transaction History</h1>

    <div class="meta">
        <p><strong>Merchant:</strong> {{ $merchant->name ?? '—' }}</p>
        <p><strong>Voucher:</strong> {{ $voucher->name ?? '—' }} ({{ $voucher->voucher_code ?? '—' }})</p>
        <p><strong>Validity:</strong>
            @if($voucher->valid_from && $voucher->valid_until)
                {{ $voucher->valid_from->format('d M Y') }} – {{ $voucher->valid_until->format('d M Y') }}
            @else
                {{ $voucher->valid_from?->format('d M Y') ?? $voucher->valid_until?->format('d M Y') ?? '—' }}
            @endif
        </p>
        <p><strong>Redeemed at this store:</strong> {{ number_format($transactions->count()) }}</p>
        <p><strong>Cost per voucher:</strong> SGD {{ number_format((float) $costPerVoucher, 2) }}</p>
        <p><strong>Total amount:</strong> SGD {{ number_format((float) $totalAmount, 2) }}</p>
    </div>

    <h2>Voucher Redemption Transactions</h2>

    @if ($transactions->isEmpty())
        <div class="no-data">No transactions found for this voucher.</div>
    @else
        @foreach ($transactions->chunk(80) as $chunk)
            <table class="data chunk">
                <colgroup>
                    <col style="width: 3%;">
                    <col style="width: 32%;">
                    <col style="width: 22%;">
                    <col style="width: 22%;">
                    <col style="width: 16%;">
                </colgroup>
                @if ($loop->first)
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member Name</th>
                            <th>Member Code</th>
                            <th>Redeemed At</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @foreach ($chunk as $tx)
                        <tr>
                            <td>{{ $tx->row_number }}</td>
                            <td>{{ $tx->member_name ?? '—' }}</td>
                            <td>{{ $tx->member_code ?? '—' }}</td>
                            <td>{{ $tx->redeemed_at ? \Carbon\Carbon::parse($tx->redeemed_at)->format('M d, Y H:i') : '—' }}</td>
                            <td class="num">SGD {{ number_format((float) $tx->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                @if ($loop->last)
                    <tfoot>
                        <tr>
                            <td colspan="4" class="num total">Total:</td>
                            <td class="num total">SGD {{ number_format((float) $totalAmount, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        @endforeach
    @endif
</body>
</html>
