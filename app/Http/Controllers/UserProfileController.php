<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('user.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validateWithBag('profile', ['name' => 'required|string|max:255']);
        $request->user()->update($data);

        return redirect()->route('user.profile.edit')->with('success', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('user.profile.edit')->with('password_success', 'Your password has been updated.');
    }
}
