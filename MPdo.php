<?php
/**
 * MPdo class
 */
class MPdo
{
    /**
     * Configure file variables
     * @var array
     */
    private $master_conf ;
    private $slave_conf ;
    
    //PDO instance
    protected $master ;
    protected $slave ;

    /**
     * Construct function just set config file
     *
     * @param mixed $config database config
     * @return null
     */
    function __construct($config){
        $this->master_conf  = $config['master'] ;
        if(isset($config['slave'])) {
            $this->slave_conf = $config['slave'] ;
        } else {
            $this->slave_conf = array() ;
        }
    }
    
    /**
     * connect database
     * @param  boolean $is_master
     * @return pdo
     */
    private function connect($db_conf)
    {
        if(!isset($db_conf['charset'])) {
            $db_conf['charset'] = 'utf8' ;
        }
        //pdo setting
        $dsn = sprintf("mysql:host=%s;dbname=%s;port=%s;charset=%s",$db_conf['host'],$db_conf['database'],$db_conf['port'],$db_conf['charset']) ;
        try {
            $dbh = new PDO($dsn, $db_conf['user'], $db_conf['pass']) ;
            $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            exit("Connect DB fail:".$e->getMessage()) ;
        }
        $dbh->exec("set names utf8");
        return $dbh ;
    }
    
    /**
     * Get pdo instance
     * @param  bool     $is_master 
     * @return object
     */
    public function getDbh($is_master = false)
    {
        //while slave conf is not set use master instead
        if($is_master || empty($this->slave_conf)) {
            if(is_object($this->master) && $this->master->query('do 1')) {
                return $this->master ;
            } else {
                $this->close($is_master) ;
                $this->master = $this->connect($this->master_conf) ;
                return $this->master ;
            }
        } else {
            if(is_object($this->slave) && $this->slave->query('do 1')) {
                return $this->slave ;
            } else {
                $this->close($is_master) ;
                $this->slave = $this->connect($this->slave_conf) ;
                return $this->slave ;
            }
        }
    }

    /**
     * Close pdo instances
     * @return null
     */
    public function close($is_master= false)
    {
        if($is_master || empty($this->slave_conf)) {
            unset($this->master) ;
        } else {
            unset($this->slave) ;
        }
    }
    
    /**
     * Excutes a sql statement
     *
     * @desc for Select only
     * @param  string   $sql
     * @return  a result set as a PDOStatement object    
     */
    public function query($sql)
    {
        $result = array() ;
        $dbh    = $this->getDbh(false) ;
        //Prepare Sql excute
        try {
            $sth = $dbh->prepare($sql);
            $sth->execute() ;
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit('PDO execute failed:'.$e->getMessage()) ;
        }
        return $result ;
    }

    /**
     * Execute a sql and return affect rows
     *
     * @desc    for Insert/Delete/Update
     * @param   string  $sql
     * @return  int     affect rows
     */
    public function exec($sql)
    {
        $affectRows = 0 ;
        $dbh = $this->getDbh(true) ;
        try {
            $affectRows = $this->exec($sql) ;
        } catch (PDOException $e) {
            exit('PDO exec failed:'.$e->getMessage()) ;
        }
        return $affectRows ;
    }
     
    /**
     * Get assoc result for select
     * 
     * @param  string $table   
     * @param  string $fields  
     * @param  string $sqlwhere
     * @param  string $orderby 
     * @return array       
     */
    public function select($table, $fields, $conds, $orderby='', $pages=array())
    {
        $fconds = $forder = $flimit = '' ;
        //Format fields
        $fields = $this->formatSet($fields) ;
        //format conditions
        if(!empty($conds)) {
            $fconds = 'and '.$this->formatSet($conds,' and ') ;
        }
        //Format order
        if(!empty($orderby)) {
            $order = $this->formatOrder($orderby) ;
            $forder = 'order by' . $order ;
        }
        //Format pages
        if(!empty($pages) && isset($pages['page'])) {
            if(!isset($pages['size'])) {
                $pages['size'] = 100 ;
            }
            $offset = ($pages['page'] - 1) * $pages['size'] ;
            $flimit = "limit $offset, ${pages['size']}" ;
        }
        $selectSql = "select $fields from $table where 1=1 $fconds $forder $flimit" ;
        return $this->query($selectSql) ;
    }
    
