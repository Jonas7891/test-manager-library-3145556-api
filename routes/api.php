<?php

use App\Adapters\Inbound\Http\Controllers\AuthController;
use App\Adapters\Inbound\Http\Controllers\BookController;
use App\Adapters\Inbound\Http\Controllers\LoanController;
use App\Adapters\Inbound\Http\Controllers\MemberController;
use App\Adapters\Inbound\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// api-contract.md §3 — prefijo /api/v1 (bootstrap/app.php)

Route::get('/health', [AuthController::class, 'health']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::post('/users', [AuthController::class, 'createUser'])->middleware('role:admin');

    Route::get('/categories', [BookController::class, 'categories']);

    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{id}', [BookController::class, 'show']);
    Route::get('/books/{id}/cover', [BookController::class, 'cover']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{id}', [BookController::class, 'update']);
        Route::delete('/books/{id}', [BookController::class, 'destroy']);
        // PHP no lee multipart en PUT real: el cliente envía POST con _method=PUT (H-17)
        Route::put('/books/{id}/cover', [BookController::class, 'uploadCover']);
        Route::delete('/books/{id}/cover', [BookController::class, 'removeCover']);
    });

    Route::middleware('role:admin,librarian')->group(function () {
        Route::get('/members', [MemberController::class, 'index']);
        Route::get('/members/{id}', [MemberController::class, 'show']);
        Route::post('/members', [MemberController::class, 'store']);

        // H-04: overdue antes de {id}
        Route::get('/loans/overdue', [LoanController::class, 'overdue']);
        Route::post('/loans', [LoanController::class, 'store']);
        Route::get('/loans', [LoanController::class, 'index']);
        Route::get('/loans/{id}', [LoanController::class, 'show']);
        Route::post('/loans/{loanId}/items/{itemId}/return', [LoanController::class, 'returnItem']);
    });

    Route::get('/reports/most-borrowed', [ReportController::class, 'mostBorrowed'])->middleware('role:admin');
});
