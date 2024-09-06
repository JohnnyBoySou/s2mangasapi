<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CollectionRequest extends FormRequest
{
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
        $collectionId = $this->route('collections');
        $isUpdate = !is_null($collectionId);
        return [
            'name' => $isUpdate ? 'nullable' : 'required',
            'capa' => $isUpdate ? 'nullable' : 'required',
            //'user_id' => $isUpdate ? 'nullable' : 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório',
            'capa.required' => 'A capa é obrigatória',
            //'user_id.required' => 'O usuário é obrigatório',
        ];
    }
}
