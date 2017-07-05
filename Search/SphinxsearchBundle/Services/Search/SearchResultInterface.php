<?php

namespace Search\SphinxsearchBundle\Services\Search;

/**
 * Search result from a *single* index. This only contains
 * the current *range* of results
 */
interface SearchResultInterface
{

    /**
     * Get index name
     */
    function getIndexName();

    /**
     * Get total search result found
     */
    function getTotalFound();

    /**
     * Get current search result count
     */
    function getCurrentFound();

    /**
     * Get current matches
     */
    function getMatches();
}
