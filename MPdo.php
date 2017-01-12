<?php
/**
 * MPdo class
 */
class MPdo
{
    /**
     * configure file
     * @var array
     */
    private $master_conf ;
    private $slave_conf ;
    
    protected $master ;
    protected $slave ;
    /**
     * Construct function 
     * @param mixed $config database config
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
        //pdo setting 
        $dsn = sprintf("mysql:host=%s;dbname=%s;port=%s;charset=%s",$db_conf['host'],$db_conf['database'],$db_conf['port'],$db_conf['charset']) ;
        $user= $db_conf['user'] ;
        $pass= $db_conf['pass'] ;
        try {
            $dbh = new PDO($dsn, $user, $pass) ;
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
     * @param  string   $sql
     * @return  a result set as a PDOStatement object    
     */
    public function query($sql)
    {
        //TODO
    }

    /**
     * Execute a sql and return affect rows
     *
     * @desc    for update
     * @param   string  $sql
     * @return  int     affect rows
     */
    public function exec($sql)
    {
        //TODO
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
    public function select($table, $fields , $conds, $orderby='', $pages=array())
    {
        $fconds = $forder = $flimit = '' ;
        //Format sql conditions
        $fields = $this->formatSet($fields) ;
        if(!empty($conds)) {
            $fconds = 'and '.$this->formatSet($conds,' and ') ;
        }
        //Get orderby condition
        if(!empty($orderby)) {
            $order = $this->formatOrder($orderby) ;
            $forder = 'order by' . $order ;
        }
        //Get data of pages
        if(!empty($pages) && isset($pages['page'])) {
            if(!isset($pages['pagesize'])) {
                $pages['pagesize'] = 100 ;
            }
            $offset = ($pages['page'] - 1) * $pages['pagesize'] ;
            $flimit = "limit $offset, ${pages['pagesize']}" ;
        }
        $dbh = $this->getDbh(false) ;
        //Prepare Sql excute
        $sth = $dbh->prepare("select $fields from $table where 1=1 $fconds $forder $flimit");
        $sth->execute() ;
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Insert record into table (Single)
     * @param   string  $table
     * @param   mixed   $set
     * @return  int     last_insert_id
     */
    public function insert($table, $set)
    {
        $dbh = $this->getDbh(true) ;
        $fset = $this->formatSet($set) ;
        $dbh->query("insert into $table set $fset") ;
        return $dbh->lastInsertId();
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
        $dbh  = $this->getDbh(true) ;
        $fset = $this->formatSet($set) ;
        $fcond= '' ;
        if(!empty($cond)) {
            $fcond = ' and '.$this->formatSet($cond , ' and ');
        }
        return $dbh->exec("update $table set $fset where 1=1 $fcond");
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
        $dbh  = $this->getDbh(true) ;
        $fcond = ' and ' . $this->formatSet($cond , ' and ');
        return $dbh->exec("delete from $table where 1=1 $fcond");
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

}