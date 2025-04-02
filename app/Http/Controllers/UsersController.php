<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\PasswordLogs;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Cache;

class UsersController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:tbl_main_users,username|max:25',
            'password' => 'required|string|min:8|max:25',
            'user_rank' => 'required|string|max:15',
            'sys_type' => 'required|string|max:10',
            'active_status' => 'required|string|max:1',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $user = User::create([
            'id_code' => time(), // Generate a unique id_code based on timestamp
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'user_rank' => $request->user_rank,
            'sys_type' => $request->sys_type,
            'active_status' => $request->active_status,
            'mod_access' => $request->mod_access ?? null, // Optional
            'approval_access' => $request->approval_access ?? '',
            'approval_code' => $request->approval_code ?? '',
        ]);
    
        $token = JWTAuth::fromUser($user);
    
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user->makeHidden(['password']),
            'token' => $token
        ], 201);
    }     

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|min:8|max:12',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid username'], 401);
        } elseif (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Incorrect password'], 401);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user->makeHidden(['password']),
        ]);
    }

    public function dashboard(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token is expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        return response()->json([
            'user' => $user,
            'message' => 'Welcome to your dashboard'
        ]);
    }

    public function sendOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);
    
            $email = $request->email;
            $otp = mt_rand(100000, 999999);
            
            // Limit OTP requests (e.g., max 3 per 10 minutes)
            $requestCountKey = 'otp_requests_' . $email;
            $requestCount = Cache::get($requestCountKey, 0);
            
            if ($requestCount >= 3) {
                return response()->json([
                    'message' => 'Too many OTP requests. Please try again later.'
                ], 429);
            }
    
            // Increase request count
            Cache::put($requestCountKey, $requestCount + 1, now()->addMinutes(10));
    
            // Store OTP securely using hashing
            Cache::put('otp_' . $email, bcrypt($otp), now()->addMinutes(10));
            Cache::put('otp_time_' . $email, now(), now()->addMinutes(10));
            Cache::forget('otp_attempts_' . $email); // Reset attempt counter
    
            // Send OTP via email
            Mail::to($email)->send(new OtpMail($otp));
    
            return response()->json(['message' => 'OTP sent to your email']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send OTP', 'details' => $e->getMessage()], 500);
        }
    }    

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);
    
        $email = $request->email;
        $otp = $request->otp;
    
        // Limit verification attempts
        $attempts = Cache::get('otp_attempts_' . $email, 0);
        if ($attempts >= 5) {
            return response()->json(['success' => false, 'message' => 'Too many attempts. Try again later.'], 429);
        }
    
        // Retrieve OTP from cache
        $cachedOtp = Cache::get('otp_' . $email);
        if (!$cachedOtp || !Hash::check($otp, $cachedOtp)) {
            Cache::put('otp_attempts_' . $email, $attempts + 1, now()->addMinutes(10));
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP'], 401);
        }
    
        // Clear OTP and attempt counter
        Cache::forget('otp_' . $email);
        Cache::forget('otp_attempts_' . $email);
        Cache::forget('otp_requests_' . $email); // Reset OTP request limit
    
        return response()->json(['success' => true, 'message' => 'OTP verified successfully']);
    }    

    public function logout(Request $request)
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }

            JWTAuth::invalidate($token);

            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Failed to log out'], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|min:8|max:25',
            'new_password' => 'required|string|min:8|max:25|confirmed',
        ]);

        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 401);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            PasswordLOgs::create([
                'id_code' => $user->id_code,
                'device_id' => $request->device_id,
                'timestamp' => now(),
            ]);

            return response()->json(['message' => 'Password changed successfully']);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token not provided or invalid'], 401);
        }
    }
}