<?php

declare(strict_types=1);

namespace app\exceptions;

class ModelValidationErrorsException extends \RuntimeException
{
    /**
     * @param array $errors ключ - имя атрибута модели, значение - массив строк сообщений ошибок
     */
    public function __construct(
        public readonly array $errors,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null)
    {
        parent::__construct(json_encode($errors, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE), $code, $previous);
    }
}
