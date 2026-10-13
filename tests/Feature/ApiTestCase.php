<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/** Base de las pruebas de contrato: API real contra PostgreSQL real (plan §3). */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected const NOVELA = '11111111-1111-4111-8111-111111111111';
    protected const CIENCIA = '22222222-2222-4222-8222-222222222222';

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('library:bootstrap-admin');
    }

    protected function token(string $username = 'admin', string $password = 'admin-pass'): array
    {
        $token = $this->postJson('/api/v1/auth/login', compact('username', 'password'))->assertOk()->json('access_token');

        return ['Authorization' => "Bearer {$token}"];
    }

    protected function librarian(): array
    {
        $this->postJson('/api/v1/users', ['username' => 'Ana ', 'password' => 'clave-ana', 'role' => 'librarian'], $this->token())->assertCreated();

        return $this->token('ana', 'clave-ana');
    }

    protected function book(array $h, string $isbn = '9780307474728', int $copies = 2, string $title = 'Cien años de soledad'): string
    {
        return $this->postJson('/api/v1/books', [
            'title' => $title, 'author' => 'Gabriel García Márquez', 'isbn' => $isbn,
            'category_id' => self::NOVELA, 'total_copies' => $copies,
        ], $h)->assertCreated()->json('id');
    }

    protected function member(array $h, string $doc = '1001'): string
    {
        return $this->postJson('/api/v1/members', ['full_name' => 'Socio '.$doc, 'document_number' => $doc], $h)->assertCreated()->json('id');
    }

    protected function assertErrorBody($response, int $status, string $code): void
    {
        $response->assertStatus($status)->assertJsonStructure(['code', 'message', 'details'])->assertJsonPath('code', $code);
        $this->assertStringNotContainsString('password', $response->getContent());
    }
}
