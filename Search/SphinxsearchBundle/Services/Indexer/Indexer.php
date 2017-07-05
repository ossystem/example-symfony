<?php

namespace Search\SphinxsearchBundle\Services\Indexer;

use Symfony\Component\Process\ProcessBuilder;
use Search\SphinxsearchBundle\Services\Search\MappingCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Search\SphinxsearchBundle\Services\Indexer\Indexer
 */
class Indexer
{

    /**
     * @var string $bin
     */
    private $bin;

    /**
     * mapping info
     * array(
     *   'IndexName' => array(
     *         rt_name => 'RT Index Name'
     *   )
     * )
     * @var array
     */
    private $mapping;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Constructor.
     *
     * @param string $bin The path to the indexer executable.
     * @param array $indexes The list of indexes that can be used.
     */
    public function __construct($bin = '/usr/bin/indexer', array $mapping = array(), ContainerInterface $container)
    {
	$this->bin = $bin;
	$this->mapping = new MappingCollection($mapping);
	$this->container = $container;
    }

    /**
     * Rebuild and rotate all indexes.
     */
    public function rotateAll()
    {
	$this->rotate(array_keys($this->mapping->toArray()));
    }

    /**
     * Rebuild and rotate the specified index(es).
     *
     * @param array|string $indexes The index(es) to rotate.
     */
    public function rotate($indexes)
    {
	$pb = new ProcessBuilder();
	$pb->inheritEnvironmentVariables();
	$bin_params = preg_split("/[\s,]+/", $this->bin);
	if (1 > count($bin_params)) {
	    throw new \RuntimeException('No binary specified');
	}

	$pb->add(array_shift($bin_params));
	while ($bin_params) {
	    $pb->add(array_shift($bin_params));
	}

	$pb->add('--rotate');
	$relativeRtNames = array();
	if (is_array($indexes)) {
	    foreach ($indexes as &$indexName) {
		if (isset($this->mapping[$indexName])) {
		    $pb->add($indexName);
		    if (isset($this->mapping[$indexName]['rt_name'])) {
			$relativeRtNames[] = $this->mapping[$indexName]['rt_name'];
		    }
		}
	    }
	} elseif (is_string($indexes)) {
	    if (isset($this->mapping[$indexes])) {
		$pb->add($indexes);
		if (isset($this->mapping[$indexes]['rt_name'])) {
		    $relativeRtNames[] = $this->mapping[$indexes]['rt_name'];
		}
	    }
	} else {
	    throw new \RuntimeException(sprintf('Indexes can only be an array or string, %s given.', gettype($indexes)));
	}
	/**
	 * FIXME: Throw an error if no valid indexes were provided?
	 */
	$indexer = $pb->getProcess();
	$indexer->setTimeout(6000);
	$code = $indexer->run();
        //tmp
        print_r($indexer->getOutput());
	if (false !== ($errStart = strpos($indexer->getOutput(), 'FATAL:'))) {
	    if (false !== ($errEnd = strpos($indexer->getOutput(), "\n", $errStart))) {
		$errMsg = substr($indexer->getOutput(), $errStart, $errEnd);
	    } else {
		$errMsg = substr($indexer->getOutput(), $errStart);
	    }

	    throw new \RuntimeException(sprintf('Error rotating indexes: "%s".', rtrim($errMsg)));
	}

	foreach ($relativeRtNames as $rtName) {
	    $this->container->get('search.sphinxsearch.rtindexer')->truncateRtIndex($rtName);
	}

	return $indexer->getOutput();
    }

}
