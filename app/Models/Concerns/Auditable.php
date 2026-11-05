<?php

namespace App\Models\Concerns;

use App\Services\Audit\AuditLogger;
use Illuminate\Support\Str;

/**
 * Audit otomatis untuk model: created, updated, deleted, restored.
 *
 * Opsional di model:
 *
 *   protected string $auditModule = 'asset';          // nama modul
 *   protected array  $auditExclude = ['last_seen'];  // field diabaikan
 *   public function auditLabel(): ?string {...}     // label data
 *   public function auditCompanyId(): ?int {...}    // jika tidak ada company_id
 */
trait Auditable
{
    /**
     * Field yang selalu diabaikan (tidak memicu log jika hanya ini yang berubah).
     */
    protected static array $auditGlobalExclude = [
        'created_at',
        'updated_at',
        'remember_token',
        'last_login_at',
        'last_login_ip',
        'last_login_device',
    ];

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAudit(
                'created',
                null,
                $model->auditFilter($model->getAttributes())
            );
        });

        static::updated(function ($model) {
            $changes = $model->auditFilter($model->getChanges());

            if ($changes === []) {
                return;
            }

            $old = [];

            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->getOriginal($key);
            }

            /*
             * Soft delete & restore tercatat sebagai deleted / restored,
             * bukan sebagai updated.
             */
            if (array_keys($changes) === ['deleted_at']) {
                return;
            }

            $model->writeAudit('updated', $old, $changes);
        });

        static::deleted(function ($model) {
            $model->writeAudit(
                'deleted',
                $model->auditFilter($model->getOriginal()),
                null
            );
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->writeAudit('restored', null, null);
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function auditModuleName(): string
    {
        return property_exists($this, 'auditModule')
            ? $this->auditModule
            : Str::snake(class_basename($this));
    }

    public function auditFilter(array $values): array
    {
        $exclude = array_merge(
            static::$auditGlobalExclude,
            property_exists($this, 'auditExclude') ? $this->auditExclude : []
        );

        return array_diff_key($values, array_flip($exclude));
    }

    /**
     * Label yang mudah dibaca, mis. nama asset.
     */
    public function resolveAuditLabel(): string
    {
        if (method_exists($this, 'auditLabel')) {
            $label = $this->auditLabel();

            if ($label) {
                return (string) $label;
            }
        }

        foreach ([
            'asset_name',
            'category_name',
            'sub_category_name',
            'vendor_name',
            'role_name',
            'company_name',
            'name',
            'title',
            'order_id',
            'maintenance_code',
            'original_name',
        ] as $field) {
            $value = $this->getAttribute($field);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '#' . $this->getKey();
    }

    public function resolveAuditCompanyId(): ?int
    {
        if (method_exists($this, 'auditCompanyId')) {
            $id = $this->auditCompanyId();

            if ($id) {
                return (int) $id;
            }
        }

        $id = $this->getAttribute('company_id');

        return $id ? (int) $id : null;
    }

    protected function writeAudit(
        string $event,
        ?array $old,
        ?array $new
    ): void {
        if (!AuditLogger::isEnabled()) {
            return;
        }

        $verbs = [
            'created' => 'menambahkan',
            'updated' => 'mengubah',
            'deleted' => 'menghapus',
            'restored' => 'memulihkan',
        ];

        $moduleLabel = AuditLogger::moduleLabel($this->auditModuleName());

        $description = Str::ucfirst($verbs[$event] ?? $event)
            . ' ' . $moduleLabel
            . ' "' . Str::limit($this->resolveAuditLabel(), 120) . '"';

        AuditLogger::log(
            event: $event,
            module: $this->auditModuleName(),
            description: $description,
            subject: $this,
            oldValues: $old,
            newValues: $new,
            companyId: $this->resolveAuditCompanyId(),
        );
    }
}
