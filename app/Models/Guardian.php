<?php

namespace App\Models;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\GuardianRelationship;
use App\Enums\Religion;
use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'user_id',
    'name',
    'relationship',
    'gender',
    'phone',
    'occupation',
    'monthly_income',
    'nik',
    'family_card_number',
    'birth_place',
    'birth_date',
    'religion',
    'blood_type',
    'last_education',
    'address',
])]
class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relationship' => GuardianRelationship::class,
            'birth_date' => 'date',
            'religion' => Religion::class,
            'blood_type' => BloodType::class,
            'last_education' => EducationLevel::class,
        ];
    }

    /**
     * The login account for this guardian, if any.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The students (children) linked to this guardian.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student');
    }
}
