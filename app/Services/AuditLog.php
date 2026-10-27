<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Escribe en admin_audit_logs quién hizo qué, sobre qué, desde dónde.
 *
 * $meta lleva solo lo que cada llamada elige (importes, recuentos, ids). Por
 * si alguien pasa de más, las claves que parecen datos sensibles se descartan
 * antes de guardar: nada de tarjetas, comprobantes, contraseñas ni códigos.
 */
class AuditLog
{
    /** Claves de $meta que nunca se guardan (se comparan sin mayúsculas). */
    protected const SENSITIVE_KEY = '/pass|secret|token|card|cvv|cvc|proof|otp|recovery|account_number|iban/i';

    public function record(string $action, ?Model $subject = null, array $meta = [], ?User $actor = null): AdminAuditLog
    {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        return AdminAuditLog::create([
            'user_id' => ($actor ?? auth()->user())?->id,
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
