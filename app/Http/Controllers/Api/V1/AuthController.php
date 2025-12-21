<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SMS\Kavehnegar;
use Illuminate\Http\Request;
class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/', // Validate Iranian phone number
        ]);

        $phoneNumber = $request->phone;
        $user = User::where('phone', $phoneNumber)->first();

        if (!$user) {
            $user = User::create(['phone' => $phoneNumber]);
        }

        // Generate a 6-digit OTP
        $otp = rand(1000, 9999);
        $expiresAt = now()->addMinutes(2);

        // Store OTP directly in the users table
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => $expiresAt,
        ]);

        // Send OTP via SMS (using an SMS gateway like Kavenegar)
        $sms = new Kavehnegar();
        return $sms->send_with_pattern($phoneNumber,$otp,'otp');
        return response()->json(['message' => 'OTP sent successfully']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'otp' => 'required|string|size:4',
        ]);

        $phoneNumber = $request->phone;
        $otp = $request->otp;

        $user = User::where('phone', $phoneNumber)
            ->where('otp_code', $otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        // Generate token with 1 year expiration
        $token = $user->createToken('auth_token', ['*'], now()->addYear())->plainTextToken;

        // Clear OTP after successful verification
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json(['token' => $token, 'user' => $user]);
    }

    private function sendSms($phoneNumber, $message)
    {
        $sms = new Kavehnegar();
        $sms->send_with_pattern($phoneNumber,$message);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
