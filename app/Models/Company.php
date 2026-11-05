<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Company Model
 *
 * @property int $id
 * @property string $company_name
 * @property string $company_code
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Company extends Model
{
    use \App\Models\Concerns\Auditable;

    protected string $auditModule = 'company';

    public function auditCompanyId(): ?int
    {
        return $this->getKey();
    }

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'company_name',
        'company_code',
        'status',
        'subscription_plan',
        'subscription_status',
        'started_at',
        'expired_at',
    ];

    /**
     * Relasi ke user.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Paket yang berlaku hari ini.
     *
     * PENTING: filter status & tanggal harus berada di dalam closure ofMany()
     * agar ikut diterapkan pada subquery pemilihan baris. Dengan
     * ->where(...)->latestOfMany(), subquery memilih ID terbesar tanpa filter,
     * sehingga paket pending / paket antrean yang lebih baru membuat
     * hasilnya NULL (perusahaan dianggap expired).
     */
    public function activeSubscription(): HasOne
    {
        // priority: upgrade (1) menimpa paket regular (0) selama masa upgrade.
        return $this->hasOne(Subscription::class)->ofMany(
            ['priority' => 'max', 'id' => 'max'],
            function ($query) {
                $query->where('status', 'active')
                    ->whereDate('start_date', '<=', today())
                    ->whereDate('end_date', '>=', today());
            }
        );
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

}