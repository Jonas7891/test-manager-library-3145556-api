<?php

namespace App\Adapters\Inbound\Http;

use App\Domain\Exceptions\DomainError;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Único traductor de errores a HTTP (architecture §5, RNF-4):
 * todo error sale como { code, message, details } sin trazas, SQL ni rutas.
 */
final class ErrorRenderer
{
    private const STATUS = [
        'INVALID_CREDENTIALS' => 401,
        'FORBIDDEN_ROLE' => 403,
        'NOT_FOUND' => 404,
        'USERNAME_TAKEN' => 409,
        'ISBN_TAKEN' => 409,
        'DOCUMENT_TAKEN' => 409,
        'BOOK_HAS_OPEN_LOANS' => 409,
        'BOOK_NOT_AVAILABLE' => 409,
        'MEMBER_HAS_OVERDUE' => 409,
        'ITEM_ALREADY_RETURNED' => 409,
        'MEMBER_LIMIT_EXCEEDED' => 422,
        'DUPLICATE_BOOK_IN_LOAN' => 422,
        'VALIDATION_ERROR' => 422,
        'INVALID_DATE_RANGE' => 422,
    ];

    public static function render(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof DomainError => self::body(self::STATUS[$e->errorCode] ?? 422, $e->errorCode, $e->getMessage(), $e->details),
            $e instanceof AccessDenied => self::body($e->status, $e->errorCode, $e->getMessage()),
            $e instanceof ValidationException => self::body(422, 'VALIDATION_ERROR', 'Hay campos inválidos.', self::validationDetails($e)),
            $e instanceof PostTooLargeException => self::body(422, 'VALIDATION_ERROR', 'El archivo es demasiado grande.', [['field' => 'file', 'issue' => 'mayor de 2 MB']]),
            $e instanceof NotFoundHttpException, $e instanceof MethodNotAllowedHttpException => self::body(404, 'NOT_FOUND', 'El recurso no existe.'),
            $e instanceof QueryException && $e->getCode() === '23514' => self::body(409, 'BOOK_NOT_AVAILABLE', 'No quedan ejemplares disponibles.'),
            $e instanceof HttpExceptionInterface && $e->getStatusCode() < 500 => self::body($e->getStatusCode(), 'VALIDATION_ERROR', 'Petición inválida.'),
            default => self::internal($e),
        };
    }

    private static function internal(Throwable $e): JsonResponse
    {
        report($e);

        return self::body(500, 'INTERNAL_ERROR', 'Ocurrió un error interno. Intenta de nuevo.');
    }

    private static function validationDetails(ValidationException $e): array
    {
        $details = [];
        foreach ($e->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $details[] = ['field' => $field, 'issue' => $message];
            }
        }

        return $details;
    }

    public static function body(int $status, string $code, string $message, array $details = []): JsonResponse
    {
        return new JsonResponse(['code' => $code, 'message' => $message, 'details' => $details], $status, [], JSON_UNESCAPED_UNICODE);
    }
}
