<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiMessage extends Model
{
    protected $fillable = ['ai_conversation_id', 'role', 'content', 'tokens'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function html(): string
    {
        return Str::markdown($this->content, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }
}
