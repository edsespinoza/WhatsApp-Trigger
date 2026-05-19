<?php

namespace App\Http\Requests\WhatsTrigger;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => [
                'required', 'string', 'max:20',
                Rule::unique('contacts')->where('user_id', $this->user()->id),
            ],
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'opted_in' => 'boolean',
        ];
    }
}
