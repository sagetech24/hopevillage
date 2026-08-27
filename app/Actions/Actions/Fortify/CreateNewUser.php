<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use App\Rules\ValidRecaptcha;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Generate a random email for the user
     */
    protected function generateUserRandomEmail(): string
    {
        // Generate email: user-uuid@hopevillage.sg
        do {
            $uuid = explode('-', Str::uuid()->toString())[0];
            $email = 'user-' . $uuid . '@hopevillage.sg';
        } while (User::where('email', $email)->exists());
        
        return $email;
    }

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Make email optional - if not provided, generate from WhatsApp
        $email = !empty(trim($input['email'] ?? '')) 
            ? trim($input['email']) 
            : null;

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',  // Changed from 'required'
                'string', 
                'email', 
                'max:255', 
                'unique:users'  // Only validate if email is provided
            ],
            'whatsapp_number' => [
                'required',
                'string',
                'max:12',
                'unique:users,whatsapp_number',
            ],
            'fin' => ['required', 'string', 'size:4', 'regex:/^\d{3}[A-Z]$/i'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'gender' => ['nullable', 'string', 'max:20'],
            'type_of_work' => ['nullable', 'string', 'max:255', 'in:Migrant worker,Migrant domestic worker'],
            // 'g-recaptcha-response' => config('services.recaptcha.secret_key') ? ['required', new ValidRecaptcha()] : ['nullable'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ], [
            'whatsapp_number.unique' => 'This mobile number is already registered.',
            'whatsapp_number.required' => 'This mobile number is required.',
            'whatsapp_number.max' => 'The mobile number must be 8 digits long.',
            'fin.required' => 'The FIN/NIRC is required.',
            'fin.regex' => 'The FIN/NIRC is invalid. Example: 123W',
            'fin.size' => 'It must be the last 4 characters only. (e.g. 124X)',
        ])->validate();

        return DB::transaction(function () use ($input, $email) {
            // Generate email from WhatsApp if not provided
            if (empty($email)) {
                $email = $this->generateUserRandomEmail();
            }

            $userData = [
                'name' => $input['name'],
                'email' => $email,
                'password' => Hash::make($input['password']),
                'user_type' => $input['user_type'] ?? 'member', // Default to member
                'whatsapp_number' => $input['whatsapp_number'] ?? null,
                'age' => $input['age'] ?? null,
                'gender' => $input['gender'] ?? null,
                'type_of_work' => $input['type_of_work'] ?? 'Migrant worker',
            ];

            // Get FIN from request instead of auto-generating
            // Use FIN from request
            if (!empty($input['fin'])) {
                $userData['fin'] = strtoupper(trim($input['fin']));
            }

            // Generate unique 9-character QR code for members
            if (($userData['user_type'] ?? 'member') === 'member') {
                // Generate a unique 9-character QR code
                do {
                    $qrCode = strtoupper(Str::random(9));
                } while (User::where('qr_code', $qrCode)->exists());
                
                $userData['qr_code'] = $qrCode;
            }

            // Handle referral code (non-blocking validation)
            $referrer = null;
            $referralCode = trim($input['ref'] ?? '');
            
            if (!empty($referralCode)) {
                // Attempt to find referrer by QR code
                // Non-blocking: if invalid, silently continue without referral
                $referrer = User::where('qr_code', strtoupper($referralCode))
                    ->where('user_type', 'member')
                    ->whereNull('deleted_at') // Exclude soft-deleted users
                    ->first();
                
                // Prevent self-referral (though unlikely at registration, check anyway)
                // We'll check this after user creation since we need the new user's QR code
            }
            
            // Set referred_by_user_id if referrer is valid
            // We'll validate self-referral after user creation
            if ($referrer) {
                $userData['referred_by_user_id'] = $referrer->id;
            }

            return tap(User::create($userData), function (User $user) use ($referrer) {
                if (Jetstream::userHasTeamFeatures($user)) {
                    $this->createTeam($user);
                }
                
                // Award 10 points for registration (only for members)
                if ($user->isMember()) {
                    app(PointsService::class)->awardRegistration($user);
                }
                
                // Award referral points if valid referral exists
                // Check self-referral prevention: if user's QR code matches referrer's QR code, invalidate
                if ($referrer && $user->isMember() && $user->qr_code !== $referrer->qr_code) {
                    // Ensure referrer is still valid (not deleted, still a member)
                    $referrer->refresh();
                    if ($referrer->user_type === 'member' && !$referrer->trashed()) {
                        app(PointsService::class)->awardReferral($referrer, $user);
                    } 
                    else {
                        // Referrer became invalid, remove referral link
                        $user->update(['referred_by_user_id' => null]);
                    }
                } elseif ($referrer && $user->qr_code === $referrer->qr_code) {
                    // Self-referral detected, remove referral link
                    $user->update(['referred_by_user_id' => null]);
                }
            });
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
