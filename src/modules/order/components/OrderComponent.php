<?php

declare(strict_types=1);

namespace app\modules\order\components;

use app\components\BaseComponent;
use app\exceptions\ModelValidationErrorsException;
use app\modules\order\exceptions\ApprovedOrderAlreadyExistsException;
use app\modules\order\models\Order;
use yii\db\Exception;

class OrderComponent extends BaseComponent
{
    private \yii\db\Connection $db;

    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->db = \Yii::$app->db;``
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

        // todo Вынести запрос в репозиторий.
        $hasApprovedOrder = (bool)$this->db->createCommand('
                select count(*) 
                from orders 
                where user_id = :userId 
                  and status = :notStatus
                limit 1
            ')->bindValues([
            'userId' => $userId,
            'notStatus' => Order::STATUS_APPROVED,
        ])->queryScalar();

        // Уникальное исключение, что бы его можно было целенаправленно обработать.
        // Например, для сообщения клиенту.
        if ($hasApprovedOrder) {
            throw new ApprovedOrderAlreadyExistsException($userId);
        }

        $order = new Order($userId, $amount, $days);
        if ($order->getErrors()) {
            throw new ModelValidationErrorsException($order->getErrors());
        }

        $order->save();

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

            $this->db->beginTransaction();

            // Отклонение всех заявок пользователя, если есть 1 одобренная.
            // todo Вынести запрос в репозиторий.
            $this->db->createCommand('
                update orders 
                set status = :statusNew
                where user_id in (
                    select oo.user_id 
                    from orders oo 
                    where oo.status = :statusApproves 
                    group by oo.user_id
                ) and status = :statusAllowed
            ')->bindValues([
                'statusAllowed' => Order::STATUS_CREATED,
                'statusApproves' => Order::STATUS_APPROVED,
                'statusNew' => Order::STATUS_DECLINED,
            ])->execute();

            // Выборка не обработанных заявок, без заявок пользователей с одобрением.
            // Выбранные заявки блокируются, выборка только не заблокированных, без ожидания освобождения.
            // Авто разблокировка после завершения транзакции.
            // todo Вынести запрос в репозиторий.
            $orders = $this->db->createCommand('
                select *
                from orders o
                where o.status = :inStatus
                and (
                    select id 
                    from orders oo 
                    where oo.user_id = o.user_id 
                      and oo.id != o.id 
                      and oo.status = :notStatus
                ) is null
                for update skip locked
                limit :limit
            ')->bindValues([
                'inStatus' => Order::STATUS_CREATED,
                'notStatus' => Order::STATUS_APPROVED,
                'limit' => $limitOrders,
            ])->queryAll();

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
                    $order->save();
                    if ($order->getErrors()) {
                        $exception = new ModelValidationErrorsException($order->getErrors());
                        \Yii::error($exception->getMessage());
                    }
                }

                $usersIdsWithApproved[$order->userId()] = true;
            }

            $this->db->getTransaction()?->commit();

        } catch (\Throwable $exception) {
            $this->db->getTransaction()?->rollBack();
            throw $exception;
        }
    }
}
