<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        // Auto-login after registration - skip verification for now
        // Verification will be handled later in the dashboard

        // Redirect based on user type
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isMember()) {
            return redirect()->route('member.events');
        } elseif ($user->isMerchantUser()) {
            return redirect()->route('merchant.dashboard.v2');
        }

        return redirect()->route('dashboard');
    }
}
