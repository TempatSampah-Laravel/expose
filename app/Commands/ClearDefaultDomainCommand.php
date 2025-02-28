<?php

namespace Expose\Client\Commands;


use Expose\Client\Support\ClearDomainNodeVisitor;
use Expose\Client\Support\InsertDefaultDomainNodeVisitor;
use Illuminate\Console\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

use function Expose\Common\banner;
use function Expose\Common\info;


class ClearDefaultDomainCommand extends Command
{


    protected $signature = 'default-domain:clear';

    protected $description = 'Clear the default domain to use with Expose.';

    public function handle()
    {

        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.expose',
            'config.php',
        ]);

        if (! file_exists($configFile)) {
            @mkdir(dirname($configFile), 0777, true);
            $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/expose.php'));
        } else {
            $updatedConfigFile = $this->modifyConfigurationFile($configFile);
        }

        file_put_contents($configFile, $updatedConfigFile);

        if(!$this->option('no-interaction')) {
            banner();
            info("✔ Cleared the Expose default domain.");
        }
    }

    protected function modifyConfigurationFile(string $configFile)
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            ],
        ]);
        $parser = new Php7($lexer);

        $oldStmts = $parser->parse(file_get_contents($configFile));
        $oldTokens = $lexer->getTokens();

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeFinder = new NodeFinder;

        $defaultDomainNode = $nodeFinder->findFirst($newStmts, function (Node $node) {
            return $node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'default_domain';
        });

        if (is_null($defaultDomainNode)) {
            $nodeTraverser = new NodeTraverser;
            $nodeTraverser->addVisitor(new InsertDefaultDomainNodeVisitor());
            $newStmts = $nodeTraverser->traverse($newStmts);
        }

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new ClearDomainNodeVisitor());

        $newStmts = $nodeTraverser->traverse($newStmts);

        $prettyPrinter = new Standard();

        return $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }
}
