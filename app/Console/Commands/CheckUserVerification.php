<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckUserVerification extends Command
{
    protected $signature = 'user:check-verification {email?}';

    protected $description = 'Check user verification status';

    public function handle()
    {
        $email = $this->argument('email');

        if ($email) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->error("User not found: {$email}");

                return 1;
            }
            $this->info("User: {$user->name} ({$user->email})");
            $this->info("ID: {$user->id}");
            $this->info('Email Verified At: '.($user->email_verified_at ?? 'NOT VERIFIED'));
            $this->info("Created At: {$user->created_at}");
        } else {
            $this->info('Recent users:');
            User::orderBy('created_at', 'desc')->take(5)->each(function ($user) {
                $verified = $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : 'NOT VERIFIED';
                $this->line("{$user->id} | {$user->name} | {$user->email} | {$verified}");
            });
        }

        return 0;
    }
}
