<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EmailVerificationController extends Controller
{
    /**
     * Verify the user's email when they click the link in the verification email.
     */
    public function verify(Request $request, $id, $hash)
    {
        try {
            \Log::info('Verification attempt', [
                'id' => $id,
                'hash' => $hash,
                'url' => $request->fullUrl(),
                'route_params' => $request->route()->parameters(),
                'all_params' => $request->all(),
            ]);

            // Route parameters are already available as method arguments
            $validated = [
                'id' => $id,
                'hash' => $hash,
            ];
            \Log::info('Parameters received', $validated);

            $user = \App\Models\User::findOrFail($id);
            \Log::info('User found', ['user_id' => $user->id, 'email' => $user->email]);

            $expectedHash = sha1($user->getEmailForVerification());
            \Log::info('Hash comparison', [
                'expected' => $expectedHash,
                'received' => $hash,
                'match' => hash_equals((string) $hash, $expectedHash),
            ]);

            if (! hash_equals((string) $hash, $expectedHash)) {
                \Log::error('Hash mismatch - verification failed');
                return redirect()->route('login')->with('error', 'Invalid verification link. Please request a new verification email.');
            }

            if ($user->hasVerifiedEmail()) {
                \Log::info('User already verified', ['user_id' => $user->id]);
                return redirect()->route('login')->with('success', 'Your email is already verified. You can log in.');
            }

            $result = $user->markEmailAsVerified();
            \Log::info('Email marked as verified', [
                'user_id' => $user->id,
                'result' => $result,
                'email_verified_at' => $user->fresh()->email_verified_at,
            ]);

            event(new Verified($user));

            return redirect()->route('login')->with('success', 'Your email has been verified. You can now log in.');
        } catch (\Exception $e) {
            \Log::error('Verification error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('login')->with('error', 'Verification failed: ' . $e->getMessage());
        }
    }
}
