<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Actions\Fortify\PasswordValidationRules;

class UserController extends Controller
{
    use PasswordValidationRules;

    public function login(Request $request)
    {
        try {
            // validasi inputan
            $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);

            // cek credential login
            $credentials = request(['email', 'password']);
            if(!Auth::attempt($credentials)){
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Filed', 500);
            }

            // jika hash tidak sesuai maka notif error
            $user = User::where('email', $request->email)->first();
            if(!Hash::check($request->password, $user->password, [])){
                throw new \Exception('Invalid Credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'acsess_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ]);

        } catch (Exception $error) {
             return ResponseFormatter::error([
                    'message' => 'Error',
                    'error' => $error,
                ], 'Authentication Filed', 500);
        }
    }

    public function register(Request $request)
    {
        try {
            // validasi user
            $request->validate([
                'name' => ['required', 'string', 'max:225'],
                'email' => ['required', 'string', 'email','max:225', 'unique:users'],
                'password' => $this->passwordRules()

            ]);
            // insert data ke user 
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'houseNumber' => $request->houseNumber,
                'phoneNumber' => $request->phoneNumber,
                'city' => $request->city,
                'password' => Hash::make($request->password), //password menggunakan hash
            ]);

            // cek user by email request after save

            $user = User::where('email', $request->email)->first();

            // get token 
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            // jika sukses maka rsponnya sbg berikut 

            return ResponseFormatter::success([
                'acsess_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ]);


        } catch (Exception $error) {
            return ResponseFormatter::error([
                    'message' => 'Error',
                    'error' => $error,
                ], 'Authentication Filed', 500);
        }
    }

    public function logout(Request $request)
    {
       $token = $request->user()->currentAccessToken()->delete();

       return ResponseFormatter::success($token, 'Token Revoked');
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success([
            $request->user(),'Data profile berhasl di ambil'
        ]);
    }

    public function updateProfile(Request $request){
        $data = $request->all();
        $user = Auth::user();
        $user->update($user);
    }

    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'reuired|image|max:2084'
        ]);

        if($validator->fails())
        {
            return ResponseFormatter::error([
                'error' => $validator->errors()
                ],
                'update photo fails', 401
            );
        }

        if($request->file('file')){
            $file = $request->file->store('assets/user', 'public');

            $user = Auth::user();
            $user->profile_photo_path = $file;
            $user->update();

            return ResponseFormatter::success([$file], 'File Success di Upload');
        }
    }

}
