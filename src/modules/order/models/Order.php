<?php

declare(strict_types=1);

namespace app\modules\order\models;

use yii\behaviors\TimestampBehavior;

/**
 * @method static Order restore(array $data)
 */
class Order extends \app\models\BaseModel
{
    public const STATUS_CREATED = 'created';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_PREV_ALLOWED = [
        self::STATUS_APPROVED => self::STATUS_CREATED,
        self::STATUS_DECLINED => self::STATUS_CREATED,
    ];

    public function __construct(
        int $userId,
        float $amount,
        int $days,
    )
    {
        parent::__construct([
            'status' => self::STATUS_CREATED,
            'user_id' => $userId,
            'amount' => $amount,
            'days' => $days,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    /**
     * @return string[]
     */
    public function fields(): array
    {
        return [
            'id',
            'status',
            'user_id',
            'amount',
            'days',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            [
                [
                    'user_id',
                    'amount',
                    'days',
                ],
                'required',
            ],
            [
                [
                    'user_id',
                    'amount',
                    'days',
                ],
                'safe',
            ],
            ['status', 'string'],
            ['status', 'default', 'value' => self::STATUS_CREATED],
            ['status', 'trim'],
            ['status', 'validateStatusRule'],
            ['user_id', 'integer', 'min' => 1],
            ['amount', 'double', 'min' => 1.0],
            ['created_at', 'integer'],
            ['updated_at', 'integer'],
        ];
    }

    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
            ],
        ];
    }

    public function validateStatusRule($attribute): bool
    {
        $statusNew = $this->$attribute;
        $statusPre = $this->getOldAttribute($attribute);

        try {
            $this->validateStatus($statusNew, $statusPre);
            return true;
        } catch (\Throwable $exception) {
            $this->addError($attribute, $exception->getMessage());
            return false;
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function userId(): int
    {
        return $this->user_id;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function days(): int
    {
        return $this->days;
    }

    public function createdAt(): \DateTimeInterface
    {
        return (new \DateTimeImmutable())->setTimestamp($this->created_at);
    }

    public function updatedAt(): \DateTimeInterface
    {
        return (new \DateTimeImmutable())->setTimestamp($this->updated_at);
    }

    public function isApproved(): bool
    {
        return self::STATUS_APPROVED === $this->status;
    }

    private function validateStatus(string $statusNew, ?string $statusPre): void
    {
        $statusPreAllowed = self::STATUS_PREV_ALLOWED[$statusNew] ?? '';

        if (self::STATUS_CREATED === $statusNew && !$statusPre) {
            return;
        }

        if (!$statusPreAllowed || $statusPreAllowed !== $statusPre) {
            throw new \RuntimeException(sprintf(
                'Недоступный статус "%s" так как предыдущее значение "%s", а должно быть "%s".',
                $statusNew, $statusPre, $statusPreAllowed
            ));
        }
    }

    public function changeStatus(string $newStatus): bool
    {
        if ($this->status === $newStatus) return false;
        $this->validateStatus($newStatus, $this->status);
        $this->status = $newStatus;

        $this->updated_at = time();

        return true;
    }
}
