<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'erros' => $validator->errors()
        ], 422));
    }


    public function rules(): array
    {
        $userID = Auth::id();
    
        // Verifica se estamos criando ou editando
        $isUpdate = isset($userID);
    
        return [
            'name' => $isUpdate ? 'nullable' : 'required',
            'username' => $isUpdate ? 'nullable' : 'required|unique:users,username,' . ($isUpdate ? $userID : null),
            'email' => $isUpdate ? 'nullable|email' : 'required|email|unique:users,email,' . ($isUpdate ? $userID : null),
            'password' => $isUpdate ? 'nullable|min:8' : 'required|min:8',  // Senha obrigatória apenas na criação
            'avatar' => $isUpdate ? 'nullable' : 'required',  // Avatar não obrigatório na edição
            'capa' => $isUpdate ? 'nullable' : 'required',    // Capa não obrigatória na edição
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório',
            'username.required' => 'O nome de usuário é obrigatório',
            'email.required' => 'O e-mail é obrigatório',
            'email.email' => 'O e-mail deve ser válido',
            'email.unique' => 'O e-mail informado já existe',
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter pelo menos :min caracteres',
            'avatar.required' => 'O avatar é obrigatória',
            'capa.required' => 'A capa é obrigatória',
        ];
    }
}
