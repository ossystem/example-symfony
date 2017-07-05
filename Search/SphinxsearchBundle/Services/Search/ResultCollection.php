<?php

namespace Search\SphinxsearchBundle\Services\Search;

use Doctrine\ORM\EntityManager;

/**
 * Search\SphinxsearchBundle\Services\Search\ResultCollection
 */
class ResultCollection implements CollectionInterface
{

    /**
     * Array of SearchResultInterface
     *
     * @var Array
     */
    private $results;

    /**
     * Construct
     *
     * @param type $rawResults
     * @param \Search\SphinxsearchBundle\Services\Search\MappingCollection $mapping
     * @param \Doctrine\ORM\EntityManager $em
     * @param type $options
     */
    public function __construct($rawResults, MappingCollection $mapping = null, EntityManager $em = null, $options)
    {
	// echo 'result_collect';
	foreach ($rawResults as $indexName => $result) {
	    $this->results[$indexName] = new IndexSearchResult($indexName, $result, $mapping, $em, $options);
	}
    }

    /**
     * Gets iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
	return new \ArrayIterator($this->results);
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function count()
    {
	return count($this->results);
    }

    /**
     * {@inheritdoc}
     *
     * @param $indexName
     * @return IndexSearchResult
     */
    public function get($indexName)
    {
	return $this->results[$indexName];
    }

}

