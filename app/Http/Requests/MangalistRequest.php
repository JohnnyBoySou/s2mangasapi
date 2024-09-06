<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MangalistRequest extends FormRequest
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
        $collectionId = $this->route('mangalist');
        $isUpdate = !is_null($collectionId);
        return [
            'name' => $isUpdate ? 'nullable' : 'required',
            'capa' => $isUpdate ? 'nullable' : 'required',
            'descricao' => $isUpdate ? 'nullable' : 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório',
            'capa.required' => 'A capa é obrigatória',
            'descricao.required' => 'A descrição é obrigatória',
            //'user_id.required' => 'O usuário é obrigatório',
        ];
    }
}
