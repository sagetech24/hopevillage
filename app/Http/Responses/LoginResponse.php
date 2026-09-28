<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        // Skip verification redirect - allow unverified users to access dashboard
        // Verification will be handled later in the dashboard

        // if ($user && $user->isMember() && !$user->is_verified) {
        //     return redirect()->route('verification.code.show');
        // }

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isMember()) {
            return redirect()->route('member.dashboard');
        } elseif ($user->isMerchantUser()) {
            return redirect()->route('merchant.dashboard.v2');
        }

        return redirect()->route('dashboard');
    }
}
