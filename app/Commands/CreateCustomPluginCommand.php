<?php

namespace Expose\Client\Commands;



use Expose\Client\Logger\Plugins\PluginManager;
use LaravelZero\Framework\Commands\Command;

use function Expose\Common\banner;
use function Expose\Common\info;
use function Expose\Common\warning;
use function Laravel\Prompts\text;

class CreateCustomPluginCommand extends Command
{


    protected $signature = 'make:plugin';

    protected $description = 'Create a new custom request plugin.';

    public function handle(PluginManager $pluginManager)
    {

        banner();

        info('Check out the <a href="https://expose.dev/docs/advanced-usage/request-plugins">documentation</a> to learn how request plugins work.');

        $pluginName = text(
            label: 'What is the name of the plugin?',
            placeholder: 'MyCustomPlugin',
            required: true
        );

        $pluginName = preg_replace('/[^a-zA-Z0-9]/', '', $pluginName);

        $customPluginDirectory = $pluginManager->getCustomPluginDirectory();

        $pluginFile = implode(DIRECTORY_SEPARATOR, [$customPluginDirectory, $pluginName . '.php']);

        if (file_exists($pluginFile)) {
            warning("The file at $pluginFile already exists.");
            return;
        }

        $pluginStub = file_get_contents(base_path('resources/stubs/CustomRequestPlugin.php.stub'));
        $pluginStub = str_replace('__CLASSNAME__', $pluginName, $pluginStub);

        file_put_contents($pluginFile, $pluginStub);

        info("✔ The plugin has been created at $pluginFile and added to the configuration.</div>");

        $this->call('plugins:manage', ['--add' => 'Expose\\Client\\Logger\\Plugins\\' . $pluginName]);

    }

}
