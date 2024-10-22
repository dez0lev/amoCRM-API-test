<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataStoreRequest extends FormRequest
{
    use FailedValidationTrait;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
//            'name' => 'required|string',
            'phone' => 'required|min:9',
//            'city' => 'required|string',
//            'date' => 'required|date',
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => 'Номер телефона обязателен.',
            'phone.min' => 'Номер телефона должен быть в правильном формате.',
        ];
    }
}
