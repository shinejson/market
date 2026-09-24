<?php

namespace App\Concerns;

use App\Models\AuditLog;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $model->writeAuditLog('created', null, $model->toArray());
        });

        static::updated(function (Model $model) {
            $model->writeAuditLog('updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function (Model $model) {
            $model->writeAuditLog('deleted', $model->toArray(), null);
        });
    }

    protected function writeAuditLog(string $action, ?array $before, ?array $after): void
    {
        try {
            AuditLog::query()->create([
                'actor_user_id' => Auth::id(),
                'tenant_id' => $this->tenant_id ?? TenantContext::id(),
                'action' => $action,
                'subject_type' => static::class,
                'subject_id' => $this->getKey(),
                'diff' => [
                    'before' => $this->stripHidden($before),
                    'after' => $this->stripHidden($after),
                ],
                'ip' => Request::ip(),
            ]);
        } catch (\Throwable) {
            // Audit must never break the primary write.
        }
    }

    protected function stripHidden(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        unset($payload['password'], $payload['remember_token']);

        return $payload;
    }
}
