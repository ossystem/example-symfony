<?php

namespace Search\SphinxsearchBundle\Services\Search;

use Search\SphinxsearchBundle\Services\Exception\ConnectionException;
use Doctrine\ORM\EntityManager;

/**
 * Search\SphinxsearchBundle\Services\Search\Sphinxsearch
 */
class Sphinxsearch
{

    /**
     * @var string $host
     */
    private $host;

    /**
     * @var string $port
     */
    private $port;

    /**
     * @var string $socket
     */
    private $socket;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var MappingCollection
     */
    private $mapping;

    /**
     * @var array $indexes
     *
     * $this->indexes should have the format:
     *
     * 	$this->indexes = array(
     * 		'IndexLabel' => array(
     * 			'index_name'	=> 'IndexName',
     * 			'field_weights'	=> array(
     * 				'FieldName'	=> (int)'FieldWeight',
     * 				...,
     * 			),
     * 		),
     * 		...,
     * 	);
     */
    private $indexes;

    /**
     * @var SphinxClient $sphinx
     */
    private $sphinx;

    /**
     * Constructor.
     *
     * @param string $host The server's host name/IP.
     * @param string $port The port that the server is listening on.
     * @param string $socket The UNIX socket that the server is listening on.
     * @param array $indexes The list of indexes that can be used.
     * @param array $mapping The list of mapping
     * @param \Doctrine\ORM\EntityManager $em  for db query
     */
    public function __construct($host = 'localhost', $port = '9312', $socket = null, array $indexes = array(), array $mapping = array(), EntityManager $em = null)
    {
	if (!class_exists('SphinxClient')) {
	    include_once ( dirname(__FILE__) . '/SphinxAPI.php');
	}

	$this->host = $host;
	$this->port = $port;
	$this->socket = $socket ? $socket : null;
	$this->indexes = $indexes;
	$this->em = $em;
	$this->mapping = new MappingCollection($mapping);
	$this->sphinx = new \SphinxClient();
	if (null !== $this->socket) {
	    $this->sphinx->setServer($this->socket);
	} else {
	    $this->sphinx->setServer($this->host, $this->port);
	}

	$doctrineConfig = $this->em->getConfiguration();
	$doctrineConfig->addCustomStringFunction('FIELD', 'Search\SphinxsearchBundle\Extension\Field');
    }

    /**
     * Set the desired match mode.
     *
     * @param int $mode The matching mode to be used.
     */
    public function setMatchMode($mode)
    {
	$this->sphinx->setMatchMode($mode);
    }

    /**
     * Sets sort mode
     *
     * @param type $mode
     * @param type $str
     */
    public function setSortMode($mode, $str)
    {
	$this->sphinx->setSortMode($mode, $str);
    }

    /**
     * Set the desired search filter.
     *
     * @param string $attribute The attribute to filter.
     * @param array $values The values to filter.
     * @param bool $exclude Is this an exclusion filter?
     */
    public function setFilter($attribute, $values, $exclude = false)
    {
	$this->sphinx->setFilter($attribute, $values, $exclude);
    }

    /**
     * Adds filter by values range
     *
     * @param type $attribute
     * @param type $min
     * @param type $max
     * @param boolean $exclude
     */
    public function setFilterRange($attribute, $min, $max, $exclude = false)
    {
	$this->sphinx->setFilterRange($attribute, $min, $max, $exclude = false);
    }

    /**
     * Adds filter by values range
     *
     * @param type $attribute
     * @param type $min
     * @param type $max
     * @param boolean $exclude
     */
    public function SetFilterFloatRange($attribute, $min, $max, $exclude = false)
    {
	$this->sphinx->SetFilterFloatRange($attribute, $min, $max, $exclude = false);
    }

