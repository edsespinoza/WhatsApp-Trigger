<?php

namespace Tests\Unit;

use App\Services\EvolutionApiService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EvolutionApiServiceTest extends TestCase
{
    private EvolutionApiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('whatstrigger.evolution.url', 'https://evo.test');
        Config::set('whatstrigger.evolution.key', 'test-key');
        Config::set('whatstrigger.evolution.instance_id', 'instance-1');

        $this->service = new EvolutionApiService;
    }

    public function test_send_text_sends_to_correct_endpoint(): void
    {
        Http::fake();

        $this->service->sendText('11999999999', 'Hello');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://evo.test/message/sendText/instance-1'
                && $request->method() === 'POST'
                && $request->data() === [
                    'number' => '5511999999999',
                    'text' => 'Hello',
                ];
        });
    }

    public function test_send_text_normalizes_phone_with_55_prefix(): void
    {
        Http::fake();

        $this->service->sendText('11999999999', 'Hello');

        Http::assertSent(function ($request) {
            return $request['number'] === '5511999999999';
        });
    }

    public function test_send_text_preserves_existing_55_prefix(): void
    {
        Http::fake();

        $this->service->sendText('5511999999999', 'Hello');

        Http::assertSent(function ($request) {
            return $request['number'] === '5511999999999';
        });
    }

    public function test_send_text_sends_api_key_header(): void
    {
        Http::fake();

        $this->service->sendText('11999999999', 'Hello');

        Http::assertSent(function ($request) {
            return $request->hasHeader('apikey')
                && $request->header('apikey')[0] === 'test-key';
        });
    }

    public function test_instance_status_correct_endpoint(): void
    {
        Http::fake();

        $this->service->instanceStatus();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://evo.test/instance/fetchInstances'
                && $request->method() === 'GET';
        });
    }

    public function test_connect_qr_code_correct_endpoint(): void
    {
        Http::fake();

        $this->service->connectQrCode();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://evo.test/instance/connect/instance-1'
                && $request->method() === 'GET';
        });
    }

    public function test_disconnect_correct_endpoint(): void
    {
        Http::fake();

        $this->service->disconnect();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://evo.test/instance/logout/instance-1'
                && $request->method() === 'DELETE';
        });
    }

    public function test_send_text_returns_response_data(): void
    {
        Http::fake([
            'https://evo.test/*' => Http::response(['key' => '123', 'status' => 'sent']),
        ]);

        $result = $this->service->sendText('11999999999', 'Hello');

        $this->assertEquals(['key' => '123', 'status' => 'sent'], $result);
    }

    public function test_throws_exception_on_http_error(): void
    {
        Http::fake([
            'https://evo.test/*' => Http::response(null, 500),
        ]);

        $this->expectException(RequestException::class);

        $this->service->sendText('11999999999', 'Hello');
    }

    public function test_returns_empty_array_when_response_is_null(): void
    {
        Http::fake([
            'https://evo.test/*' => Http::response(null, 200),
        ]);

        $result = $this->service->sendText('11999999999', 'Hello');

        $this->assertEquals([], $result);
    }
}
