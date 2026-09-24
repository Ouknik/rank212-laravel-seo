<?php

namespace SeoSaas\LaravelSeo\Exceptions;

class SeoQuotaExceededException extends SeoApiException
{
    // Thrown when monthly quota is exhausted (HTTP 429 / 402)
}
