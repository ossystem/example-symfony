<?php

namespace Search\SphinxsearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Search\SphinxsearchBundle\Command\SphinxIndexerCommand
 */
class SphinxConfigGeneratorCommand extends ContainerAwareCommand
{

    protected $quiet = false;
    protected $output;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('sphinx:config:generate')
            ->setDescription('Creates config in app/config dirrectory')
            ->setDefinition(array(
                    new InputArgument('ondisk', InputArgument::OPTIONAL, 'If ram not enough set it to true'),
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->quiet = $input->getOption('quiet');
        $ondisk_dict = $input->getArgument('ondisk');
        $this->output = $output;
        $kernel_dir = $this->getContainer()->get('kernel')->getRootDir();

        $this->log('Read '.$kernel_dir.'/config/parameters.yml');
        $data_dir = realpath($kernel_dir.'/../sphinxdata');
        $this->log('Sphinx data dir: '.  $data_dir);
        $daemon_port = $this->getContainer()->getParameter('sphinxsearch_port');
        $ql_port = $this->getContainer()->getParameter('sphinxql_port');
        $this->log('Spinx daemon ports: '.$daemon_port.', '.$ql_port);

        $database_host = $this->getContainer()->getParameter('database_host');
        $database_name = $this->getContainer()->getParameter('database_name');
        $database_user = $this->getContainer()->getParameter('database_user');
        $database_password = $this->getContainer()->getParameter('database_password');
        $this->log('DB: '.$database_user.':'.$database_password.'@'.$database_host.'/'.$database_name);

        $this->log('Write option ondisk_dict is '.($ondisk_dict?'enabled':'disabled'));

        $template = file_get_contents($kernel_dir.'/config/sphinx-rada.template');

        $conf = str_replace(
            array('*sql_host*',  '*sql_user*',  '*sql_pass*',      '*sql_db*',  '*ondisk_dict*',       '*sphinxdata-dir*', '*daemon_port*', '*daemon_ql_port*'),
            array($database_host,$database_user,$database_password,$database_name,$ondisk_dict?'1':'0',$data_dir, $daemon_port, $ql_port),
            $template
        );

        $conf_path_dev = $kernel_dir.'/config/sphinx-rada-dev.conf';
        $conf_path_prod = $kernel_dir.'/config/sphinx-rada-prod.conf';
        $this->log('Writing to '.$conf_path_dev);
        if (!file_put_contents($conf_path_dev, $conf))
            $this->log('Writing to '.$conf_path_dev.' FAILED');
        $this->log('Writing to '.$conf_path_prod);
        if (!file_put_contents($conf_path_prod, $conf))
            $this->log('Writing to '.$conf_path_prod.' FAILED');


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
