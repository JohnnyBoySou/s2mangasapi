<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WallpaperRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true; // Alterar se necessário, baseado em permissões.
    }

    /**
     * Define as regras de validação.
     */
    public function rules(): array
    {
        // Validações específicas dependendo do método HTTP
        if ($this->isMethod('post')) {
            return [
                'name' => 'required|string|max:255',
                'capa' => 'required|url|max:2083',
                'data' => 'required|array',
                'data.*.img' => 'required|url', // Cada item deve ter um campo `img` com URL válida
                'user_id' => 'sometimes|exists:users,id', // Valida se user_id existe na tabela users
            ];
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'name' => 'sometimes|string|max:255',
                'capa' => 'sometimes|url|max:2083',
                'data' => 'sometimes|array',
                'data.*.img' => 'required_with:data|url', // Valida apenas se o campo `data` for enviado
                'user_id' => 'sometimes|exists:users,id', // Valida se user_id existe na tabela users
            ];
        }

        if ($this->isMethod('delete')) {
            return [
                'img' => 'required|url', // A URL da imagem a ser removida
            ];
        }

        return [];
    }

    /**
     * Mensagens de erro personalizadas.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.string' => 'O campo nome deve ser um texto válido.',
            'name.max' => 'O campo nome não pode exceder 255 caracteres.',
            'capa.required' => 'O campo capa é obrigatório.',
            'capa.url' => 'O campo capa deve conter uma URL válida.',
            'capa.max' => 'A URL da capa não pode exceder 2083 caracteres.',
            'data.required' => 'O campo data é obrigatório.',
            'data.array' => 'O campo data deve ser um array.',
            'data.*.img.required' => 'Cada item do campo data deve conter uma imagem.',
            'data.*.img.url' => 'Cada imagem no campo data deve ser uma URL válida.',
            'img.required' => 'O campo img é obrigatório para exclusão.',
            'img.url' => 'O campo img deve conter uma URL válida.',
            'user_id.exists' => 'O campo user_id deve conter um ID de usuário válido.',
        ];
    }
}
