<?php

namespace Search\SphinxsearchBundle\Services\Ql;

/**
 * Description of PDOSphinxQL
 *
 * @author Vadim
 */
class PDOSphinxQL
{

    /**
     * PDO
     * @var \Doctrine\DBAL\Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * Construct.
     *
     * @param string $host
     * @param string $port
     * @param string $dbname
     */
    function __construct($host, $port, $dbname)
    {
	$config = new \Doctrine\DBAL\Configuration();
	$connectionParams = array(
	    'dbname' => $dbname,
	    'user' => '',
	    'password' => '',
	    'host' => $host,
	    'driver' => 'pdo_mysql',
	    'port' => $port
	);
	$this->conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    }

    /**
     * Replaces parameters for index
     *
     * @param string $index_name
     * @param array $params
     * @return integer
     */
    public function replace($index_name, array $params)
    {
	$prepared = $this->prepareParams($params, true);
	$sql = "REPLACE INTO " . $index_name . " (" . implode(',', array_keys($prepared)) . ") VALUES " . " (" . implode(',', $prepared) . ")";
	$query = $this->conn->prepare($sql);
	$query->execute();

	return $query->rowCount();
    }

    /**
     * Updates params for index
     *
     * @param string $index_name
     * @param array $params
     * @param array $where
     * @return integer
     * @throws \Exception
     */
    public function update($index_name, array $params, array $where)
    {
	if (0 == count($params) || 1 != count($where)) {
	    throw new \Exception('Invalid params count');
	}

	$prepared = $this->prepareParams($params, true);
	$preparedWhere = $this->prepareParams($where, false);
	$sql = "UPDATE " . $index_name . " SET ";
	foreach ($prepared as $k => $v) {
	    $sql .= " $k = $v ,";
	}

	$sql = rtrim($sql, ',');
	foreach ($preparedWhere as $k => $v) {
	    $sql .= " WHERE $k = $v";
	}

	$query = $this->conn->prepare($sql);
	$query->execute();

	return $query->rowCount();
    }

    /**
     * Deletes params for index
     *
     * @param type $rt_index
     * @param array $params
     * @return type
     * @throws \Exception
     */
    public function delete($rt_index, array $params)
    {
	if (1 !== count($params)) {
	    throw new \Exception('Invalid params count');
	}

	foreach ($params as $key => $value) {
	    $sql = "DELETE FROM " . $rt_index . " WHERE $key = " . (int) $value;
	}

	$query = $this->conn->prepare($sql);
	$query->execute();

	return $query->rowCount();
    }

    /**
     *
     * @param type $rt_index
     * @param type $idName
     * @return type
     */
    public function truncate($rt_index, $idName)
    {
        $limit = 1000;
        
        do {
            $sql = "SELECT $idName FROM " . $rt_index . " limit 1000 ";
            $query = $this->conn->prepare($sql);
            $query->execute();
            $ids = $query->fetchAll(\PDO::FETCH_COLUMN);
            echo " --- attempt to deleting about ".count($ids)." records from rt index".PHP_EOL;
            foreach ($ids as $id) {
                $this->delete($rt_index, array($idName => $id));
            }
        } while ($limit == count($ids));
	return;
    }

    /**
     *
     * @param array $params
     * @return type
     */
    private function prepareParams(array $params, $escapeColumn = false)
    {
        $esc = $escapeColumn?'`':'';            
	$prepared = array();
	foreach ($params as $k => $v) {
	    if (is_array($v)) {
		$prepared[$esc.$k.$esc] = " (" . implode(',', array_map(function($in) {
					    return (int) $in;
					}, $v)) . ") ";
	    } elseif (is_int($v)) {
		$prepared[$esc.$k.$esc] = $v;
	    } else {
		$prepared[$esc.$k.$esc] = $this->conn->quote($v);
	    }
	}

	return $prepared;
    }

}
