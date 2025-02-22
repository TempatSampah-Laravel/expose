<?php

namespace Expose\Client\Contracts;

use Expose\Client\Logger\LoggedRequest;
use Expose\Client\Logger\LoggedResponse;

interface LoggerContract
{
    public function synchronizeRequest(LoggedRequest $loggedRequest): void;

    public function synchronizeResponse(LoggedRequest $loggedRequest, LoggedResponse $loggedResponse): void;
}
