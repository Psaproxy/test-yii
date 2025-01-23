<?php

declare(strict_types=1);

namespace app\modules\order\components;

use app\components\BaseComponent;
use app\exceptions\ModelValidationErrorsException;
use app\modules\order\exceptions\ApprovedOrderAlreadyExistsException;
use app\modules\order\models\Order;
use app\modules\order\repository\OrderRepository;
use yii\db\Exception;

class OrderComponent extends BaseComponent
{
    private OrderRepository $orderRepo;

    public function __construct($config = [])
    {
        parent::__construct($config);

        /** @var OrderRepository $orderRepo */
        $orderRepo = \Yii::$container->get(OrderRepository::class);
        $this->orderRepo = $orderRepo;

        //$this->userComponent = \Yii::$app->getModule('user')->user;
    }

    /**
     * @throws ApprovedOrderAlreadyExistsException
     * @throws Exception
     */
    public function add(
        int $userId,
        float $amount,
        int $days,
    ): int
    {
        /**
         * Проверка, что существует действующий пользователь.
         * @throws NotFounActualUserException
         */
        //$userId = $this->userComponent->mustActualExist($userId);

        // Правила валидации $amount и $term в модели.
        // А проверка конкретных сценариев для данного пользователя должна быть здесь.

        $hasApprovedOrder = $this->orderRepo->hasApproved($userId);
        if ($hasApprovedOrder) {
            // Уникальное исключение, что бы его можно было целенаправленно обработать.
            // Например, для сообщения клиенту.
            throw new ApprovedOrderAlreadyExistsException($userId);
        }

        $order = new Order($userId, $amount, $days);
        if (!$order->getErrors()) $order->save();
        if ($order->getErrors()) {
            throw new ModelValidationErrorsException($order->getErrors());
        }

        return $order->id();
    }

    /**
     * @throws Exception
     * @throws \Throwable
     */
    public function processAll(int $limitOrders = 100): void
    {
        try {
            $limitOrders = max(1, min(100, $limitOrders));

            $this->orderRepo->beginTransaction();

            // Отклонение всех заявок пользователя, если есть 1 одобренная.
            $this->orderRepo->declineAllNotApproved();

            // Выборка не обработанных заявок, без заявок пользователей с одобрением.
            // Выбранные заявки блокируются, выборка только не заблокированных, без ожидания освобождения.
            // Авто разблокировка после завершения транзакции.
            $orders = $this->orderRepo->findAllNotDone($limitOrders);

            $usersIdsWithApproved = [];
            foreach ($orders as $orderData) {
                $order = Order::restore($orderData);

                // Мок обработки, одобрение 10%.
                $statusNew = 0 === random_int(0, 9) ? Order::STATUS_APPROVED : Order::STATUS_DECLINED;

                // Пользователь может иметь только 1 одобренную заявку.
                if (Order::STATUS_APPROVED === $statusNew && isset($usersIdsWithApproved[$order->userId()])) {
                    $statusNew = Order::STATUS_DECLINED;
                }

                if ($order->changeStatus($statusNew)) {
                    if (!$order->getErrors()) $order->save();
                    if ($order->getErrors()) {
                        $exception = new ModelValidationErrorsException($order->getErrors());
                        \Yii::error($exception->getMessage());
                    }
                }

                if (Order::STATUS_APPROVED === $statusNew) {
                    $usersIdsWithApproved[$order->userId()] = true;
                }
            }

            $this->orderRepo->commitTransaction();

        } catch (\Throwable $exception) {
            $this->orderRepo->rollBackTransaction();
            throw $exception;
        }
    }
}
