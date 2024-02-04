<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'prohibited',
            'last_name' => 'prohibited',
            'email' => 'prohibited',
            'password' => 'prohibited',
            'nick_name' => 'string|max:25|unique:users',
            'word' => 'string',
            'game.score' => 'prohibited',
            'game.words' => 'prohibited',
            'game.attempts_remaining' => 'prohibited',
            'personal_best' => 'prohibited',
        ];
    }
}
