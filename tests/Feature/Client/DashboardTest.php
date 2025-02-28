<?php

namespace Tests\Feature\Client;

use Expose\Client\Configuration;
use Expose\Client\Factory;
use Expose\Client\Http\HttpClient;
use Expose\Client\Logger\LoggedRequest;
use Expose\Client\Logger\RequestLogger;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Arr;
use Mockery as m;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use Tests\Feature\TestCase;


class DashboardTest extends TestCase
{
    /** @var Browser */
    protected $browser;

    /** @var Factory */
    protected $dashboardFactory;

    /** @var RequestLogger */
    protected $requestLogger;

    public function setUp(): void
    {
        parent::setUp();

        $this->browser = new Browser($this->loop);
        $this->requestLogger = $this->app->make(RequestLogger::class);
    }

    public function tearDown(): void
    {
        $this->dashboardFactory->getApp()->close();

        parent::tearDown();
    }

    /** @test */
    public function accessing_the_dashboard_works()
    {
        $this->startDashboard();

        /** @var ResponseInterface $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:4040'));

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function it_can_replay_requests()
    {
        $request = new Request('GET', '/example', [
            'X-Expose-Request-ID' => 'request-one',
        ]);

        $httpClient = m::mock(HttpClient::class);
        $httpClient->shouldReceive('performRequest')
            ->once()
            ->withArgs(function ($arg) {
                $sentRequest = Message::parseMessage($arg);

                return Arr::get($sentRequest, 'start-line') === 'GET /example HTTP/1.1';
            });

        app()->instance(HttpClient::class, $httpClient);

        $this->startDashboard();

        $this->logRequest($request);

        $this->assertSame(200,
            $this->await($this->browser->get('http://127.0.0.1:4040/api/replay/request-one'))
                ->getStatusCode()
        );
    }

    /** @test */
    public function it_can_replay_modified_requests()
    {
        $request = new Request('GET', '/example', [
            'X-Expose-Request-ID' => 'request-one',
        ]);

        $httpClient = m::mock(HttpClient::class);
        $httpClient->shouldReceive('performRequest')
            ->once()
            ->withArgs(function ($arg) {
                $sentRequest = Message::parseMessage($arg);

                return Arr::get($sentRequest, 'start-line') === 'POST /modified HTTP/1.1';
            });

        app()->instance(HttpClient::class, $httpClient);

        $this->startDashboard();

        $this->logRequest($request);

        $this->assertSame(200,
            $this->await($this->browser->post('http://127.0.0.1:4040/api/replay-modified', [
                'Content-Type' => 'application/json',
            ], json_encode([
                'method' => 'POST',
                'uri' => '/modified',
                'headers' => [
                    'X-Expose-Request-ID' => 'request-one',
                ],
                'body' => '',
            ])))
                ->getStatusCode()
        );
    }

    /** @test */
    public function it_returns_404_for_non_existing_replay_logs()
    {
        $this->startDashboard();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(404);

        $this->await($this->browser->get('http://127.0.0.1:4040/api/replay/invalid-request'));
    }

    /** @test */
    public function it_can_clear_logs()
    {
        $this->startDashboard();

        $this->logRequest(new Request('GET', '/foo'));
        $this->logRequest(new Request('POST', '/bar'));
        $this->logRequest(new Request('DELETE', '/baz'));

        $this->assertCount(3, $this->requestLogger->getData());

        $this->await($this->browser->get('http://127.0.0.1:4040/api/logs/clear'));
        $this->assertCount(0, $this->requestLogger->getData());
    }

    protected function logRequest(RequestInterface $request): LoggedRequest
    {
        return $this->requestLogger->logRequest(
            Message::toString($request),
            \Laminas\Http\Request::fromString(Message::toString($request))
        );
    }

    protected function startDashboard()
    {
        app()->singleton(Configuration::class, function ($app) {
            return new Configuration('localhost', '8080', false);
        });

        $this->dashboardFactory = (new Factory())
            ->setLoop($this->loop)
            ->createHttpServer();
    }
}
