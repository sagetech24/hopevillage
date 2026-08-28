<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLink
{
    /**
     * Handle the incoming password reset link request.
     */
    public function __invoke(Request $request)
    {
        $resetMethod = $request->input('reset_method', 'email');
        $identifier = $resetMethod === 'email' 
            ? $request->input('email') 
            : $request->input('whatsapp_number');

        // Validate input
        $rules = [
            'reset_method' => ['required', 'in:email,whatsapp'],
        ];

        if ($resetMethod === 'email') {
            $rules['email'] = ['required', 'email'];
        } else {
            $rules['whatsapp_number'] = ['required', 'string'];
        }

        $request->validate($rules);

        $action = app(RequestPasswordResetLink::class);
        $result = $action([
            'reset_method' => $resetMethod,
            'identifier' => $identifier,
        ]);

        if ($result->sent) {
            return redirect()->route('password.reset.verify');
        }

        return back()->with('status', $result->message);
    }
}

