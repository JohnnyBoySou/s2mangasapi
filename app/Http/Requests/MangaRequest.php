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
    // Obtém todos os erros e os transforma em uma string separada por vírgulas
    $errors = implode(', ', $validator->errors()->all());

    throw new HttpResponseException(response()->json([
        'status' => false,
        'message' => $errors,
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
            'description' => $isUpdate ? 'nullable|string ':'required|array',  // "description" é obrigatória
            'capa' => $isUpdate ? 'nullable|string' : 'required|string',  // "capa" é opcional no update
            'categories' => $isUpdate ? 'nullable|string ':'required|array',  // "categories" é obrigatória
            'languages' => $isUpdate ? 'nullable|string ':'required|array',  // "languages" é obrigatória
            'release_date' => $isUpdate ? 'nullable|string ':'required|date',  // "release_date" é obrigatória
            'status' => $isUpdate ? 'nullable|string ':'required|string',  // "status" é obrigatório
            'type' => $isUpdate ? 'nullable|string ':'required|string|in:Mangá,Light Novel,Manhwa',  // "type" deve ser um desses valores
            'year' => $isUpdate ? 'nullable|string ': 'nullable|integer',  // "year" é obrigatório e deve ser um número
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.required' => 'O campo UUID é obrigatório.',
            'uuid.uuid' => 'O campo UUID deve ser um UUID válido.',
            'uuid.unique' => 'O UUID informado já está em uso.',
            'name.required' => 'O campo nome é obrigatório.',
            'name.string' => 'O campo nome deve ser uma string.',
            'name.max' => 'O campo nome não pode ter mais que 255 caracteres.',
            'description.required' => 'O campo descrição é obrigatório.',
            'description.array' => 'O campo descrição deve ser uma string.',
            'capa.required' => 'O campo capa é obrigatório.',
            'capa.string' => 'O campo capa deve ser uma string.',
            'categories.required' => 'O campo categorias é obrigatório.',
            'categories.array' => 'O campo categorias deve ser um array.',
            'languages.required' => 'O campo idiomas é obrigatório.',
            'languages.array' => 'O campo idiomas deve ser um array.',
            'release_date.required' => 'O campo data de lançamento é obrigatório.',
            'release_date.date' => 'O campo data de lançamento deve ser uma data válida.',
            'status.required' => 'O campo status é obrigatório.',
            'status.string' => 'O campo status deve ser uma string.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.string' => 'O campo tipo deve ser uma string.',
            'type.in' => 'O campo tipo deve ser um dos seguintes valores: Mangá, Light Novel, Manhwa.',
            'year.integer' => 'O campo ano deve ser um número inteiro.',
        ];
    }
}
