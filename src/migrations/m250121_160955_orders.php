<?php

use yii\db\Migration;

class m250121_160955_orders extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('orders', [
            'id' => $this->primaryKey(),
            'status' => $this->string()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'amount' => $this->float()->notNull(),
            'days' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropTable('orders');
    }
}
