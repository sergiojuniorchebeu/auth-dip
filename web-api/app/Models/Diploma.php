<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Diploma extends Model
{
    protected $fillable = ['number', 'holder_name', 'program', 'diploma_type', 'specialty', 'study_level', 'study_year', 'average', 'mention', 'graduation_year', 'issue_date', 'institution', 'document_path', 'transcript_path', 'status', 'qr_token'];
    protected $hidden = ['qr_token'];
    protected function casts(): array { return ['average' => 'decimal:2', 'issue_date' => 'date']; }

    public function verificationRequests(): HasMany { return $this->hasMany(VerificationRequest::class); }
}
