<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMerchantUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->isMerchantUser()) {
            abort(403, 'Unauthorized access. Merchant user privileges required.');
        }

        // If the user has merchants but none is selected, use the first one.
        // Merchant users with no store yet can still open the merchant dashboard.
        if (!$user->currentMerchant()) {
            $firstMerchant = $user->merchants()->first();
            if ($firstMerchant) {
                $user->setCurrentMerchant($firstMerchant->id);
            }
        }

        return $next($request);
    }
}
