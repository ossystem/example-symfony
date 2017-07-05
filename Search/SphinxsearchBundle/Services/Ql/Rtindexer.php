<?php

namespace Search\SphinxsearchBundle\Services\Ql;

use Assetic\Util\ProcessBuilder;
use Search\SphinxsearchBundle\Services\Search\MappingCollection;

/**
 * Search\SphinxsearchBundle\Services\Ql\Rtindexer
 */
class Rtindexer
{
	/**
         * pdo
	 * @var Search\SphinxsearchBundle\Services\Ql\PDOSphinxQl
	 */
	private $pdo;

	/**
         * mapping info
         * @var array
         */
	private $mapping;

	/**
	 * Constructor.
	 *
	 * @param string $bin The path to the indexer executable.
	 * @param array $indexes The list of indexes that can be used.
	 */
	public function __construct($host = 'localhost', $port = '9312', $socket = null, array $mapping = array())
	{
		$this->pdo     = new PDOSphinxQL($host,$port,'');
		$this->mapping = new MappingCollection($mapping);
	}

	/**
	 *
	 * @param type $entity
	 */
        public function replaceEntity($entity){
            $className = get_class($entity);
            $indexParams = $this->mapping->getEntityIndexParams(substr($className, strrpos($className, '\\')+1));
            $replaceParams = array();
            if (isset($indexParams['rt_fields']) && isset($indexParams['rt_name'])){
                foreach($indexParams['rt_fields'] as $fieldName => $fieldParams){
                    switch ($fieldParams['type']) {
                        case 'attr_multi':
                            $array_entities = $entity->{'get'.ucfirst($fieldParams['map'])}();
                            $array_ids = array();
                            if(count($array_entities)) {
				foreach($array_entities as $item){
				    $array_ids[] = $item->getId();
				}
			    }
                            $replaceParams[$fieldName] = $array_ids;
                            break;
                        case 'attr_timestamp':
                            $val = $entity->{'get'.ucfirst($fieldParams['map'])}();
                            if (is_object($val)){
                               $val = $val->getTimestamp();
                            }
                            $replaceParams[$fieldName] = (string)$val;
                            break;
                        case 'id':
                            $val = $entity->{'get'.ucfirst($fieldParams['map'])}();
                            if (is_object($val)){
                               $val = $val->getTimestamp();
                            }
                            $replaceParams[$fieldName] = (int)$val;
                            break;
                        default:
                            $val = $entity->{'get'.ucfirst($fieldParams['map'])}();
                            if (is_object($val)){
                                //todo: push id
                               $val = $val->getId();
                            }
                            $replaceParams[$fieldName] = $val;
                            break;
                    }
                }
                $replaceParams[$indexParams['parameter']] = $indexParams['value'];
                $this->pdo->replace($indexParams['rt_name'],$replaceParams);
                if (isset($indexParams['index']) && isset($indexParams['delete_attr']) && $indexParams['delete_attr'] && $entity->getId() ) {
                   $this->pdo->update($indexParams['index'],array($indexParams['delete_attr']=>1,),array('id'=>$entity->getId(),));
		}
            }
        }

	/**
	 *
	 * @param type $entity
	 */
        public function deleteEntity($entity){
            $className = get_class($entity);
            $indexParams = $this->mapping->getEntityIndexParams(substr($className, strrpos($className, '\\')+1));
            if (isset($indexParams['rt_name'])) {
                $this->pdo->delete($indexParams['rt_name'],array('id'=>$entity->getId(),));
	    }
            if (isset($indexParams['index']) && isset($indexParams['delete_attr']) && $indexParams['delete_attr'] && $entity->getId() ) {
                $this->pdo->update($indexParams['index'],array($indexParams['delete_attr']=>1, ),array('id'=>$entity->getId(), ));
	    }
        }

	/**
	 *
	 * @param string $name
	 */
        public function truncateRtIndex($name) {
            //todo: search id name
            $this->pdo->truncate($name, 'id');
        }
}
