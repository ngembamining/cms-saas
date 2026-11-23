<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LunaConversation extends Model
{
    use HasFactory;

    protected $table = 'luna_conversations';

    protected $fillable = [
        'user_id',
        'title',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages for the conversation.
     * Use 'conversation_id' as the foreign key
     */
    public function messages()
    {
        return $this->hasMany(LunaMessage::class, 'conversation_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}