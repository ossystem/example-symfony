<?php

namespace Search\SphinxsearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Search\SphinxsearchBundle\Command\SphinxConfigRunCommand
 */
class SphinxConfigRunCommand extends ContainerAwareCommand
{

    protected $quiet = false;
    protected $output;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('sphinx:config:run')
             ->setDescription('Restart sphinx');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->quiet = $input->getOption('quiet');
        $this->output = $output;
        $kernel_dir = $this->getContainer()->get('kernel')->getRootDir();

        $conf_path = $kernel_dir.'/config/sphinx-rada-dev.conf';
        if (!file_exists($conf_path))
            return $this->log('Not found: '.$conf_path);
        $c1 = '/usr/bin/searchd -c '.$conf_path.' --stop';
        $c2 = '/usr/bin/searchd -c '.$conf_path;
        $this->log('Exec '.$c1);
        system ($c1);
        sleep(2);
        $this->log('Exec '.$c2);
        system ($c2);
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
