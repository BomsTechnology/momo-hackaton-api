<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|min:8|confirmed'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'phone' => str_replace(' ', '', $fields['phone']),
            'password' => Hash::make($fields['password']),
        ]);

        $reponse = $this->sendVerificationSms($user->phone);

        if ($reponse->status() == 201) {
            $code = json_decode($reponse->content())->data->code;
            $user->update(['verif_code' => $code]);
            $response = [
                'status' => true,
                'message' => 'Register successful!',
                'data' => [
                    'user' => $user,
                ]
            ];
            return response($response, 201);
        } else {
            return $reponse;
        }
    }


    public function login(Request $request)
    {
        $fields = $request->validate([
            'phone' => 'required|string',
            'password' => 'required'
        ]);
        //check email
        $user = User::where('phone', str_replace(' ', '', $fields['phone']))->first();

        //check password
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response(['status' => false, 'message' => 'invalid phone or password'], 401);
        }

        //verify activation phone
        if (!$user->phone_verified_at) {
            return response(['status' => false, 'message' => 'Your phone address is not verified.'], 403);
        }

        //create token
        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'status' => true,
            'message' => 'Login successful!',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ];
        return response($response, 201);
    }


    public function sendVerificationSms(string $phone)
    {
        $code = random_int(100000, 999999);
        $basic  = new \Vonage\Client\Credentials\Basic(env('VONAGE_KEY'), env('VONAGE_SECRET'));
        $client = new \Vonage\Client($basic);
        $response = $client->sms()->send(
            new \Vonage\SMS\Message\SMS($phone, env('VONAGE_SMS_FROM'), "Votre code de vérification est: $code \n valide 1h")
        );

        $message = $response->current();

        if ($message->getStatus() == 0) {
            $response = [
                'status' => true,
                'message' => 'Sms verification Send successfully!',
                'data' => [
                    'code' => $code,
                ]
            ];
            return response($response, 201);
        } else {
            return response(['status' => false, 'message' => 'Send Sms error' . $message->getStatus()], 401);
        }
    }

    public function verifyCode(Request $request, User $user)
    {
        $fields = $request->validate([
            'code' => 'required|string',
        ]);
        if ($fields['code'] == $user->verif_code) {

            $user->update([
                'phone_verified_at' => Carbon::now()->toDateTimeString()
            ]);

            $token = $user->createToken('myapptoken')->plainTextToken;

            $response = [
                'status' => true,
                'message' => 'Phone verify successfully!',
                'data' => [
                    'user' =>  $user,
                    'token' => $token
                ]
            ];
            return response($response, 201);
        } else {
            return response(['status' => false, 'message' => 'Incorrect verification code'], 401);
        }
    }

    public function resetPassword(Request $request)
    {
        //
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        $response = [
            'status' => true,
            'message' => 'Logout successfully',
        ];
        return response($response, 201);
    }
}
