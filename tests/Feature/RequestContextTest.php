<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class RequestContextTest extends TestCase
{
    public function test_resposta_possui_request_id(): void
    {
        $response = $this->getJson('/');

        $response->assertHeader('X-Request-ID');

        $requestId = $response->headers->get('X-Request-ID');

        $this->assertTrue(Str::isUuid($requestId));
    }

    public function test_requisicoes_recebem_ids_diferentes(): void
    {
        $response1 = $this->getJson('/');
        $response2 = $this->getJson('/');

        $id1 = $response1->headers->get('X-Request-ID');
        $id2 = $response2->headers->get('X-Request-ID');

        $this->assertNotEmpty($id1);
        $this->assertNotEmpty($id2);
        $this->assertNotEquals($id1, $id2);
    }

    public function test_registra_log_da_requisicao(): void
{
    Log::spy();

    $response = $this->getJson('/');

    $requestId = $response->headers->get('X-Request-ID');

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(function ($message, $context) use ($requestId) {
            return $message === 'http.request.completed'
                && $context['request_id'] === $requestId
                && $context['method'] === 'GET'
                && $context['status'] === 200
                && is_numeric($context['duration_ms'])
                && $context['duration_ms'] >= 0
                && isset($context['route']);
        });
}
}