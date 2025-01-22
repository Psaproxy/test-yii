<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

abstract class BaseModel extends ActiveRecord
{
    public static function tableName(): string
    {
        $reflector = new \ReflectionClass(static::class);
        $modelName = $reflector->getShortName();
        return strtolower(
            preg_replace('/(?<=\\w)(?=[A-Z])|(?<=[a-z])(?=\d)/', '_', $modelName) . 's'
        );
    }

    public static function restore(array $data): self
    {
        $reflector = new \ReflectionClass(static::class);
        $entity = $reflector->newInstanceWithoutConstructor();
        $entity->attributes = $data;
        if ($data['id']) $entity->setAttribute('id', $data['id']);
        $entity->oldAttributes = $data;
        return $entity;
    }
}
