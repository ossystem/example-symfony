<?php

namespace Search\SphinxsearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Search\SphinxsearchBundle\Command\SphinxIndexerCommand
 */
class SphinxIndexerCommand extends ContainerAwareCommand
{

    protected $quiet = false;
    protected $output;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
	$this->setName('sphinx:indexer')
		->setDescription('Rotate configured indexes')
		->setDefinition(array(
		    new InputArgument('index', InputArgument::OPTIONAL, 'index names comma separated, if no spesified all indexes will rotated'),
			)
	);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
	$index = $input->getArgument('index');
	$this->quiet = $input->getOption('quiet');
	$this->output = $output;
	if (!$index) {
	    $this->log('Rotate all index.');
	    $this->log($this->getContainer()->get('search.sphinxsearch.indexer')->rotateAll());
	} else {
	    $this->log($this->getContainer()->get('search.sphinxsearch.indexer')->rotate(preg_split("/[\s,]+/", $index)));
	}
    }

    /**
     * Write a message to the output
     *
     * @param string $message
     *
     * @return void
     */
    protected function log($message)
    {
	if (false === $this->quiet) {
	    $this->output->writeln($message);
	}
    }

}
