<?php

namespace Expose\Client\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use function Expose\Common\banner;
use function Expose\Common\lineTable;
use function Expose\Common\lineTableLabel;
use function Expose\Common\warning;
use function Expose\Common\info;
use function Expose\Common\headline;
use function Laravel\Prompts\confirm;

class RollbackTokenCommand extends Command
{


    protected $signature = 'token:rollback';

    protected $description = 'Rollback the Expose token and setup to the previous version, if applicable.';

    protected string $previousSetupPath;

    public function handle()
    {

        $this->previousSetupPath = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.expose',
            'previous_setup.json',
        ]);

        banner();

        if (!file_exists($this->previousSetupPath)) {
            warning('No previous setup found.');
            return;
        }

        headline('Previous Setup');

        $previousSetup = json_decode(file_get_contents($this->previousSetupPath), true);

        $previousSetupTable = collect($previousSetup)->mapWithKeys(function ($value, $key) {
            return [lineTableLabel($key) => lineTableLabel($value)];
        })->toArray();

        lineTable($previousSetupTable);

        if (!confirm("Do you want to rollback your Expose setup to the previous state?", false)) {
            return;
        }

        $this->rememberPreviousSetup();

        $token = $previousSetup['token'];

        Artisan::call("token $token --no-interaction");

        info("✔ Set Expose token to <span class='font-bold'>$token</span>.");

        if ($domain = $previousSetup['default_domain']) {
            Artisan::call("default-domain $domain");
        }

        if ($server = $previousSetup['default_server']) {
            Artisan::call("default-server $server");
        }

        Artisan::output();
    }


    protected function rememberPreviousSetup()
    {
        $previousSetup = [
            'token' => config('expose.auth_token'),
            'default_server' => config('expose.default_server'),
            'default_domain' => config('expose.default_domain'),
        ];

        file_put_contents($this->previousSetupPath, json_encode($previousSetup));
    }
}
