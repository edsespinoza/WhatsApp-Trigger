<?php

namespace App\Http\Controllers\WhatsTrigger;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $now = now();
        Subscription::create([
            'user_id' => $user->id,
            'plan' => Subscription::PLAN_FREE,
            'messages_limit' => Subscription::limitForPlan(Subscription::PLAN_FREE),
            'messages_sent' => 0,
            'period_start' => $now->toDateString(),
            'period_end' => $now->copy()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        return response()->json([
            'token' => $user->createToken('whatstrigger', ['*'], now()->addDays(30))->plainTextToken,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        $user = Auth::user();

        return response()->json([
            'token' => $user->createToken('whatstrigger', ['*'], now()->addDays(30))->plainTextToken,
            'user' => $user,
        ]);
    }
}
