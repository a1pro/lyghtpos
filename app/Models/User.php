<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'intended_pos',
        'cyclos_user_id',
        'cyclos_group',
        'cyclos_username',
        'merchant_id',
        'status',
        'cyclos_user_password',
        'wallet_amount',
        'phone',
        'location',
        'otp',
        'time_validity',
        'email_verified',
        'email_verified_at',
        'forgot_password_otp',
        'forgot_password_otp_validity'
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

    protected $appends = [
        'address'
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
        return $this->first_name.' '.$this->last_name;
    }

    public function getAvatar(): string
    {
        return 'https://ui-avatars.com/api/?name=' . $this->getFullname() . '&background=random';
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

     /**
     * Get the merchant user that referred this customer
     * The merchant_id on users table references the user ID of the merchant
     */
    public function referredByMerchantUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'merchant_id');
    }

    /**
     * Get the merchant record that referred this customer
     * Through the merchant user relationship
     */
    public function referredByMerchant(): HasOne
    {
        return $this->hasOne(Merchant::class, 'user_id', 'merchant_id');
    }

    /**
     * Check if the user is a customer referred by a merchant
     */
    public function isReferredByMerchant(): bool
    {
        return !is_null($this->merchant_id) && $this->isCustomer();
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

    public function isCustomer(): bool
    {
        return $this->role_id === 11;
    }

    public function scopeFavorMembers($query)
    {
        return $query->where('role_id', 8);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class, 'merchant_id');
    }

    // Add transactions relationship (if not exists)
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'merchant_id');
    }

    // Add notifications relationship
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Get full name
    public function getFullnameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Get the address for the user
     */
    public function address(): HasOne
    {
        return $this->hasOne(UserAddress::class);
    }
    
    public function getAddressAttribute()
    {
        return $this->address()->first();
    }
}
