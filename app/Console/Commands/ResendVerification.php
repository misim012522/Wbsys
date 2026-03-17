<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResendVerification extends Command
{
    protected $signature = 'user:resend-verification {email}';
    protected $description = 'Resend verification email';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }
        
        // Mark as unverified first
        $user->email_verified_at = null;
        $user->save();
        
        $user->sendEmailVerificationNotification();
        
        $this->info("✓ Verification email sent to {$email}");
        $this->info("Check your inbox and click the link to verify.");
        
        return 0;
    }
}
