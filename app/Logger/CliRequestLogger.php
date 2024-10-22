<?php

namespace Expose\Client\Logger;

use Expose\Client\Support\ConsoleSectionOutput;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Terminal;
use function Termwind\render;
use function Termwind\terminal;

class CliRequestLogger extends Logger
{
    /** @var Collection */
    protected $requests;

    protected $section;

    protected $verbColors = [
        'GET' => 'blue',
        'HEAD' => '#6C7280',
        'OPTIONS' => '#6C7280',
        'POST' => 'yellow',
        'PUT' => 'yellow',
        'PATCH' => 'yellow',
        'DELETE' => 'red',
    ];

    protected $consoleSectionOutputs = [];

    /**
     * The current terminal width.
     *
     * @var int|null
     */
    protected $terminalWidth;

    /**
     * Computes the terminal width.
     *
     * @return int
     */
    protected function getTerminalWidth()
    {
        if ($this->terminalWidth == null) {
            $this->terminalWidth = (new Terminal)->getWidth();

            $this->terminalWidth = $this->terminalWidth >= 30
                ? $this->terminalWidth
                : 30;
        }

        return $this->terminalWidth;
    }

    public function __construct(ConsoleOutputInterface $consoleOutput)
    {
        parent::__construct($consoleOutput);

        $this->section = new ConsoleSectionOutput($this->output->getStream(), $this->consoleSectionOutputs, $this->output->getVerbosity(), $this->output->isDecorated(), $this->output->getFormatter());

        $this->requests = new Collection();
    }

    /**
     * @return ConsoleOutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    public function renderMessageBox(string $text, string $bgColor = "bg-pink-100", $textColor = "text-pink-600", $additionalClasses = "") {
        render("");

        $terminalWidth = terminal()->width();

        if (strlen($text) > $terminalWidth) {
            $lines = collect(explode("\n", wordwrap($text, $terminalWidth, "\n")))
                ->map(function ($line) {
                    return trim($line);
                })
                ->filter();
        }
        else {
            $lines = [$text];
        }

        foreach ($lines as $line) {
            render("<div class='mx-3 w-full px-3 $bgColor $textColor $additionalClasses'> $line </div>");
        }
    }

    public function renderInfo($string) {
        render("<div class='px-2 w-full text-center'> $string </div>");
    }

    public function renderError($text) {
        $this->renderMessageBox($text, "bg-red-100", "text-red-600");
    }

    public function renderConnectionTable($data) {
        render("");

        $template = <<<HTML
    <div class="flex ml-3 mr-6">
        <span class="w-24">key</span>
        <span class=" text-gray-800">&nbsp;</span>
        <span class="text-left font-bold">value</span>
    </div>
HTML;

        foreach ($data as $key => $value) {
            $output = str_replace(
                ['key', 'value'],
                [$key, $value],
                $template
            );

            render($output);
        }
    }

    protected function getRequestColor(?LoggedRequest $request)
    {
        $statusCode = optional($request->getResponse())->getStatusCode();
        $color = 'white';

        if ($statusCode >= 200 && $statusCode < 300) {
            $color = 'green';
        } elseif ($statusCode >= 300 && $statusCode < 400) {
            $color = 'blue';
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            $color = 'yellow';
        } elseif ($statusCode >= 500) {
            $color = 'red';
        }

        return $color;
    }

    public function logRequest(LoggedRequest $loggedRequest)
    {
        $dashboardUrl = 'http://127.0.0.1:'.config('expose.dashboard_port');

        if ($this->requests->has($loggedRequest->id())) {
            $this->requests[$loggedRequest->id()] = $loggedRequest;
        } else {
            $this->requests->prepend($loggedRequest, $loggedRequest->id());
        }
        $this->requests = $this->requests->slice(0, config('expose.max_logged_requests', 10));

        $terminalWidth = $this->getTerminalWidth();

        $requests = $this->requests->map(function (LoggedRequest $loggedRequest) {
            return [
                'method' => $loggedRequest->getRequest()->getMethod(),
                'url' => $loggedRequest->getRequest()->getUri(),
                'duration' => $loggedRequest->getDuration(),
                'time' => $loggedRequest->getStartTime()->isToday() ? $loggedRequest->getStartTime()->toTimeString() : $loggedRequest->getStartTime()->toDateTimeString(),
                'color' => $this->getRequestColor($loggedRequest),
                'status' => optional($loggedRequest->getResponse())->getStatusCode(),
            ];
        });

        $maxMethod = mb_strlen($requests->max('method'));
        $maxDuration = mb_strlen($requests->max('duration'));

        $output = $requests->map(function ($loggedRequest) use ($terminalWidth, $maxMethod, $maxDuration) {
            $method = $loggedRequest['method'];
            $spaces = str_repeat(' ', max($maxMethod + 2 - mb_strlen($method), 0));
            $url = $loggedRequest['url'];
            $duration = $loggedRequest['duration'];
            $time = $loggedRequest['time'];
            $durationSpaces = str_repeat(' ', max($maxDuration + 2 - mb_strlen($duration), 0));
            $color = $loggedRequest['color'];
            $status = $loggedRequest['status'];

            $dots = str_repeat('.', max($terminalWidth - strlen($method.$spaces.$url.$time.$durationSpaces.$duration) - 16, 0));

            if (empty($dots)) {
                $url = substr($url, 0, $terminalWidth - strlen($method.$spaces.$time.$durationSpaces.$duration) - 15 - 3).'...';
            } else {
                $dots .= ' ';
            }

            return sprintf(
                '  <fg=%s;options=bold>%s </>   <fg=%s;options=bold>%s%s</> %s<fg=#6C7280> %s%s%s%s ms</>',
                $color,
                $status,
                $this->verbColors[$method] ?? 'default',
                $method,
                $spaces,
                $url,
                $dots,
                $time,
                $durationSpaces,
                $duration,
            );
        });

        $this->section->overwrite($output);
    }
}
