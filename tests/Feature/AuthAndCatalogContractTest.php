<?php

namespace Tests\Feature;

/** T-16 (parte 1): HU-01 a HU-05 contra la API real. */
final class AuthAndCatalogContractTest extends ApiTestCase
{
    public function test_health_is_public_t_01(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertExactJson(['status' => 'ok']);
        $this->getJson('/health')->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_login_hu_01(): void
    {
        $ok = $this->postJson('/api/v1/auth/login', ['username' => 'admin', 'password' => 'admin-pass'])->assertOk();
        $ok->assertJsonPath('token_type', 'Bearer')->assertJsonPath('expires_in', 28800)->assertJsonPath('role', 'admin');

        $badUser = $this->postJson('/api/v1/auth/login', ['username' => 'nadie', 'password' => 'admin-pass']);
        $badPass = $this->postJson('/api/v1/auth/login', ['username' => 'admin', 'password' => 'mala']);
        $this->assertErrorBody($badUser, 401, 'INVALID_CREDENTIALS');
        $this->assertSame($badUser->json('message'), $badPass->json('message'), 'CA-01.2');
    }

    public function test_token_and_role_are_enforced_ca_01_3(): void
    {
        $this->assertErrorBody($this->getJson('/api/v1/books'), 401, 'INVALID_CREDENTIALS');
        $this->assertErrorBody($this->getJson('/api/v1/books', ['Authorization' => 'Bearer basura']), 401, 'INVALID_CREDENTIALS');
        $lib = $this->librarian();
        $this->assertErrorBody($this->getJson('/api/v1/reports/most-borrowed?from=2026-10-01&to=2026-10-31', $lib), 403, 'FORBIDDEN_ROLE');
        $this->assertErrorBody($this->postJson('/api/v1/books', [], $lib), 403, 'FORBIDDEN_ROLE');
    }

    public function test_create_librarian_hu_02(): void
    {
        $admin = $this->token();
        $res = $this->postJson('/api/v1/users', ['username' => ' Ana ', 'password' => 'x', 'role' => 'librarian'], $admin)->assertCreated();
        $res->assertJsonPath('username', 'ana')->assertJsonMissingPath('password')->assertJsonMissingPath('password_hash');

        $this->assertErrorBody($this->postJson('/api/v1/users', ['username' => 'ANA', 'password' => 'y', 'role' => 'librarian'], $admin), 409, 'USERNAME_TAKEN');
        $this->assertErrorBody($this->postJson('/api/v1/users', ['username' => 'otro', 'password' => 'y', 'role' => 'admin'], $admin), 422, 'VALIDATION_ERROR');
    }

    public function test_books_crud_and_search_hu_03_hu_04(): void
    {
        $admin = $this->token();
        $id = $this->book($admin);
        $this->getJson("/api/v1/books/{$id}", $admin)->assertOk()
            ->assertJsonPath('available_copies', 2)->assertJsonPath('category.name', 'Novela')->assertJsonPath('cover_key', null);

        $this->assertErrorBody($this->postJson('/api/v1/books', [
            'title' => 'X', 'author' => 'Y', 'isbn' => '9780307474728', 'category_id' => self::NOVELA, 'total_copies' => 1,
        ], $admin), 409, 'ISBN_TAKEN');
        $this->assertErrorBody($this->postJson('/api/v1/books', [
            'title' => 'X', 'author' => 'Y', 'isbn' => '123', 'category_id' => self::NOVELA, 'total_copies' => 1,
        ], $admin), 422, 'VALIDATION_ERROR');

        $this->putJson("/api/v1/books/{$id}", ['title' => 'Nuevo', 'author' => 'A', 'category_id' => self::CIENCIA, 'total_copies' => 5], $admin)
            ->assertOk()->assertJsonPath('available_copies', 5);

        $this->book($admin, '9780000000002', 1, 'Otro libro');
        $this->getJson('/api/v1/books?q=nuev&size=10', $admin)->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('items.0.title', 'Nuevo')->assertJsonStructure(['items', 'page', 'size', 'total']);
        $this->assertErrorBody($this->getJson('/api/v1/books?size=51', $admin), 422, 'VALIDATION_ERROR');

        $this->deleteJson("/api/v1/books/{$id}", [], $admin)->assertNoContent();
        $this->assertErrorBody($this->getJson("/api/v1/books/{$id}", $admin), 404, 'NOT_FOUND');
        $this->getJson('/api/v1/categories', $admin)->assertOk()->assertJsonCount(5);
    }

    public function test_cover_upload_validates_type_and_size_ca_03_4(): void
    {
        $admin = $this->token();
        $id = $this->book($admin);
        $png = "\x89PNG\r\n\x1a\n".str_repeat('a', 100);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('portada.png', $png);
        $this->call('POST', "/api/v1/books/{$id}/cover", ['_method' => 'PUT'], [], ['file' => $file], $this->transformHeadersToServerVars($admin))
            ->assertOk()->assertJsonPath('id', $id);
        $this->get("/api/v1/books/{$id}/cover", $admin)->assertOk()->assertHeader('Content-Type', 'image/png');

        $fakeJpg = \Illuminate\Http\UploadedFile::fake()->createWithContent('falsa.jpg', 'GIF89a no es jpg');
        $res = $this->call('POST', "/api/v1/books/{$id}/cover", ['_method' => 'PUT'], [], ['file' => $fakeJpg], $this->transformHeadersToServerVars($admin));
        $this->assertErrorBody($res, 422, 'VALIDATION_ERROR');

        $twoMb = \Illuminate\Http\UploadedFile::fake()->createWithContent('justa.png', "\x89PNG\r\n\x1a\n".str_repeat('a', 2 * 1024 * 1024 - 8));
        $this->call('POST', "/api/v1/books/{$id}/cover", ['_method' => 'PUT'], [], ['file' => $twoMb], $this->transformHeadersToServerVars($admin))->assertOk();
        $threeMb = \Illuminate\Http\UploadedFile::fake()->createWithContent('grande.png', "\x89PNG\r\n\x1a\n".str_repeat('a', 3 * 1024 * 1024));
        $res = $this->call('POST', "/api/v1/books/{$id}/cover", ['_method' => 'PUT'], [], ['file' => $threeMb], $this->transformHeadersToServerVars($admin));
        $this->assertErrorBody($res, 422, 'VALIDATION_ERROR');

        $this->deleteJson("/api/v1/books/{$id}/cover", [], $admin)->assertNoContent();
        $this->getJson("/api/v1/books/{$id}", $admin)->assertJsonPath('cover_key', null);
    }

    public function test_members_hu_05(): void
    {
        $lib = $this->librarian();
        $id = $this->member($lib, '555');
        $this->assertErrorBody($this->postJson('/api/v1/members', ['full_name' => 'Otro', 'document_number' => '555'], $lib), 409, 'DOCUMENT_TAKEN');
        $this->getJson('/api/v1/members?q=555', $lib)->assertOk()->assertJsonPath('total', 1);
        $this->getJson("/api/v1/members/{$id}", $lib)->assertOk()->assertJsonPath('active_loans', 0);
        $this->deleteJson("/api/v1/members/{$id}", [], $lib)->assertNotFound();
    }
}
