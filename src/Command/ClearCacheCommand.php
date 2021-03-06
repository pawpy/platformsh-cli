<?php

namespace Platformsh\Cli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCacheCommand extends CommandBase
{
    protected $local = true;

    protected function configure()
    {
        $this
            ->setName('clear-cache')
            ->setAliases(['clearcache', 'cc'])
            ->setDescription('Clear the CLI cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->api->clearCache();
        $this->stdErr->writeln("All caches have been cleared");
    }
}
