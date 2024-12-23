<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeedRequest extends FormRequest
{
    /**
     * Determine se o usuário está autorizado a fazer esta requisição.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Altere para sua lógica de autorização, se necessário
    }

    /**
     * Obtenha as regras de validação que devem ser aplicadas à requisição.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mangalist_id' => 'required|exists:mangalists,id', // Verifica se o ID do mangalist existe na tabela mangalists
            'title' => 'required|string|max:255', // Valida que o título é uma string e não excede 255 caracteres
            'manga_ids' => 'required|array', // Valida que manga_ids é um array
            'manga_ids.*' => 'exists:manga_items,id', // Verifica se cada ID de manga dentro de manga_ids existe na tabela manga_items
        ];
    }

    /**
     * Obtenha as mensagens de erro personalizadas para a validação.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'mangalist_id.required' => 'O mangalist é obrigatório.',
            'mangalist_id.exists' => 'O mangalist especificado não existe.',
            'title.required' => 'O título é obrigatório.',
            'manga_ids.required' => 'Os IDs dos mangas são obrigatórios.',
            'manga_ids.array' => 'Os IDs dos mangas devem ser fornecidos como um array.',
            'manga_ids.*.exists' => 'Um ou mais mangas especificados não existem.',
        ];
    }
}
