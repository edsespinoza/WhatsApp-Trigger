<?php

namespace App\Http\Controllers\WhatsTrigger;

use App\Http\Controllers\Controller;
use App\Services\EvolutionApiService;
use Illuminate\Http\JsonResponse;

class WhatsAppController extends Controller
{
    public function __construct(private readonly EvolutionApiService $evolution) {}

    public function status(): JsonResponse
    {
        $data = $this->evolution->instanceStatus();

        return response()->json($data);
    }

    public function qrcode(): JsonResponse
    {
        $data = $this->evolution->connectQrCode();

        return response()->json($data);
    }

    public function disconnect(): JsonResponse
    {
        $data = $this->evolution->disconnect();

        return response()->json($data);
    }
}
