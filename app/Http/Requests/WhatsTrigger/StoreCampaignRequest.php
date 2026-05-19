<?php

namespace App\Http\Requests\WhatsTrigger;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'message' => 'required|string|max:4096',
            'target_tags' => 'nullable|array',
            'target_tags.*' => 'string|max:50',
            'scheduled_at' => 'nullable|date|after:now',
        ];
    }
}
