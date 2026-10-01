<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
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
            'logoSrc' => $this->merchantLogoDataUri($invoice->merchant),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'invoice-%s.pdf',
            Str::slug($invoice->invoice_number) ?: 'invoice'
        );

        return $pdf->stream($filename);
    }

    private function merchantLogoDataUri(?Merchant $merchant): ?string
    {
        $media = $merchant?->getFirstMedia('logo');

        if (! $media) {
            return null;
        }

        $mime = (string) $media->mime_type;
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            return null;
        }

        $path = $media->getPath();
        if (! is_file($path)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
