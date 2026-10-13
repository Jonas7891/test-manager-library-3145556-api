<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Presenter;
use App\Application\Services\CreateLibrarianService;
use App\Application\Services\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-01 y HU-02 */
final class AuthController
{
    public function health(): JsonResponse
    {
        return Presenter::json(['status' => 'ok']);
    }

    public function login(Request $request, LoginService $service): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        return Presenter::json($service->login($data['username'], $data['password']));
    }

    public function createUser(Request $request, CreateLibrarianService $service): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string'],
        ]);

        return Presenter::json(Presenter::user($service->create($data['username'], $data['password'], $data['role'])), 201);
    }
}
