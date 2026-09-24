<?php

namespace SeoSaas\LaravelSeo\Exceptions;

use Exception;

class SeoApiException extends Exception
{
    protected ?int $statusCode;

    public function __construct(string $message = '', int $code = 0, ?int $statusCode = null, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
