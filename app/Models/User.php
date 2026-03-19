<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'subscribed',
        'governance_check',
        'account_type',
        'cyclos_user_id',
        'cyclos_group',
        'cyclos_username',
        'merchant_id',
        'status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isAdmin(): bool
    {
        return $this->role_id === 1;
    }

    public function isMerchant(): bool
    {
        return $this->role_id === 2;
    }


    public function cart()
    {
        return $this->belongsToMany(related: Product::class, table: 'user_cart')->withPivot('quantity');
    }

    public function purchase(): HasMany
    {
        return $this->hasMany(related: Purchase::class);
    }

    public function purchaseCart(): BelongsToMany
    {
        return $this->belongsToMany(related: Product::class, table: 'user_purchase_cart')
            ->withPivot(['quantity', 'purchase_price'])
            ->withTimestamps();
    }

    public function getFullname(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAvatar(): string
    {
        return 'https://www.gravatar.com/avatar/' . md5($this->email);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function getRoleNameAttribute()
    {
        return $this->role?->name;
    }

    /**
     * Get the merchant details if user is a merchant
     */
    public function merchant(): HasOne
    {
        return $this->hasOne(Merchant::class, 'user_id');
    }

    public function subscribed()
    {
        return false;
    }

    public function hasMethodOnFile()
    {
        return false;
    }

    public function governancelayers()
    {
        return $this->belongsToMany(GovernanceLayer::class, 'user_governance_layers')->withPivot('status')->withTimeStamps();
    }

    public function scopeMerchants($query)
    {
        return $query->where('role_id', 2);
    }

    public function getGovernanceCheckAttribute($value)
    {
        return $value ?? 'pending';
    }

    public function isTradeMerchant(): bool
    {
        return $this->role_id === 2;
    }

    public function isTradeMember(): bool
    {
        return $this->role_id === 6;
    }

    public function isOrganization(): bool
    {
        return $this->role_id === 7;
    }

    public function isFavorMember(): bool
    {
        return $this->role_id === 8;
    }
}
