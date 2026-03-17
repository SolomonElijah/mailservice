<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function showForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'company'  => 'nullable|string|max:100',
            'terms'    => 'accepted',
        ], [
            'terms.accepted'    => 'You must accept the terms and conditions.',
            'password.confirmed'=> 'Passwords do not match.',
            'email.unique'      => 'This email is already registered.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name'    => trim($request->name),
            'email'   => strtolower(trim($request->email)),
            'password'=> Hash::make($request->password),
            'company' => $request->company ?? null,
            'role'    => User::count() === 0 ? 'admin' : 'user', // First user is admin
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', "Welcome, {$user->name}! Your account has been created.");
    }
}
