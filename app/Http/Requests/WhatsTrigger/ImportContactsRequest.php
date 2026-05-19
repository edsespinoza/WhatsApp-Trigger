<?php

namespace App\Http\Requests\WhatsTrigger;

use Illuminate\Foundation\Http\FormRequest;

class ImportContactsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'contacts' => 'required|array|min:1|max:500',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.phone' => 'required|string|max:20',
            'contacts.*.tags' => 'nullable|array',
            'contacts.*.tags.*' => 'string|max:50',
            'contacts.*.opted_in' => 'boolean',
        ];
    }
}
