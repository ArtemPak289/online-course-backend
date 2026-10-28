<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * Определение состояния покупки по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentId = 'pay_'.Str::uuid();

        return [
            'user_id' => User::factory()->student(),
            'course_id' => Course::factory(),
            'amount' => 99.99,
            'status' => 'pending',
            'payment_id' => $paymentId,
            'payment_url' => config('app.url').'/api/payments/'.$paymentId,
        ];
    }

    /**
     * Состояние: успешно оплаченная покупка.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }
}
