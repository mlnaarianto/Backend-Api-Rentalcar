<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chat extends Model
{
    protected $guarded = ['id'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastSender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_sender_id');
    }

    /**
     * Ambil semua sender_id unik yang pernah kirim pesan di chat ini.
     * Dipakai buat nentuin siapa "customer" lawan bicara admin.
     */
    public function participantIds(): array
    {
        return $this->messages()->distinct()->pluck('sender_id')->toArray();
    }
}