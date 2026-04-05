<?php

namespace App\Http\Requests\FormRequest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:10|max:100000',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be at least 10.',
            'amount.max' => 'Amount must be at most 100000.',
        ];
    }
}
