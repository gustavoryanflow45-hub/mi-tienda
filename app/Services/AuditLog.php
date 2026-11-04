<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Escribe en admin_audit_logs quién hizo qué, sobre qué, desde dónde.
 *
 * El actor es una cuenta del panel (admin_id) o un usuario de la tienda
 * (user_id; p. ej. un vendedor que importa un CSV). Por defecto, quien esté
 * autenticado en el guard de la ruta actual.
 *
 * $meta lleva solo lo que cada llamada elige (importes, recuentos, ids). Por
 * si alguien pasa de más, las claves que parecen datos sensibles se descartan
 * antes de guardar: nada de tarjetas, comprobantes, contraseñas ni códigos.
 */
class AuditLog
{
    /** Claves de $meta que nunca se guardan (se comparan sin mayúsculas). */
    protected const SENSITIVE_KEY = '/pass|secret|token|card|cvv|cvc|proof|otp|recovery|account_number|iban/i';

    public function record(string $action, ?Model $subject = null, array $meta = [], ?Authenticatable $actor = null): AdminAuditLog
    {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();
        $actor ??= auth()->user();

        return AdminAuditLog::create([
            'admin_id' => $actor instanceof Admin ? $actor->id : null,
            'user_id' => $actor instanceof User ? $actor->id : null,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'meta' => $this->clean($meta) ?: null,
            'ip' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 509) : null,
        ]);
    }

    protected function clean(array $meta): array
    {
        return collect($meta)
            ->reject(fn ($value, $key) => preg_match(self::SENSITIVE_KEY, (string) $key))
            ->map(fn ($value) => is_array($value) ? $this->clean($value) : $value)
            ->all();
    }
}
