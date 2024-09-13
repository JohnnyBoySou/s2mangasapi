<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Requests\ResetPasswordCodeRequest;
use App\Http\Requests\ResetPasswordValidateCodeRequest;
use App\Mail\SendEmailForgetPasswordCode;
use App\Models\User;
use App\Service\ResetPasswordValidateCodeService;
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
            Log::warning(
                "Tentativa de recuperação de senha com email inexistente",
                ["email" => $request->email]
            );
            return response()->json([
                'status' => false,
                'message' => 'Email inexistente'
            ], 400);
        }
        try {
            $userPasswordResets = DB::table('password_reset_tokens')->where([
                'email' => $request->email,
            ]);

            if ($userPasswordResets) {
                $userPasswordResets->delete();
            }

            $code = mt_rand(100000, 999999);
            $token = Hash::make($code);

            $userNewPasswordResets = DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]);

            if ($userNewPasswordResets) {
                $currentDate = Carbon::now();
                $oneHourLater = $currentDate->addHour();
                $formattedTime = $oneHourLater->format('H:i');
                $formattedDate = $oneHourLater->format('d/m/Y');


                Mail::to($user->email)->send(new SendEmailForgetPasswordCode($user, $code, $formattedDate, $formattedTime));
            }
            Log::info('Recuperar senha.', ['email' => $request->email]);

            return response()->json([
                'status' => true,
                'message' => 'Email enviado com sucesso',
            ], 200);
        } catch (Exception $e) {

            Log::warning('Erro ao recuperar senha.', ['email' => $request->email, 'error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Erro ao recuperar a senha.',
                'msg' => $e->getMessage()
            ], 400);
        }


    }
   
    public function forgetPasswordValidate(resetPasswordValidateCodeRequest $request, ResetPasswordValidateCodeService $resetPasswordValidateCode): JsonResponse
    {
        try {

            // Validar o código do token
            $validationResult = $resetPasswordValidateCode->resetPasswordValidateCode($request->email, $request->code);

            // Verificar o resultado da validação
            if (!$validationResult['status']) {

                // Exibir mensagem de erro
                return response()->json([
                    'status' => false,
                    'message' => $validationResult['message'],
                ], 400);

            }

            // Recuperar os dados do usuário
            $user = User::where('email', $request->email)->first();

            // Verificar existe o usuário no banco de dados
            if (!$user) {

                // Salvar log
                Log::notice('Usuário não encontrado.', ['email' => $request->email]);

                // Exibir mensagem de erro
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não encontrado!',
                ], 400);

            }

            // Salvar log
            Log::info('Código recuperar senha válido.', ['email' => $request->email]);

            return response()->json([
                'status' => true,
                'message' => 'Código recuperar senha válido!',
            ], 200);

        } catch (Exception $e) {

            // Salvar log
            Log::warning('Erro validar código recuperar senha.', ['email' => $request->email, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Código inválido!',
            ], 400);
        }
    }
    public function resetPassword(ResetPasswordCodeRequest $request, ResetPasswordValidateCodeService $resetPasswordValidateCode) : JsonResponse
    { {

            try {

                // Validar o código do token
                $validationResult = $resetPasswordValidateCode->resetPasswordValidateCode($request->email, $request->code);

                // Verificar o resultado da validação
                if (!$validationResult['status']) {

                    // Exibir mensagem de erro
                    return response()->json([
                        'status' => false,
                        'message' => $validationResult['message'],
                    ], 400);

                }

                // Recuperar os dados do usuário
                $user = User::where('email', $request->email)->first();

                // Verificar existe o usuário no banco de dados
                if (!$user) {

                    // Salvar log
                    Log::notice('Usuário não encontrado.', ['email' => $request->email]);

                    // Exibir mensagem de erro
                    return response()->json([
                        'status' => false,
                        'message' => 'Usuário não encontrado!',
                    ], 400);

                }

                // Alterar a senha do usuário no banco de dados
                $user->update([
                    'password' => Hash::make($request->password)
                ]);

                // gerar o token 
                $token = $user->first()->createToken('api-token')->plainTextToken;

                // Recuperar os registros recuperar senha do usuário
                $userPasswordResets = DB::table('password_reset_tokens')->where('email', $request->email);

                // Se existir token cadastrado para o usuário recuperar senha, excluir o mesmo
                if ($userPasswordResets) {
                    $userPasswordResets->delete();
                }

                // Salvar log
                Log::info('Senha atualizada com sucesso.', ['email' => $request->email]);

                return response()->json([
                    'status' => true,
                    'user' => $user,
                    'token' => $token,
                    'message' => 'Senha atualizada com sucesso!',
                ], 200);
            } catch (Exception $e) {

                // Salvar log
                Log::warning('Senha não atualizada.', ['email' => $request->email, 'error' => $e->getMessage()]);

                return response()->json([
                    'status' => false,
                    'message' => 'Senha não atualizada!',
                ], 400);

            }

        }
    }

}
