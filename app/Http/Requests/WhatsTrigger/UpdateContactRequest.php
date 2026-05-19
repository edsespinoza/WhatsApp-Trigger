<?php

namespace App\Http\Requests\WhatsTrigger;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'phone' => [
                'sometimes', 'string', 'max:20',
                Rule::unique('contacts')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->route('contact')),
            ],
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'opted_in' => 'boolean',
        ];
    }
}
