<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OauthAccessTokens;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $errors = [];
        if (!empty($request->number)) {
            $user = User::where('number', $request->number)->first();
            if (!is_numeric($request->number) || strlen((string)$request->number) != 10) {
                $errors['number'] = "Phone number must be exactly 10 digits.";
            }else if(!empty($user->id)){
                $errors['number'] = "Phone number already exist.";
            }
        } else {
            $errors['number'] = "Phone number is required";
        }
        if (!empty($request->password)) {

            if (strlen($request->password) < 8) {
                $errors['password'] = "Password must be at least 8 characters long.";
            }
            if (!preg_match('/[A-Z]/', $request->password)) {
                $errors['password'] = "Password must contain at least one uppercase letter.";
            }
            if (!preg_match('/[a-z]/', $request->password)) {
                $errors['password'] = "Password must contain at least one lowercase letter.";
            }
            if (!preg_match('/\d/', $request->password)) {
                $errors['password'] = "Password must contain at least one digit.";
            }
            if (!preg_match('/[^a-zA-Z0-9]/', $request->password)) {
                $errors['password'] = "Password must contain at least one special character.";
            }
            if (preg_match('/\s/', $request->password)) {
                $errors['password'] = "Password must not contain any whitespace characters.";
            }
        } 
        if (!empty($errors)) {
            return response()->json([
                'error' => true,
                'msg' => $errors
            ], 422);
        }
        if (isset($request->number) && isset($request->email)) {

            $user = new User([
                'first_name' => !empty($request->first_name) ? $request->first_name : '',
                'last_name' => !empty($request->last_name) ? $request->last_name : '',
                'number' => $request->number,
                'email' => $request->email,
                'password' => !empty($request->password) ? bcrypt($request->password) : ""
            ]);
            $user->save();
            if (!empty($user->id)) {
                
                $user->createToken('LaravelAuthApp')->accessToken;
                return response()->json(['error' => false,  "msg" => "Hi user, your registration is successful. Please Proceed to Login."], 200);
            } else {
                return response()->json(['error' => true, 'msg' => 'user not created'], 401);
            }
        } else {
            return response()->json(['error' => true, 'msg' => 'Please provide all required fields'], 401);
        }
    }
    public function login(Request $request)
    {
        $credentials = $request->only('number', 'password');

        if (auth()->attempt($credentials)) {
            $token = auth()->user()->createToken('auth_token')->accessToken;
            $user = auth()->user();
            unset($user->password);
            return response()->json(['token' => $token,"user"=>$user], 200);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
    public function logout(Request $request)
    {
        $user = OauthAccessTokens::where('user_id', $request->user_id)->first();
        if (!empty($user->user_id)) {
            $user_in = User::where('id', $request->user_id)->first();
            $user_in->fcm_token = "";
            $user_in->save();
            $user->delete();
            return response()->json(['success' => true], 200);
        } else {
            return response()->json(['error' => true], 200);
        }
    }
}
