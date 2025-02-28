<?php

namespace Expose\Client\Commands;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

use function Expose\Common\banner;
use function Expose\Common\info;
use function Laravel\Prompts\table;

class ServerListCommand extends ServerAwareCommand
{

    const DEFAULT_SERVER_ENDPOINT = 'https://expose.dev/api/servers';

    protected $signature = 'servers {--json}';

    protected $description = 'Set or retrieve the default server to use with Expose.';

    public function handle()
    {

        $servers = collect($this->lookupRemoteServers())->map(function ($server) {
            return [
                'key' => $server['key'],
                'region' => $server['region'],
                'plan' => Str::ucfirst($server['plan']),
            ];
        });

        if($this->option('json')) {
            $this->line($servers->toJson());
            return;
        }

        banner();

        info('You can connect to a specific server with the <span class="font-bold">--server=key</span> option or set this server as default with the <span class="font-bold">default-server</span> command.');

        table(['Key', 'Region', 'Type'], $servers);
    }
}
