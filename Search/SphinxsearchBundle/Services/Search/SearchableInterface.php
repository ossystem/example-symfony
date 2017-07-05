<?php

namespace Search\SphinxsearchBundle\Services\Search;

interface SearchableInterface
{

	/**
	 * @abstract
	 * @return string
	 */
	public function getSearchLabel();

	/**
	 * @abstract
	 * @return string
	 */
	public function getSearchDescription();

	/**
	 * @abstract
	 * @return string
	 */
	public function getSearchRoute();

	/**
	 * @abstract
	 * @return array
	 */
	public function getSearchRouteParameters();
}
