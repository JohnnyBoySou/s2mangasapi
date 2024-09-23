<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $rules = [
            'id' => 'required|integer|exists:comments,id',
            'message' => 'required|string|max:200',
        ];


        return $rules;
    }
}
