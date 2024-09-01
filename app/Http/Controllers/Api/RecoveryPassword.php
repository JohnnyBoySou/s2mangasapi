<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgetPasswordRequest;
use App\Mail\SendEmailForgetPasswordCode;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RecoveryPassword extends Controller
{
    public function forgetPasswordCode(ForgetPasswordRequest $request): JsonResponse
    {
        $user = User::where("email", $request->email)->first();
        if (!$user) {
            Log::warning("Tentativa de recuperação de senha com email inexistente",
            ["email"=> $request->email]);
            return response()->json([
                'status' => false,
                'message' => 'Email inexistente'
            ], 400);
        }
        try {
            $userPasswordResets = DB::table('password_reset_tokens')->where([
                'email'=> $request->email,
            ]);

            if ($userPasswordResets) {
                $userPasswordResets->delete();
            }

            $code = mt_rand(100000, 999999);
            $token = Hash::make($code);

            $userNewPasswordResets  = DB::table('password_reset_tokens')->insert([
                'email'=> $request->email,
                'token'=> $token,
                'crated_at'=> Carbon::now(),
            ]);

            if($userNewPasswordResets) {
                $currentDate = Carbon::now();
                $oneHourLater = $currentDate->addHour();
                $formattedTime = $oneHourLater->format('H:i');
                $formattedDate = $oneHourLater->format('d/m/Y');


                Mail::to($user->email)->send(new SendEmailForgetPasswordCode());
            }

        } catch (Exception $e) {
        }
    
    
        }
    public function forgetPasswordValidate()
    {

    }
    public function resetPassword()
    {

    }

}
