<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyUserEmail extends Command
{
    protected $signature = 'user:verify-email {email}';

    protected $description = 'Manually verify a user email';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User not found: {$email}");

            return 1;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info('User is already verified!');

            return 0;
        }

        $result = $user->markEmailAsVerified();

        $user->refresh();

        if ($user->hasVerifiedEmail()) {
            $this->info('✓ User email verified successfully!');
            $this->info("email_verified_at: {$user->email_verified_at}");
        } else {
            $this->error('✗ Failed to verify email. Result: '.($result ? 'true' : 'false'));
        }

        return 0;
    }
}
