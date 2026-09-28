<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reimbursement History - {{ $voucher->name ?? 'Admin Voucher' }}</title>
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

    <h1>Admin Voucher Reimbursement Transactions</h1>

    <div class="meta">
        <p><strong>Merchant:</strong> {{ $merchant->name ?? '—' }}</p>
        <p><strong>Voucher:</strong> {{ $voucher->name ?? '—' }} ({{ $voucher->voucher_code ?? '—' }})</p>
        <p><strong>Total Dispensed:</strong> ${{ number_format((float) $totalDispensed, 2) }}</p>
        <p><strong>Total Reimbursed:</strong> ${{ number_format((float) $totalReimbursed, 2) }}</p>
        <p><strong>Outstanding Balance:</strong> ${{ number_format((float) $outstanding, 2) }}</p>
    </div>

    <h2>Voucher Reimbursement Transactions</h2>

    @if ($reimbursements->isEmpty())
        <div class="no-data">No reimbursements found for this voucher.</div>
    @else
        @foreach ($reimbursements->chunk(80) as $chunk)
            <table class="data chunk">
                <colgroup>
                    <col style="width: 8%;">
                    <col style="width: 18%;">
                    <col style="width: 22%;">
                    <col style="width: 32%;">
                    <col style="width: 20%;">
                </colgroup>
                @if ($loop->first)
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Period</th>
                            <th>Reimbursed At</th>
                            <th>Notes</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @foreach ($chunk as $reimbursement)
                        <tr>
                            <td>{{ $reimbursement->row_number }}</td>
                            <td>{{ $reimbursement->ledgerEntry?->period_month?->format('M Y') ?? '—' }}</td>
                            <td>{{ \Carbon\Carbon::parse($reimbursement->reimbursed_at)->format('M d, Y') }}</td>
                            <td>{{ $reimbursement->notes ?? '—' }}</td>
                            <td class="num">${{ number_format((float) $reimbursement->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                @if ($loop->last)
                    <tfoot>
                        <tr>
                            <td colspan="4" class="num total">Total:</td>
                            <td class="num total">${{ number_format((float) $totalReimbursed, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        @endforeach
    @endif
</body>
</html>
