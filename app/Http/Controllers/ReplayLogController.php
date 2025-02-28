<?php

namespace Expose\Client\Http\Controllers;

use Expose\Client\Http\HttpClient;
use Expose\Client\Logger\RequestLogger;
use Expose\Common\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class ReplayLogController extends Controller
{
    public function __construct(protected RequestLogger $requestLogger, protected HttpClient $httpClient)
    {
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $loggedRequest = $this->requestLogger->findLoggedRequest($request->get('log'));

        if (is_null($loggedRequest)) {
            $httpConnection->send(Message::toString(new Response(404)));

            return;
        }

        $loggedRequest->refreshId();

        $this->httpClient->performRequest($loggedRequest->getRequest()->toString());

        $httpConnection->send(Message::toString(new Response(200)));
    }
}
