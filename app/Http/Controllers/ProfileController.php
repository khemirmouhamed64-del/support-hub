<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['member' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $member = Auth::user();

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:team_members,email,' . $member->id,
        ]);

        $member->update($data);

        return back()->with('success', __('auth.profile_updated'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $member = Auth::user();

        if (!Hash::check($request->current_password, $member->password)) {
            return back()->withErrors(['current_password' => __('auth.current_password_incorrect')]);
        }

        $member->update(['password' => Hash::make($request->password)]);

        return back()->with('success', __('auth.password_changed'));
    }
}
