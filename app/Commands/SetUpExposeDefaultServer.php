<?php

namespace Expose\Client\Commands;


use Expose\Client\Contracts\FetchesPlatformDataContract;
use Expose\Client\Traits\FetchesPlatformData;
use Illuminate\Support\Facades\Artisan;

use function Expose\Common\info;
use function Expose\Common\headline;
use function Laravel\Prompts\select;
use function Termwind\render;

class SetUpExposeDefaultServer implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected string $token;

    public function __invoke(string $token)
    {
        if (!$this->exposePlatformSetup()) return;

        $this->token = $token;

        $closestServer = null;
        $servers = collect();

        headline('Default Server');

        if ($this->isProToken()) {
            $servers = $this->getServers();
            $closestServer = $this->getClosestServer();

            info('This token has access to our high-performance, global server network.');
        } else {
            info('The free license is limited to the <span class="font-bold">free server (Region: Europe)</span>.
            To access our high-performance, global server network, upgrade to <a href="https://expose.dev/go-pro">Expose Pro</a>.');

            Artisan::call("default-server free");
            Artisan::call("default-domain:clear", ['--no-interaction' => true]);
            render(Artisan::output());
        }

        if ($servers->isNotEmpty()) {
            info();
            $server = select(
                label: 'What default server would you like to use?',
                options: $servers->mapWithKeys(function ($server) {
                    return [
                        $server['key'] =>  '[' . $server['key'] . '] ' . $server['region']
                    ];
                }),
                default: $closestServer ? $closestServer['key'] : null,
                hint: "You can use `expose default-server` to change this setting."
            );

            if ($server) {
                Artisan::call("default-server $server");
                Artisan::call("default-domain:clear", ['--no-interaction' => true]);
                render(Artisan::output());
            }
        }
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
