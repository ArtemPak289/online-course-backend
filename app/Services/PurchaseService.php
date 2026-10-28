<?php

namespace App\Services;

use App\Events\CoursePurchased;
use App\Jobs\SendPaymentNotification;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    public function __construct(
        protected Payment $paymentGateway = new Payment
    ) {}

    /**
     * Создать запись о покупке и инициировать операцию во внешней платёжной системе.
     */
    public function createPurchase(User $user, Course $course): Purchase
    {
        // Приобрести можно только опубликованный курс
        if (! $course->isPublished()) {
            abort(422, 'Невозможно приобрести неопубликованный курс');
        }

        return DB::transaction(function () use ($user, $course) {
            $purchase = Purchase::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ],
                [
                    'amount' => $course->price,
                    'status' => 'pending',
                ]
            );

            // Если курс уже оплачен, повторная инициализация не требуется
            if ($purchase->isPaid()) {
                return $purchase;
            }

            // Обращение к внешней платёжной системе
            $paymentData = $this->paymentGateway->create($purchase);

            $purchase->update([
                'payment_id' => $paymentData['id'],
                'payment_url' => $paymentData['url'],
            ]);

            Log::info('Инициирована покупка курса', [
                'purchase_id' => $purchase->id,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $purchase->amount,
            ]);

            return $purchase;
        });
    }

    /**
     * Обработать входящий вебхук платёжной системы с проверкой криптографической подписи.
     *
     * @param  array<string, mixed>  $payload
     * @return array{purchase: Purchase, idempotent: bool}
     */
    public function handleWebhook(string $rawPayload, ?string $signature, array $payload): array
    {
        // Проверка подписи HMAC-SHA256
        $expectedSignature = hash_hmac('sha256', $rawPayload, (string) config('app.key'));

        if (! hash_equals($expectedSignature, (string) $signature)) {
            Log::warning('Неверная подпись webhook платежа', ['received_signature' => $signature]);
            abort(401, 'Недействительная криптографическая подпись вебхука');
        }

        return DB::transaction(function () use ($payload) {
            $purchase = Purchase::where('payment_id', $payload['payment_id'])->firstOrFail();

            // Идемпотентность: если статус уже paid, повторно события не запускаем
            if ($purchase->status === 'paid') {
                return [
                    'purchase' => $purchase,
                    'idempotent' => true,
                ];
            }

            $purchase->update([
                'status' => $payload['status'],
            ]);

            Log::info('Получен и обработан webhook оплаты', [
                'purchase_id' => $purchase->id,
                'status' => $payload['status'],
            ]);

            if ($purchase->isPaid()) {
                // Доменное событие покупки курса
                event(new CoursePurchased($purchase));

                // Фоновая очередь отправки уведомления об оплате
                SendPaymentNotification::dispatch($purchase->id);
            }

            return [
                'purchase' => $purchase->fresh(),
                'idempotent' => false,
            ];
        });
    }

    /**
     * Получить список покупок пользователя.
     *
     * @return LengthAwarePaginator<Purchase>
     */
    public function getUserPurchases(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->purchases()
            ->with('course')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Проверить, оплачен ли доступ пользователя к конкретному курсу.
     */
    public function hasPurchasedCourse(User $user, Course $course): bool
    {
        // Администраторы и авторы имеют доступ к курсу без покупки
        if ($user->isAdmin() || $user->id === $course->author_id) {
            return true;
        }

        return $user->purchases()
            ->where('course_id', $course->id)
            ->where('status', 'paid')
            ->exists();
    }
}
