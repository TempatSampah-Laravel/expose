<?php

namespace Expose\Client\Commands;


use Expose\Client\Commands\Support\ValidateExposeToken;
use Expose\Client\Contracts\FetchesPlatformDataContract;
use Expose\Client\Support\TokenNodeVisitor;
use Expose\Client\Traits\FetchesPlatformData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

use function Expose\Common\banner;
use function Expose\Common\error;
use function Expose\Common\info;
use function Expose\Common\success;
use function Laravel\Prompts\text;

class StoreAuthenticationTokenCommand extends Command implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected $signature = 'token {token?}';

    protected $description = 'Set the authentication token to use with Expose.';

    protected ?string $token = '';

    protected bool $newSetup = false;

    public function handle()
    {
        $this->token = $this->argument('token');
        $this->newSetup = empty(config('expose.auth_token'));

        if (empty($this->token) && !$this->newSetup) {
            return $this->call('token:get', ['--no-interaction' => $this->option('no-interaction')]);
        }

        if (!$this->option('no-interaction')) {
            banner();
        }

        if (empty($this->token)) {
            info("There is no authentication token specified yet.", newLine: true);
            info("If you don't have a token, visit <a href='https://expose.dev'>expose.dev</a> to create your free account.");
            $this->token = text(
                label: 'Expose token',
                required: true,
            );
        }

        if ($this->exposeToken()->isInvalid()) {

            if (!$this->option('no-interaction')) {
                error("Token $this->token is invalid. Please check your token and try again. If you don't have a token, visit <a href='https://expose.dev'>expose.dev</a> to create your free account.");

                if ($this->exposeToken()->hasError() && $this->getOutput()->isVerbose()) {
                    info();
                    info($this->exposeToken()->getError());
                }

                return 1;
            } else {
                $this->fail("Token $this->token is invalid. Please check your token and try again.");
            }
        }

        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.expose',
            'config.php',
        ]);

        if (!file_exists($configFile)) {
            @mkdir(dirname($configFile), 0777, true);
            $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/expose.php'), $this->token);
        } else {
            $this->rememberPreviousSetup();
            $updatedConfigFile = $this->modifyConfigurationFile($configFile, $this->token);
        }

        file_put_contents($configFile, $updatedConfigFile);

        if (!$this->option('no-interaction')) {

            info("Setting up new Expose token <span class='font-bold'>$this->token</span>...");

            (new SetupExposeProToken)($this->token);
        } else {
            if(!$this->isProToken()) {
                Artisan::call("default-server free");
                Artisan::call("default-domain:clear", ['--no-interaction' => true]);
            }
            info("Token set to $this->token.");
        }

        if ($this->newSetup) {
            info();
            success("🎉 You're all set! Share your first site by running `expose` in your site's directory.");
            info();
            info("If you want to learn more about how to use Expose, checkout the <a href='https://expose.dev/docs/getting-started/sharing-your-first-site'>documentation</a>.");
        }

    }

    protected function rememberPreviousSetup(): void
    {

        $previousSetup = [
            'token' => config('expose.auth_token'),
            'default_server' => config('expose.default_server'),
            'default_domain' => config('expose.default_domain'),
        ];

        $previousSetupPath = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.expose',
            'previous_setup.json',
        ]);

        if (!file_exists($previousSetupPath)) {
            fopen($previousSetupPath, 'w');
        }

        file_put_contents($previousSetupPath, json_encode($previousSetup));
    }

    protected function modifyConfigurationFile(string $configFile, string $token)
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine',
                'endLine',
                'startTokenPos',
                'endTokenPos',
            ],
        ]);
        $parser = new Php7($lexer);

        $oldStmts = $parser->parse(file_get_contents($configFile));
        $oldTokens = $lexer->getTokens();

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new TokenNodeVisitor($token));

        $newStmts = $nodeTraverser->traverse($newStmts);

        $prettyPrinter = new Standard();

        return $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
