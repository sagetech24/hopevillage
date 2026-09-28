<?php

namespace App\Http\Controllers;

use App\Models\MerchantAdminVoucherInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantAdminVoucherInvoicePdfController extends Controller
{
    public function __invoke(Request $request, MerchantAdminVoucherInvoice $invoice)
    {
        $user = $request->user();

        if (! $user?->isAdmin()) {
            $merchantId = $user?->currentMerchant()?->id;
            abort_unless($user?->isMerchantUser() && $merchantId === $invoice->merchant_id, 403);
        }

        $invoice->load(['merchant', 'generatedBy']);

        $pdf = Pdf::loadView('pdf.merchant-admin-voucher-invoice', [
            'invoice' => $invoice,
            'merchant' => $invoice->merchant,
            'logoSrc' => $this->logoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'invoice-%s.pdf',
            Str::slug($invoice->invoice_number) ?: 'invoice'
        );

        return $pdf->stream($filename);
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('hv-logo.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
