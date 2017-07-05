<?php

namespace Search\SphinxsearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Search\SphinxsearchBundle\DependencyInjection\SphinxsearchExtension
 */
class SphinxsearchExtension extends Extension
{

    /**
     * {@inheritdoc}
     *
     * @param array $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
	$processor = new Processor();
	$configuration = new Configuration();
	$config = $processor->processConfiguration($configuration, $configs);
	$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
	$loader->load('sphinxsearch.xml');
	$loader->load('paginate.xml');
	/**
	 * Indexer.
	 */
	if (isset($config['indexer'])) {
	    $container->setParameter('search.sphinxsearch.indexer.bin', $config['indexer']['bin']);
	}

	/**
	 * Indexes.
	 */
	$container->setParameter('search.sphinxsearch.indexes', $config['indexes']);
	$container->setParameter('search.sphinxsearch.mapping', $config['mapping']);
	/**
	 * Searchd.
	 */
	if (isset($config['searchd'])) {
	    $container->setParameter('search.sphinxsearch.searchd.host', $config['searchd']['host']);
	    $container->setParameter('search.sphinxsearch.searchd.port', (int) $config['searchd']['port']);
	    $container->setParameter('search.sphinxsearch.searchd.socket', $config['searchd']['socket']);
	}

	/**
	 * Ql.
	 */
	if (isset($config['ql'])) {
	    $container->setParameter('search.sphinxsearch.ql.host', $config['ql']['host']);
	    $container->setParameter('search.sphinxsearch.ql.port', (int) $config['ql']['port']);
	    $container->setParameter('search.sphinxsearch.ql.socket', $config['ql']['socket']);
	}
    }

    /**
     * Returns alias name
     *
     * @return string
     */
    public function getAlias()
    {
	return 'sphinxsearch';
    }

}
