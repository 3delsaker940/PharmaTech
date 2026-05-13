<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SocialLoginController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

   public function callback($provider)
{
    // جلب بيانات المستخدم من Google
    $googleUser = Socialite::driver($provider)->stateless()->user();

    // البحث عن المستخدم
    $user = User::where('email', $googleUser->email)->first();

    // إنشاء مستخدم إذا لم يكن موجودًا
    if (!$user) {

        $user = User::create([
            'first_name'    => $googleUser->user['given_name'] ?? '',
            'middle_name'   => '',
            'last_name'     => $googleUser->user['family_name'] ?? '',
            'email'         => $googleUser->email,
            'phone_number'  => '',
            'licence_number'=> '',
            'password'      => bcrypt(uniqid()),
        ]);
    }

    // تسجيل الدخول
    Auth::login($user);

    // توجيه المستخدم
    return redirect('/');
}
}
