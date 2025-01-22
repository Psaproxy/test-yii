<?php

declare(strict_types=1);

namespace app\modules\order\exceptions;

class ApprovedOrderAlreadyExistsException extends \RuntimeException
{
    public function __construct(
        public readonly int $userId
    )
    {
        parent::__construct("Пользователь ID $userId уже имеет одобренную заявку.");
    }
}
