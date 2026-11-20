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
        $data = $request->validate(['name' => 'required|string|max:255']);
        $request->user()->update($data);

        return redirect()->route('user.profile.edit')->with('success', 'Your profile has been updated.');
    }
}
