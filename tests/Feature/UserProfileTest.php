<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    public function test_password_change_requires_current_password_and_confirmation(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password');
            $table->rememberToken(); $table->timestamps(); $table->softDeletes();
        });
        $user = User::create(['name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'old-password']);
        $this->actingAs($user)->put(route('user.profile.password.update'), [
            'current_password' => 'wrong-password', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertSessionHasErrorsIn('password', 'current_password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->put(route('user.profile.password.update'), [
            'current_password' => 'old-password', 'password' => 'new-password', 'password_confirmation' => 'different-password',
        ])->assertSessionHasErrorsIn('password', 'password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->put(route('user.profile.password.update'), [
            'current_password' => 'old-password', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertRedirect(route('user.profile.edit'))->assertSessionHas('password_success');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
