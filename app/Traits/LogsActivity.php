<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            static::logActivity($model, 'created', 'Menambahkan ' . $model->getActivityName());
        });

        static::updated(function ($model) {
            // Only log if something actually changed
            $changes = $model->getDirty();
            if (empty($changes)) return;

            // Optional: Filter out timestamps or specific fields
            unset($changes['updated_at']);
            if (empty($changes)) return;

            $details = [
                'old' => array_intersect_key($model->getOriginal(), $changes),
                'new' => $changes,
            ];

            static::logActivity($model, 'updated', 'Memperbarui ' . $model->getActivityName(), $details);
        });

        static::deleted(function ($model) {
            static::logActivity($model, 'deleted', 'Menghapus ' . $model->getActivityName());
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                static::logActivity($model, 'restored', 'Memulihkan ' . $model->getActivityName());
            });
        }
    }

    protected static function logActivity($model, string $action, string $description, array $details = null)
    {
        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'model_type'  => get_class($model),
            'model_id'    => $model->id,
            'description' => $description,
            'details'     => $details,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);
    }

    /**
     * Get a friendly name for the model instance.
     * Override this in the model if needed.
     */
    public function getActivityName(): string
    {
        return ($this->name ?? $this->asset_code ?? $this->id);
    }
}
