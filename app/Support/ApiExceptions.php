<?php

namespace App\Support;

use App\Exceptions\BusinessRuleException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Formato único de los errores de /api/*, sea cual sea la cabecera Accept:
 *
 *   { "message": "texto para el usuario", "code": "estable_para_la_app",
 *     "errors": { "campo": ["..."] } }   ← solo en validation_failed
 *
 * La app decide por `code`, nunca por el texto (que va traducido según
 * Accept-Language). Los códigos están en docs/api/v1.md. Un error no
 * previsto deja el render de Laravel (JSON; sin traza fuera de debug).
 */
class ApiExceptions
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (Throwable $e, Request $request) {
            return $request->is('api/*') ? self::render($e) : null;
        });
    }

    public static function render(Throwable $e): ?JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => self::json(
                __('Los datos enviados no son válidos.'), 'validation_failed', 422, ['errors' => $e->errors()]
            ),
            $e instanceof AuthenticationException => self::json(
                __('No autenticado. Inicia sesión de nuevo.'), 'unauthenticated', 401
            ),
            $e instanceof AuthorizationException, $e instanceof AccessDeniedHttpException => self::json(
                __('No tienes permiso para hacer esto.'), 'forbidden', 403
            ),
            $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => self::json(
                __('Recurso no encontrado.'), 'not_found', 404
            ),
            $e instanceof MethodNotAllowedHttpException => self::json(
                __('Método no permitido.'), 'method_not_allowed', 405, [], $e->getHeaders()
            ),
            $e instanceof ThrottleRequestsException => self::json(
                __('Demasiadas peticiones. Espera un momento.'), 'too_many_requests', 429, [], $e->getHeaders()
            ),
            $e instanceof BusinessRuleException => self::json($e->getMessage(), 'business_rule', 422),
            $e instanceof HttpExceptionInterface => self::json(
                $e->getMessage() ?: __('Error en la petición.'), 'http_error', $e->getStatusCode(), [], $e->getHeaders()
            ),
            default => null,
        };
    }

    protected static function json(string $message, string $code, int $status, array $extra = [], array $headers = []): JsonResponse
    {
        // El error sale del pipeline antes que SetApiLocale pueda poner la cabecera.
        $headers['Content-Language'] = app()->getLocale();

        return response()->json(['message' => $message, 'code' => $code] + $extra, $status, $headers);
    }
}
