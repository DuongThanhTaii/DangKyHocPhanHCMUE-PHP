<?php

namespace App\Application\Common;

/**
 * Base Response class for all UseCase responses
 * 
 * Standard format: { isSuccess, data, message, ?errors }
 */
readonly class UseCaseResponse
{
    public function __construct(
        public bool $isSuccess,
        public mixed $data = null,
        public string $message = '',
        public ?array $errors = null,
    ) {
    }

    public static function success(mixed $data = null, string $message = 'Thành công'): self
    {
        return new self(
            isSuccess: true,
            data: $data,
            message: $message,
        );
    }

    public static function fail(string $message, ?array $errors = null): self
    {
        return new self(
            isSuccess: false,
            data: null,
            message: $message,
            errors: $errors,
        );
    }

    public function toArray(): array
    {
        $result = [
            'isSuccess' => $this->isSuccess,
            'data' => $this->data,
            'message' => $this->message,
        ];

        if ($this->errors !== null) {
            $result['errors'] = $this->errors;
        }

        return $result;
    }
}
