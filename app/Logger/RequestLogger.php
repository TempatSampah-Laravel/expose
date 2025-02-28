<?php

namespace Expose\Client\Logger;

use Expose\Client\Contracts\LogStorageContract;
use Laminas\Http\Request;
use Laminas\Http\Response;

class RequestLogger
{
    public function __construct(protected CliLogger $cliLogger, protected FrontendLogger $frontendLogger, protected LogStorageContract $logStorage, )
    {
    }

    public function findLoggedRequest(string $id): ?LoggedRequest
    {
        return $this->logStorage->requests()->withResponses()->find($id);
    }

    public function logRequest(string $rawRequest, Request $request): LoggedRequest
    {
        $loggedRequest = new LoggedRequest($rawRequest, $request);

        $this->cliLogger->synchronizeRequest($loggedRequest);
        $this->logStorage->synchronizeRequest($loggedRequest);
        $this->frontendLogger->synchronizeRequest($loggedRequest);

        return $loggedRequest;
    }

    public function logResponse(Request $request, string $rawResponse)
    {
        $exposeRequestId = $request->getHeader("x-expose-request-id") ? $request->getHeader("x-expose-request-id")->getFieldValue() : null;

        if (!$exposeRequestId) {
            return;
        }

        try {
            $loggedRequest = $this->logStorage->requests()->find($exposeRequestId);

            if($loggedRequest === null) {
                return;
            }

            $response = Response::fromString($rawResponse);
            $loggedResponse = new LoggedResponse($rawResponse, $response, $request);

            $loggedRequest->setResponse($rawResponse, $response);
            $loggedRequest->setStopTime();

            $this->logStorage->synchronizeResponse($loggedRequest, $loggedResponse, $rawResponse);

            $this->frontendLogger->synchronizeResponse($loggedRequest, $loggedResponse, $rawResponse);

            $this->cliLogger->synchronizeResponse($loggedRequest, $loggedResponse, $rawResponse);
        } catch (\Throwable $e) {
            //
        }
    }

    public function getData(): array
    {
        $requests = $this->logStorage->requests()->get();

        return $requests ? $requests->toArray() : [];
    }

    public function clear()
    {
        $this->logStorage->requests()->delete();
    }
}
