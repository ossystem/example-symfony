<?php

namespace Search\SphinxsearchBundle\Services\Search;

use Countable;
use IteratorAggregate;

/**
 * Search\SphinxsearchBundle\Services\Search\CollectionInterface
 */
interface CollectionInterface extends Countable, IteratorAggregate
{

    /**
     * Get index items by index name
     *
     * @param string Index name
     * @return SearchResultInterface
     */
    function get($indexName);
}
