<?php

declare(strict_types=1);

namespace app\repository;

use yii\db\Exception;

abstract class BaseRepository
{
    protected \yii\db\Connection $db;

    public function __construct()
    {
        $this->db = \Yii::$app->db;
    }

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function rollBackTransaction(): void
    {
        $this->db->getTransaction()?->rollBack();
    }

    /**
     * @throws Exception
     */
    public function commitTransaction(): void
    {
        $this->db->getTransaction()?->commit();
    }
}
