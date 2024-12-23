<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class MangaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autoriza todas as requisições, altere se necessário
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        // Personaliza o retorno de erro em caso de falha na validação
        throw new HttpResponseException(response()->json([
            'status' => false,
            'erros' => $validator->errors()
        ], 422));
    }

    public function rules(): array
    {
        // Verifica se estamos atualizando ou criando (baseado na presença de um ID de collection)
        $collectionId = $this->route('collection');
        $isUpdate = !is_null($collectionId);

        return [
            'uuid' => $isUpdate ? 'nullable|uuid|unique:mangas,uuid,' . $this->route('manga') : 'required|uuid|unique:mangas,uuid', // UUID único, mas permite update
            'name' => $isUpdate ? 'nullable|string|max:255' : 'required|string|max:255',  // "name" pode ser opcional no update
            'description' => $isUpdate ? 'nullable|string ':'required|string',  // "description" é obrigatória
            'capa' => $isUpdate ? 'nullable|string' : 'required|string',  // "capa" é opcional no update
            'categories' => $isUpdate ? 'nullable|string ':'required|array',  // "categories" é obrigatória
            'languages' => $isUpdate ? 'nullable|string ':'required|array',  // "languages" é obrigatória
            'release_date' => $isUpdate ? 'nullable|string ':'required|date',  // "release_date" é obrigatória
            'status' => $isUpdate ? 'nullable|string ':'required|string',  // "status" é obrigatório
            'type' => $isUpdate ? 'nullable|string ':'required|string|in:Mangá,Light Novel,Manhwa',  // "type" deve ser um desses valores
            'year' => $isUpdate ? 'nullable|string ': 'required|integer',  // "year" é obrigatório e deve ser um número
        ];
    }
}
