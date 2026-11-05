<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias 'api.idempotent', en las peticiones de la API que crean algo que
 * mueve dinero (solicitar recarga o retiro). Exige la cabecera
 * Idempotency-Key: la app genera un UUID por envío del formulario y lo
 * repite si reintenta (timeout, doble toque, red caída).
 *
 *  - La primera petición con esa clave se ejecuta y, si sale bien (2xx), su
 *    respuesta se guarda TTL segundos para ese usuario y esa ruta.
 *  - Repetir la clave con el mismo cuerpo devuelve la respuesta guardada
 *    (cabecera Idempotent-Replayed: true) sin volver a ejecutar nada.
 *  - Repetirla con otro cuerpo es un error de la app: 422
 *    idempotency_key_reused.
 *  - Dos peticiones con la misma clave a la vez: la segunda recibe 409
 *    request_in_progress (un lock de caché las serializa).
 *
 * Un error (4xx/5xx) no se guarda: corregir y reenviar con la misma clave
 * vuelve a ejecutarse.
 */
class EnsureIdempotentRequest
{
    public const HEADER = 'Idempotency-Key';

    public const TTL = 86400;

    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header(self::HEADER);

        if (! preg_match('/^[A-Za-z0-9_-]{8,100}$/', $key)) {
            return $this->error(__('Falta la cabecera Idempotency-Key (8 a 100 caracteres: letras, números, - o _).'), 'idempotency_key_required', 400);
        }

        $scope = 'idempotency:'.$request->user()->getAuthIdentifier().':'.$request->route()->getName().':'.$key;
        $fingerprint = $this->fingerprint($request);

        $lock = Cache::lock($scope.':lock', 30);

        if (! $lock->get()) {
            return $this->error(__('Esta solicitud ya se está procesando. Espera unos segundos.'), 'request_in_progress', 409);
        }

        try {
            if ($stored = Cache::get($scope)) {
                if ($stored['fingerprint'] !== $fingerprint) {
                    return $this->error(__('Esa Idempotency-Key ya se usó con otros datos. Genera una nueva para cada solicitud.'), 'idempotency_key_reused', 422);
                }

                return response($stored['body'], $stored['status'], [
                    'Content-Type' => 'application/json',
                    'Idempotent-Replayed' => 'true',
                ]);
            }

            $response = $next($request);

            if ($response->isSuccessful()) {
                Cache::put($scope, [
                    'fingerprint' => $fingerprint,
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(),
                ], self::TTL);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /** Huella del cuerpo: los campos y el contenido de cada archivo subido. */
    protected function fingerprint(Request $request): string
    {
        $files = collect($request->allFiles())
            ->flatten()
            ->map(fn (UploadedFile $file) => sha1_file($file->getRealPath()))
            ->all();

        $input = $request->except(array_keys($request->allFiles()));
        ksort($input);

        return hash('sha256', json_encode([$input, $files]));
    }

    protected function error(string $message, string $code, int $status): Response
    {
        return response()->json(['message' => $message, 'code' => $code], $status, [
            'Content-Language' => app()->getLocale(),
        ]);
    }
}