    /**
     * Search for the specified query string.
     *
     * @param string $query The query string that we are searching for.
     * @param array $indexes The indexes to perform the search on.
     *
     * @return ResultCollection The results of the search.
     *
     * $indexes should have the format:
     *
     * 	$indexes = array(
     * 		'IndexLabel' => array(
     * 			'result_offset'	=> (int),
     * 			'result_limit'	=> (int)
     * 		),
     * 		...,
     * 	);
     */
    public function search($query, array $indexes)
    {
	// $query = $this->sphinx->escapeString($query);

	$results = array();
	foreach ($indexes as $label => $options) {
	    /**
	     * Ensure that the label corresponds to a defined index.
	     */
	    if (!isset($this->indexes[$label])) {
		continue;
	    }

	    if (isset($options['page']) && isset($options['limit']) && is_numeric($options['page']) && is_numeric($options['limit'])) {
		$options['result_offset'] = abs($options['page'] - 1) * $options['limit'];
		$options['result_limit'] = $options['limit'];
	    }
	    /**
	     * Set the offset and limit for the returned results.
	     */
	    if (isset($options['result_offset']) && isset($options['result_limit']) && is_numeric($options['result_offset']) && is_numeric($options['result_limit'])) {
		$this->sphinx->setLimits($options['result_offset'], $options['result_limit'], $options['result_offset']*2);
	    }

	    if (!isset($options['page'])) {
		$options['page'] = (int) ( $options['result_offset'] / $options['result_limit'] ) + 1;
	    }
	    if (!isset($options['limit'])) {
		$options['limit'] = $options['result_limit'];
	    }
	    /**
	     * Weight the individual fields.
	     */
	    if (!empty($this->indexes[$label]['field_weights'])) {
		$this->sphinx->setFieldWeights($this->indexes[$label]['field_weights']);
	    }

	    /**
	     * Eclude deleted records
	     */
	    foreach ($this->indexes[$label]["index"] as $indexName) {
		if ($delAttr = $this->mapping->getIndexDeleteAttr($indexName)) {
		    $this->sphinx->SetFilter($delAttr, array(0));
		}
	    }
	    /**
	     * Perform the query.
	     */
	    $results[$label] = $this->sphinx->query($query, implode(' ', $this->indexes[$label]["index"]));
	    if ($this->sphinx->IsConnectError()) {
		throw new ConnectionException(sprintf('Searching index "%s" for "%s" failed with error "%s".', $label, $query, $this->sphinx->getLastError()));
	    } elseif (SEARCHD_OK !== $results[$label]['status']) {
		throw new \RuntimeException(sprintf('Searching index "%s" for "%s" failed with error "%s".', $label, $query, $this->sphinx->getLastError()));
	    }
	}

	/**
	 * FIXME: Throw an exception if $results is empty?
	 */
	return new ResultCollection($results, $this->mapping, $this->em, $options);
    }

    /**
     * Escapes special chars
     *
     * @param type $string
     * @return type
     */
    public function escapeString($string)
    {
	return $this->sphinx->escapeString($string);
    }
    
    /**
     * get (k1 | *k1*) & (k2 | *k2*) & (k3 | *k3*)
     *
     * @param type $string
     * @return type
     */
    public function lowKeyword($sQuery)
    {
        $sQuery = str_replace(array('(',')'), array(' ',' '), $sQuery);
        $aRequestString=preg_split('/[\s,-]+/', $sQuery, 5);
        $aKeyword = array();
        if ($aRequestString) {
            foreach ($aRequestString as $sValue)
            {
                if (strlen($sValue)>1)
                {
                    $tryShortWord = preg_split('/[^\w]+/', $sValue, -1, PREG_SPLIT_NO_EMPTY);
                    $isShortWord = false;
                    foreach ($tryShortWord as $shortWord) {
                        if ( strlen($shortWord)<2 )
                        	$isShortWord = true;
                    }
                    $sEsc = $this->sphinx->escapeString($sValue);
                    
                    if ($isShortWord)
                        $aKeyword[] .= "(".$sEsc.")";
                    else
                        $aKeyword[] .= "(".$sEsc." | *".$sEsc."*)";
                }
            }
            $sSphinxKeyword = implode(" & ", $aKeyword);
        }
        return $sSphinxKeyword;
    }

}
