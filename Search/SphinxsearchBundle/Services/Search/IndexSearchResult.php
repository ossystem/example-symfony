<?php

namespace Search\SphinxsearchBundle\Services\Search;

use \Doctrine\ORM\EntityManager;
use \Doctrine\Common\Collections\ArrayCollection;
use Search\SphinxsearchBundle\Services\Exception\MappingException;

class IndexSearchResult implements SearchResultInterface
{

	/**
	 * @var string
	 */
	private $indexName;

	/**
	 * @var array
	 */
	private $rawResults;

	/**
	 * @var int
	 */
	private $totalFound;

	/**
	 * @var array
	 */
	private $matches;

	/**
	 * @var MappingCollection
	 */
	private $mapping;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 *
	 * @param string $indexName
	 * @param array $rawResults
	 * @param \Search\SphinxsearchBundle\Services\Search\MappingCollection $mapping
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param array $options
	 */
	public function __construct($indexName, $rawResults, MappingCollection $mapping = null, EntityManager $em = null, $options)
	{

		$this->rawResults = $rawResults;
		$this->indexName = $indexName;
		$this->totalFound = $rawResults['total_found'];
		$this->mapping = $mapping;
		$this->em = $em;

		// Normalize sphinxsearch result array
		if (array_key_exists('matches', $rawResults)) {
			$rawMatches = $rawResults['matches'];
			$this->matches = array();
			foreach ($rawMatches as $id => $match) {
				$match['attrs']['id'] = $id;
				$this->matches [] = $match;
			}
		} else {
			$this->matches = array();
		}
		$this->pagerOptions = $options;
	}

	/**
	 * Get pager option
	 *
	 * @return string
	 */
	public function getPagerLimit()
	{
		return $this->pagerOptions['limit'];
	}

	/**
	 * Get pager option
	 *
	 * @return string
	 */
	public function getPagerPage()
	{
		return $this->pagerOptions['page'];
	}

	/**
	 * Get indexName
	 *
	 * @return string $indexName
	 */
	public function getIndexName()
	{
		return $this->indexName;
	}

	/**
	 * Get totalFound
	 *
	 * @return integer $totalFound
	 */
	public function getTotalFound()
	{
		return $this->totalFound;
	}

	/**
	 * Get count matches
	 *
	 * @return integer
	 */
	public function getCurrentFound()
	{
		return count($this->matches);
	}

	/**
	 * Get matches
	 *
	 * @return array $matches
	 */
	public function getMatches()
	{
		return $this->matches;
	}

	/**
	 * @return ArrayCollection will return collection of objects if it matched them
	 */
	public function getMappedMatches()
	{
		$mapping = $this->mapping;
		$parameters = $mapping->getAvailableParameters();
		$matches = $this->matches;
		$Result = new ArrayCollection();

		foreach ($matches as $match) {
			$attrs = array_keys($match['attrs']);
			$matchedAttrs = array_intersect($attrs, $parameters);
			if (!count($matchedAttrs)){
				continue;
			}

			foreach ($matchedAttrs as $matchedAttr) {
				$value = $match['attrs'][$matchedAttr];
				$repoName = $mapping->findRepository($matchedAttr, $value);
				if ($repoName) {
					$repo = $this->em->getRepository($repoName);
					$element = $repo->findOneById($match['attrs']['id']);
					if ($element) {
						if ($element instanceof SearchableInterface) {
							$Result->add($element);
						} else {
							throw new MappingException(sprintf('Object "%s" don\'t implements interface "SearchableInterface".', get_class($element)));
						}
					}
				}
			}
		}

		return $Result;
	}

	/**
	 * @return ArrayCollection will return collection of objects if it matched them
	 */
	public function getMappedGroupedMatches()
	{
		$mapping = $this->mapping;
		$parameters = $mapping->getAvailableParameters();
		$matches = $this->matches;
		//$Result = new ArrayCollection();
		$Result = array();
		$idsByModel = array();
		foreach ($matches as $match) {
			$attrs = array_keys($match['attrs']);
			$matchedAttrs = array_intersect($attrs, $parameters);
			if (!count($matchedAttrs)){
				continue;
			}

			foreach ($matchedAttrs as $matchedAttr) {
				$value = $match['attrs'][$matchedAttr];
				$repoName = $mapping->findRepository($matchedAttr, $value);
				if ($repoName) {

					if (!isset($idsByModel[$repoName])) {
						$idsByModel[$repoName] = array();
					}

					$idsByModel[$repoName][] = $match['attrs']['id'];
				}
			}
		}

		foreach ($idsByModel as $repoName => $ids) {
			$repo = $this->em->getRepository($repoName);
			$qb = $this->em->createQueryBuilder();
			$selectResult = $qb->select('r, FIELD(r.id, ' . implode(', ', $ids) . ') AS HIDDEN FIELDIDS ')
							->from($repoName, 'r') // todo push id name
							->where($qb->expr()->in('r.id', $ids))
							->orderBy('FIELDIDS')
							->getQuery()->getResult();
			foreach ($selectResult as $entity)
				$Result[] = $entity;
		}
		return $Result;
	}

}
