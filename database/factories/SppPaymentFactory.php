<?php

namespace Database\Factories;

use App\Enums\SppPaymentStatus;
use App\Models\Guardian;
use App\Models\SppBill;
use App\Models\SppPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SppPayment>
 */
class SppPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spp_bill_id' => SppBill::factory(),
            'guardian_id' => Guardian::factory(),
            'verified_by' => null,
            'amount' => 350000,
            'proof_path' => 'bukti-spp/'.fake()->uuid().'.jpg',
            'status' => SppPaymentStatus::Pending,
            'verified_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => SppPaymentStatus::Approved,
            'verified_at' => now(),
        ]);
    }
}
