<?php

namespace Search\SphinxsearchBundle\Services\Search;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Search\SphinxsearchBundle\Services\Search\MappingCollection
 */
class MappingCollection extends ArrayCollection
{

    /**
     * @return array
     */
    public function getAvailableParameters()
    {
        $parameters = array();
        foreach ($this->toArray() as $element) {
            if (!in_array($element['parameter'], $parameters)) {
                $parameters[] = $element['parameter'];
            }
        }

        return $parameters;
    }

    /**
     * @param $parameter
     * @param $value
     * return string Repository name
     */
    public function findRepository($parameter, $value)
    {
        foreach ($this->toArray() as $element) {
            if ($element['parameter'] == $parameter && $element['value'] == $value) {
                return $element['repository'];
            }
        }

        return false;
    }

    /**
     * @param $name
     * return array Repository Index Params
     */
    public function getEntityIndexParams($name)
    {
        foreach ($this->toArray() as $key => $element) {
            if (
                substr($element['repository'], strrpos($element['repository'], ':') + 1) == $name ||
                substr($element['repository'], strrpos($element['repository'], '\\') + 1) == $name
            ) {
                return array_merge($this->get($key), array('index' => $key));
            }
        }
        return null;
    }

    /**
     * @param $name
     * return array Repository Index Params
     */
    public function getIndexDeleteAttr($indexName)
    {
        if ($indexParam = $this->get($indexName)) {
            if (isset($indexParam['delete_attr'])) {
                return $indexParam['delete_attr'];
            }
        }

        return null;
    }

}
