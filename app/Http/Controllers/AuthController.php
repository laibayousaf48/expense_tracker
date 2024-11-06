<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        try {
            // Validate incoming request data
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);
    
            // Create the user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);
    
            // Success response
            return response()->json([
                'message' => 'User registered successfully.',
                'user' => $user,
            ], 201);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
    
        } catch (\Exception $e) {
            // Handle any other unexpected errors
            return response()->json([
                'message' => 'An error occurred while registering the user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    // Login an existing user
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|string|email',
    //         'password' => 'required|string',
    //     ]);

    //     if (!Auth::attempt($request->only('email', 'password'))) {
    //         throw ValidationException::withMessages([
    //             'email' => ['The provided credentials are incorrect.'],
    //         ]);
    //     }

    //     $user = Auth::user();
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         'message' => 'Login successful.', 
    //         'access_token' => $token, 
    //         'token_type' => 'Bearer'
    //     ]);
    // }
    public function login(Request $request)
    {
        try {
            // Check if the user is already logged in
            if (Auth::check()) {
                return response()->json([
                    'message' => 'User is already logged in.',
                    'user' => Auth::user()
                ], 200);
            }
    
            // Validate incoming request data
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);
    
            // Attempt to authenticate the user
            if (!Auth::attempt($request->only('email', 'password'))) {
                // If authentication fails, throw a validation exception with a message
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
    
            // Generate a new token for the authenticated user
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;
    
            // Return success response with token
            return response()->json([
                'message' => 'Login successful.', 
                'access_token' => $token, 
                'token_type' => 'Bearer'
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
    
        } catch (\Exception $e) {
            // Handle any other unexpected errors
            return response()->json([
                'message' => 'An error occurred during login.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    // Logout the authenticated user
    public function logout()
    {
        Auth::user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }



    //extra functionalities
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ]);

        $user = $request->user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return response()->json(['user' => $user], 200);
    }


    // Reset password request
    public function resetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['status' => __($status)], 200)
            : response()->json(['email' => __($status)], 400);
    }

    // Confirm password reset
    public function confirmReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|confirmed',
            'token' => 'required|string',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['status' => __($status)], 200)
            : response()->json(['email' => __($status)], 400);
    }

}

