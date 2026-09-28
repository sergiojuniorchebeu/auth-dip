<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationRequest extends Model
{
    protected $fillable = ['reference', 'employer_id', 'diploma_id', 'requester_name', 'requester_email', 'requester_phone', 'holder_name', 'candidate_birth_date', 'candidate_birth_place', 'candidate_id_number', 'candidate_email', 'candidate_phone', 'diploma_number', 'diploma_type', 'specialty', 'declared_average', 'graduation_year', 'program', 'employment_position', 'employment_reference', 'verification_purpose', 'candidate_consent', 'candidate_document_path', 'diploma_document_path', 'transcript_document_path', 'authorization_document_path', 'status', 'decision_note', 'admin_note', 'processed_by', 'processed_at'];
    protected function casts(): array { return ['processed_at' => 'datetime', 'candidate_birth_date' => 'date', 'candidate_consent' => 'boolean', 'declared_average' => 'decimal:2']; }
    public function diploma(): BelongsTo { return $this->belongsTo(Diploma::class); }
    public function employer(): BelongsTo { return $this->belongsTo(User::class, 'employer_id'); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
