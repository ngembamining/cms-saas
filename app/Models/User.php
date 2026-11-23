<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    // DISABLE TIMESTAMPS - table doesn't have updated_at
    public $timestamps = false;

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'password',
        'role',
        'password_changed',
        'assigned_branch',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_method',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
        'phone_number',
        'reset_token',
        'reset_expires',
        'avatar',
        // Email settings
        'email_password',
        'email_imap_host',
        'email_imap_port',
        'email_smtp_host',
        'email_smtp_port',
        'email_encryption',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'email_password',
    ];

    protected $casts = [
        'password_changed'          => 'boolean',
        'two_factor_enabled'        => 'boolean',
        'two_factor_confirmed_at'   => 'datetime',
        'reset_expires'             => 'datetime',
        'created_at'                => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * Get the branch that the user belongs to
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'assigned_branch');
    }

    /**
     * Alias for branch relationship
     */
    public function assignedBranch()
    {
        return $this->belongsTo(Branch::class, 'assigned_branch');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'recorded_by');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'created_by');
    }

    public function approvedLoans()
    {
        return $this->hasMany(Loan::class, 'approved_by');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'recorded_by');
    }

    // ============================================
    // EMAIL SETTINGS
    // ============================================

    /**
     * Get user's email password (decrypted)
     */
    public function getEmailPasswordAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Set user's email password (encrypted)
     */
    public function setEmailPasswordAttribute($value)
    {
        $this->attributes['email_password'] = $value ? encrypt($value) : null;
    }

    // ============================================
    // AVATAR METHODS
    // ============================================

    /**
     * Get the user's avatar with fallback
     */
    public function getAvatarAttribute($value)
    {
        if ($value && !empty($value)) {
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return $value;
            }
            return asset('storage/' . $value);
        }
        return $this->getDefaultAvatar();
    }

    /**
     * Get the avatar URL for API/JSON responses
     */
    public function getAvatarUrlAttribute()
    {
        return $this->avatar;
    }

    /**
     * Get default avatar - always returns a valid URL
     */
    private function getDefaultAvatar()
    {
        // Try to use the SVG default avatar
        if (file_exists(public_path('img/default-avatar.svg'))) {
            return asset('img/default-avatar.svg');
        }

        // Fallback: generate a data URI with initials
        $name = $this->full_name ?? $this->username ?? 'User';
        $initials = $this->getInitials($name);
        $color = $this->getColorFromString($name);

        return "data:image/svg+xml;charset=utf-8," . urlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="50" fill="' . $color . '"/>
                <text x="50" y="58" font-size="40" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-weight="bold">' . $initials . '</text>
            </svg>'
        );
    }

    /**
     * Get initials from name
     */
    private function getInitials($name)
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(mb_substr($word, 0, 1));
            }
        }
        return substr($initials, 0, 2) ?: 'U';
    }

    /**
     * Get a color from a string
     */
    private function getColorFromString($string)
    {
        $colors = ['#4F46E5', '#7C3AED', '#2563EB', '#0D9488', '#059669', '#D97706', '#DC2626', '#7C3AED', '#6366F1', '#8B5CF6'];
        $hash = 0;
        for ($i = 0; $i < strlen($string); $i++) {
            $hash = ord($string[$i]) + (($hash << 5) - $hash);
        }
        return $colors[abs($hash) % count($colors)];
    }

    // ============================================
    // ROLE METHODS
    // ============================================

    public function hasRole($role)
    {
        if (is_array($role)) {
            return in_array($this->role, $role);
        }
        return $this->role === $role;
    }

    public function getRoleLabelAttribute()
    {
        $labels = [
            'director' => 'Director',
            'branch_manager' => 'Branch Manager',
            'accountant' => 'Accountant',
            'secretary' => 'Secretary',
            'hr' => 'HR',
            'staff' => 'Staff',
        ];
        return $labels[$this->role] ?? ucfirst($this->role);
    }

    public function getNameAttribute()
    {
        return $this->full_name ?? $this->username;
    }

    // ============================================
    // TWO-FACTOR AUTHENTICATION METHODS
    // ============================================

    /**
     * Does this user have 2FA turned on?
     *
     * Returns true only when:
     *   - the flag is set, AND
     *   - a valid method is chosen ('authenticator' or 'sms')
     */
    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->two_factor_enabled
            && !empty($this->two_factor_method)
            && in_array($this->two_factor_method, ['authenticator', 'sms'], true);
    }

    /**
     * Has the user finished confirming 2FA setup?
     */
    public function hasConfirmedTwoFactor(): bool
    {
        return $this->hasTwoFactorEnabled()
            && !is_null($this->two_factor_confirmed_at);
    }

    /**
     * Human-readable label for the 2FA method.
     */
    public function getTwoFactorMethodLabelAttribute(): string
    {
        return match ($this->two_factor_method) {
            'authenticator' => 'Authenticator App',
            'sms'           => 'SMS',
            default         => 'None',
        };
    }

    /**
     * Convenience role helpers (optional, but handy for controllers/middleware).
     */
    public function isDirector(): bool
    {
        return $this->role === 'director';
    }

    public function isBranchManager(): bool
    {
        return $this->role === 'branch_manager';
    }

    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }
}