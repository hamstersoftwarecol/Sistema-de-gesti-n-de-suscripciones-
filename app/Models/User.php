<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STAFF = 'staff';

    public const ROLE_SELLER = 'seller';

    public const ROLE_CUSTOMER = 'customer';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_STAFF, self::ROLE_SELLER, self::ROLE_CUSTOMER];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'customer_id',
        'google_id',
        'avatar',
        'phone',
        'locale',
        'theme',
        'accent',
        'surface',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Mirror the database defaults so freshly created models are complete. */
    protected $attributes = [
        'role' => self::ROLE_STAFF,
        'theme' => 'system',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_ADMIN => __('Administrator'),
            self::ROLE_STAFF => __('Staff'),
            self::ROLE_SELLER => __('Seller'),
            self::ROLE_CUSTOMER => __('Customer'),
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class);
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    public function assignedCards(): HasMany
    {
        return $this->hasMany(KanbanCard::class, 'assigned_to');
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Admins and staff members manage the whole back office. */
    public function isStaff(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN, self::ROLE_STAFF);
    }

    public function isSeller(): bool
    {
        return $this->role === self::ROLE_SELLER;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function homeRoute(): string
    {
        return $this->isCustomer() ? route('portal.dashboard') : route('dashboard');
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
