<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDocument extends Model
{
    protected $fillable = ['user_id', 'document_type', 'path', 'original_name', 'status', 'review_note'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
