<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model
{
    use HasFactory;
    protected $table = 'suppliers';
    // DISABLE TIMESTAMPS
    public $timestamps = false;
    protected $fillable = [
        'name',
        'contact',
        'address',
        'branch_id',
        'email',
        'avatar',
        'outstanding_balance',
    ];
    protected $casts = [
        'outstanding_balance' => 'decimal:2',
    ];
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    public function loans()
    {
        return $this->hasMany(Loan::class, 'suppliers_id');
    }
    /**
     * Get the supplier's avatar with fallback
     */
    public function getAvatarAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return asset('img/default-avatar.png');
    }
    /**
     * Get the supplier's avatar URL
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->attributes['avatar'] ?? false) {
            return asset('storage/' . $this->attributes['avatar']);
        }
        return asset('img/default-avatar.png');
    }
}