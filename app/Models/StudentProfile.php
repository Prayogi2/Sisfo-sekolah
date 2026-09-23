<?php

namespace App\Models;

use App\Enums\BloodType;
use App\Enums\FamilyStatus;
use App\Enums\Religion;
use App\Enums\ResidenceType;
use App\Enums\TransportationMode;
use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'nickname',
    'nik',
    'religion',
    'family_status',
    'birth_order',
    'siblings_count',
    'weight_kg',
    'height_cm',
    'blood_type',
    'street_address',
    'hamlet',
    'village',
    'district',
    'regency',
    'province',
    'postal_code',
    'residence_type',
    'transportation',
    'distance_km',
    'travel_duration_minutes',
    'photo_path',
])]
class StudentProfile extends Model
{
    /** @use HasFactory<StudentProfileFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'religion' => Religion::class,
            'family_status' => FamilyStatus::class,
            'blood_type' => BloodType::class,
            'residence_type' => ResidenceType::class,
            'transportation' => TransportationMode::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