    /**
     * Get Single Record From DB
     *
     * @desc be sure there is only one record, or the first one will be choosed
     * @param  string   $table      table name
     * @param  mixed    $fields     select fields
     * @param  mixed    $conds      select conditions
     * @return array
     */
    public function getOne($table, $fields , $conds)
    {
        $formatCond = '' ;
        $res = array() ;
        $fields = $this->formatSet($fields);
        if(!empty($conds)) {
            $formatCond = ' and '.$this->formatSet($conds, ' and ') ;
        }
        $oneSql = "select $fields from $table where 1=1 $formatCond" ;
        $result = $this->query($oneSql) ;
        if(!empty($result)) {
            $res = $result[0] ;
        }
        return $res ;
    }

    /**
     * Get all records match the condition
     * 
     * @param  string   $table  
     * @param  mixed    $fields 
     * @param  mixed    $conds
     * @return array
     */
    public function getAll($table, $fields, $conds)
    {
        $formatCond = '' ;
        $result     = array() ;
        $fields     = $this->formatSet($fields) ;
        if(!empty($conds)) {
            $formatCond = ' and '.$this->formatSet($conds, ' and ') ;
        }
        $allSql = "select $fields from $table where 1=1 $formatCond" ;
        return $this->query($allSql) ;
    }

    /**
     * Insert record into table (Single)
     * 
     * @param   string  $table
     * @param   mixed   $set
     * @return  int     last_insert_id
     */
    public function insert($table, $set)
    {
        $fset = $this->formatSet($set) ;
        $insertSql = "insert into $table set $fset" ;
        $dbh  = $this->getDbh(true) ;
        try {
            $sth = $dbh->prepare($insertSql);
            $sth->execute() ;
            return $dbh->lastInsertId() ;
        } catch (PDOException $e) {
            exit('PDO insert record failed:'.$e->getMessage()) ;
        }
    }
     
    /**
     * update operation
     * 
     * @param  string   $table
     * @param  mixed    $set
     * @param  array    $cond
     * @return int      affect rows
     */
    public function update($table, $set, $cond="")
    {
        $fcond= '' ;
        $fset = $this->formatSet($set) ;
        if(!empty($cond)) {
            $fcond = ' and '.$this->formatSet($cond , ' and ');
        }
        $updateSql = "update $table set $fset where 1=1 $fcond" ;
        return $this->exec($updateSql) ;
    }
    
    /**
     * Delete table record
     * 
     * @param  string   $table  table name
     * @param  array    $cond   delete condition / not null
     * @return 
     */
    public function delete($table, $cond)
    {
        //Delete table is disallowed
        if(empty($cond)) {
            return false ;
        }
        $fcond = ' and ' . $this->formatSet($cond , ' and ');
        $deleteSql = "delete from $table where 1=1 $fcond" ;
        return $this->exec($deleteSql) ;
    }

    /**
     * Format set for database using 
     * 
     * @param  array    $set    db conditions
     * @param  string   $glue   set join glue
     * @return string
     */
    private function formatSet($set , $glue=',')
    {
        if(is_array($set) && !empty($set)) {
            $result = array() ;
            foreach ($set as $key => $val) {
                if(is_int($key) && is_array($val)) {
                    $result = array_merge($result,$val) ;
                } else if(is_array($val)) {
                    $result[] = "$key in ('".implode("','",$val)."')" ;
                } else {
                    $result[] = "$key='${val}'" ;    
                }
            }
            $set = implode($glue, $result) ;
        }
        return $set ;
    }

    /**
     * Format order
     * 
     * @param  mixd $order  order string or order assoc array
     * @return string
     */
    private function formatOrder($order) 
    {
        $forder = '' ;
        if(!empty($order) && is_array($order)) {
            $orderby = array() ;
            foreach($order as $fd => $ord) 
            {
                $orderby[] = "$fd $ord" ;
            }
            $order = implode(',', $orderby) ;
        }
        return $order ;
    }

    public function __destruct()
    {
        if(is_object($this->master)) {
            $this->master = null ;
        }

        if(is_object($this->slave)) {
            $this->slave  = null ;
        }
    }
}