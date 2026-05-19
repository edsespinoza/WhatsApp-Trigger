<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class EvolutionApiService
{
    private string $baseUrl;

    private string $apiKey;

    private string $instanceId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('whatstrigger.evolution.url'), '/');
        $this->apiKey = config('whatstrigger.evolution.key');
        $this->instanceId = config('whatstrigger.evolution.instance_id');
    }

    public function sendText(string $phone, string $text): array
    {
        return $this->post("/message/sendText/{$this->instanceId}", [
            'number' => $this->normalizePhone($phone),
            'text' => $text,
        ]);
    }

    public function instanceStatus(): array
    {
        return $this->get('/instance/fetchInstances');
    }

    public function connectQrCode(): array
    {
        return $this->get("/instance/connect/{$this->instanceId}");
    }

    public function disconnect(): array
    {
        return $this->delete("/instance/logout/{$this->instanceId}");
    }

    // Remove formatação e garante código do país (55 para Brasil)
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        return $digits;
    }

    private function get(string $path): array
    {
        return $this->request('get', $path);
    }

    private function post(string $path, array $body = []): array
    {
        return $this->request('post', $path, $body);
    }

    private function delete(string $path): array
    {
        return $this->request('delete', $path);
    }

    private function request(string $method, string $path, array $body = []): array
    {
        /** @var Response $response */
        $response = Http::withHeaders(['apikey' => $this->apiKey])
            ->timeout(15)
            ->{$method}($this->baseUrl.$path, $body);

        $response->throw();

        return $response->json() ?? [];
    }
}
