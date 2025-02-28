<?php

namespace Expose\Client\Commands;

use Expose\Client\Contracts\FetchesPlatformDataContract;
use Expose\Client\Traits\FetchesPlatformData;
use Illuminate\Support\Facades\Artisan;

class SetUpExposeProToken implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected string $token;


    public function __invoke(string $token)
    {
        if (!$this->exposePlatformSetup()) return;

        $this->token = $token;

        if ($this->isProToken() && $this->hasTeamDomains()) {
             (new SetUpExposeDefaultDomain)($token);
        } else {
             (new SetUpExposeDefaultServer)($token);
        }
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
