<?php

namespace Expose\Client\Commands;

use Illuminate\Console\Command;

use function Expose\Common\banner;
use function Expose\Common\warning;
use function Expose\Common\info;

class GetAuthenticationTokenCommand extends Command
{


    protected $signature = 'token:get';
    protected $description = 'Retrieve the authentication token to use with Expose.';

    public function handle()
    {
        $token = config('expose.auth_token');

        if ($this->option('no-interaction') === true) {
            $this->line($token ?? '');
            return;
        }

        banner();

        if (empty($token)) {
            warning('There is no authentication token specified.');
        } else {
            info("Current authentication token: <span class='font-bold'>$token</span>");
        }
    }
}
