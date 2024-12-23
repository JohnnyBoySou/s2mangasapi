<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RemoveMangaRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta solicitação.
     */
    public function authorize(): bool
    {
        return true; // Ajuste conforme sua lógica de autenticação/autorizações
    }

    /**
     * Manipula falhas de validação.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'errors' => $validator->errors(),
        ], 422));
    }

    /**
     * Regras de validação.
     */
    public function rules(): array
    {
        return [
            'manga_id' => 'required|integer|exists:mangas,id',
        ];
    }

    /**
     * Mensagens personalizadas para as regras de validação.
     */
    public function messages(): array
    {
        return [
            'manga_id.required' => 'O ID do mangá é obrigatório.',
            'manga_id.integer' => 'O ID do mangá deve ser um número inteiro.',
            'manga_id.exists' => 'O ID do mangá informado não existe.',
        ];
    }
}
