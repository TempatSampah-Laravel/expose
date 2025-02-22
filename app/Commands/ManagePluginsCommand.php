<?php

namespace Expose\Client\Commands;

use Expose\Client\Logger\Plugins\PluginManager;
use LaravelZero\Framework\Commands\Command;

use function Expose\Common\banner;
use function Expose\Common\info;
use function Expose\Common\warning;
use function Laravel\Prompts\multiselect;

class ManagePluginsCommand extends Command
{

    protected $signature = 'plugins:manage {--add=}';

    protected $description = 'Activate and deactivate request plugins.';

    protected PluginManager $pluginManager;


    public function handle(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;

        $plugins = $this->pluginManager->getPlugins();

        if($this->option('add')) {
            $this->addPlugin($this->option('add'));
            return;
        }


        banner();

        info('Request plugins allow you to highlight important parts about incoming requests in the CLI and client dashboard.');
        info("You can learn more about them in the <a href='https://expose.dev/docs/advanced-usage/request-plugins'>documentation</a>.");


        // Build key-based list for easier Windows support
        $activePlugins = collect($this->pluginManager->getEnabledPlugins())
            ->map(function ($pluginClass) {
                return class_basename($pluginClass);
            })
            ->toArray();


        $pluginSelectList = collect($plugins)
            ->mapWithKeys(function ($pluginClass) {
                return [str($pluginClass)->afterLast('\\')->__toString() => $pluginClass];
            })
            ->toArray();

        $pluginsToEnable = multiselect(
            'Select the plugins you want to enable:',
            $pluginSelectList,
            $activePlugins);


        $pluginsToEnable = collect($pluginsToEnable)
            ->map(function ($pluginClass) use ($pluginSelectList) {
                return $pluginSelectList[$pluginClass];
            })
            ->values()
            ->toArray();

        $this->pluginManager->modifyPluginConfiguration($pluginsToEnable);

        info("✔ Request plugins have been updated.");
    }

    protected function addPlugin(string $class): void {

        if (!str($class)->contains('\\')) {
            warning("<span class='font-bold'>$class</span> is not a fully qualified class name. Please try something line <span class='font-bold'>plugins:manage --add=Expose\\\Client\\\Logger\\\Plugins\\\MyCustomPlugin</span>.");
            return;
        }

        $pluginsToEnable = collect($this->pluginManager->getEnabledPlugins())
            ->prepend($class)
            ->unique()
            ->values()
            ->toArray();

        $this->pluginManager->modifyPluginConfiguration($pluginsToEnable);
    }

}
