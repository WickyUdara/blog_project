<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Mail\RegisterMail;
use App\Mail\ForgotPasswordMail;
use Hash;
use Mail;
use Str;
use Auth;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function register()
    {
        return view('auth.register');
    }
    public function forgot()
    {
        return view('auth.forgot');
    }

    public function reset(Request $request)
    {
        $user = User::where('remember_token', '=', $request->remember_token)->first();
        if (!empty($user)) {
            $data['user'] = $user;
            return view('auth.reset', $data);
        } else {
            abort(404);
        }
    }
    public function post_reset($token, Request $request)
    {
        $user = User::where('remember_token', '=', $token)->first();
        if (!empty($user)) {
            if ($request->password == $request->cpassword) {
                $user->email_verified_at = date('Y-m-d H:i:s');
                $user->password = Hash::make($request->password);
                $user->save();

                return redirect('login')->with('success', 'Password Successfully Updated!');
            } else {
                return redirect()->back()->with('error', "Password and confirm password does not match!");
            }
        } else {
            abort(404);
        }
    }


    public function forgot_password(Request $request)
    {
        $user = User::where('email', '=', $request->email)->first();
        if (!empty($user)) {
            $user->remember_token = Str::random(40);
            $user->save();
            Mail::to($user->email)->send(new ForgotPasswordMail($user));
            return redirect()->back()->with('success', "Please check your email and reset your password");
        } else {
            return redirect()->back()->with('error', "Email not found in the system");
        }
    }
    public function create_user(Request $request)
    {
        request()->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required'
        ]);

        $save = new User;
        $save->name = trim($request->name);
        $save->email = trim($request->email);
        $save->password = Hash::make($request->password);
        $save->remember_token = Str::random(40);
        $save->save();

        Mail::to($save->email)->send(new RegisterMail($save));

        return redirect('login')->with('success', "User Registered Successfully! Please Verify your email address");
    }
    public function verify($token)
    {
        $user = User::where('remember_token', '=', $token)->first();
        if (!empty($user)) {
            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->save();

            return redirect('login')->with('success', 'Your Account Successfully Verified!');
        } else {
            abort(404);
        }
    }

    public function auth_login(Request $request)
    {
        $remember = !empty($request->remember);
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $remember)) {
            if (!empty(Auth::user()->email_verified_at)) {
                return redirect('panel/dashboard');
            } else {
                $user_id = Auth::user()->id;
                Auth::logout();

                $save = User::getSingle($user_id);
                $save->remember_token = Str::random(40);
                $save->save();

                Mail::to($save->email)->send(new RegisterMail($save));
                return redirect('login')->with('success', "Please Verify your email address first");
            }
        } else {
            return redirect()->back()->with('error', 'Please enter correct email and password');
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect('login');
    }
}
