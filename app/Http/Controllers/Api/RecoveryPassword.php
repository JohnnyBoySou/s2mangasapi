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
use Illuminate\Support\Facades\Auth;
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

            $code = mt_rand(1000, 9999);
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
            Log::info('Código válido.', ['email' => $request->email]);

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
    public function resetPassword(ResetPasswordCodeRequest $request, ResetPasswordValidateCodeService $resetPasswordValidateCode): JsonResponse
    {
        try {
            // Validar o código de redefinição
            $validationResult = $resetPasswordValidateCode->resetPasswordValidateCode($request->email, $request->code);
            if (!$validationResult['status']) {
                return response()->json([
                    'status' => false,
                    'message' => $validationResult['message'],
                ], 400);
            }

            // Recuperar os dados do usuário
            $user = User::where('email', $request->email)->first();

            // Verificar se o usuário existe no banco de dados
            if (!$user) {
                Log::notice('Usuário não encontrado.', ['email' => $request->email]);

                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não encontrado!',
                ], 400);
            }

            // Atualizar a senha do usuário (Laravel aplicará o bcrypt automaticamente)
            $user->password = $request->password;
            $updateStatus = $user->save();

            // Verificar se a senha foi atualizada
            if (!$updateStatus) {
                Log::error('Falha ao atualizar a senha no banco de dados.', ['email' => $request->email]);

                return response()->json([
                    'status' => false,
                    'message' => 'Falha ao atualizar a senha!',
                ], 500);
            }

            // Gerar o token de autenticação
            $token = $user->createToken('auth_token')->plainTextToken;

            // Verificar e remover tokens de redefinição de senha
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            // Salvar log de sucesso
            Log::info('Senha atualizada com sucesso.', ['email' => $request->email]);

            return response()->json([
                'status' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'Senha atualizada com sucesso!',
            ], 200);

        } catch (Exception $e) {
            Log::warning('Erro ao atualizar a senha.', ['email' => $request->email, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Erro ao atualizar a senha!',
            ], 400);
        }
    }


}
