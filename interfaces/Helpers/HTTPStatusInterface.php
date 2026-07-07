<?php

namespace Arris\Helpers;

interface HTTPStatusInterface
{
    public function getReasonPhrase(): string;

    public function getStatusLine(): string;

    public function isInformational(): bool;

    public function isSuccess(): bool;

    public function isRedirection(): bool;

    public function isClientError(): bool;

    public function isServerError(): bool;

    public function isError(): bool;
}
