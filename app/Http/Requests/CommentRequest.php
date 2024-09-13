<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function authorize()
    {
        // Permitir acesso a todos os usuários autenticados
        return true;
    }

    public function rules()
    {
        $rules = [
            'manga_id' => 'required|uuid',
            'message' => 'required|string|max:200',
            'parent_id' => 'nullable|uuid|exists:comments,id',
        ];

        // Verificar se estamos atualizando um comentário
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['message'] = 'required|string|max:200'; // Regra específica para atualização, se necessário
        }

        return $rules;
    }
}
