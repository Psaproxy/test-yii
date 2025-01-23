<?php

declare(strict_types=1);

namespace app\modules\order\repository;

use app\modules\order\models\Order;
use app\repository\BaseRepository;
use yii\db\Exception;

class OrderRepository extends BaseRepository
{
    /**
     * @throws Exception
     */
    public function hasApproved(int $userId): bool
    {
        return (bool)$this->db->createCommand('
                select count(*) 
                from orders 
                where user_id = :userId 
                  and status = :notStatus
                limit 1
            ')->bindValues([
            'userId' => $userId,
            'notStatus' => Order::STATUS_APPROVED,
        ])->queryScalar();
    }

    /**
     * @throws Exception
     */
    public function declineAllNotApproved(): void
    {
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
    }

    /**
     * @return array[]
     * @throws Exception
     */
    public function findAllNotDone(mixed $limitOrders): array
    {
        return $this->db->createCommand('
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
    }
}
