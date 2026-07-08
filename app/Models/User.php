<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['karyawan_id', 'name', 'email', 'password', 'google_id', 'avatar_url', 'nonaktif_pada'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'nonaktif_pada' => 'datetime',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function sudahKlaim(): bool
    {
        return $this->karyawan_id !== null;
    }

    public function akunAktif(): bool
    {
        return $this->nonaktif_pada === null;
    }

    /**
     * Tim teknis yang boleh diakses user (dari permission).
     * Admin Sistem → semua tim.
     *
     * @return list<\App\Enums\TimTeknis>
     */
    public function timTeknis(): array
    {
        if ($this->hasRole(\App\Enums\Role::AdminSistem->value)) {
            return \App\Enums\TimTeknis::cases();
        }

        $permissionDimiliki = $this->getAllPermissions()->pluck('name');

        return array_values(array_filter(
            \App\Enums\TimTeknis::cases(),
            fn (\App\Enums\TimTeknis $t) => $permissionDimiliki->contains($t->permission()),
        ));
    }
}
