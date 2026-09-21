<?php

namespace App\Models;

use App\Enums\SppBillStatus;
use App\Enums\SppPaymentStatus;
use Database\Factories\SppBillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'period', 'amount', 'due_date'])]
class SppBill extends Model
{
    /** @use HasFactory<SppBillFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'amount' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SppPayment::class);
    }

    /**
     * Total yang sudah dibayar & disetujui admin. Relasi payments harus
     * sudah di-eager-load (preventLazyLoading aktif di luar produksi).
     */
    public function paidAmount(): int
    {
        return (int) $this->payments
            ->where('status', SppPaymentStatus::Approved)
            ->sum('amount');
    }

    public function remainingAmount(): int
    {
        return max(0, $this->amount - $this->paidAmount());
    }

    public function status(): SppBillStatus
    {
        $paid = $this->paidAmount();

        if ($paid <= 0) {
            return SppBillStatus::Pending;
        }

        return $paid >= $this->amount ? SppBillStatus::Paid : SppBillStatus::Partial;
    }
}
