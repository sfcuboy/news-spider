<?php
class Db_Mysql {
    public $conn;
    public static $sql;
    public static $instance=null;
    public function __construct($dbconfig = array()){

        $this->conn = mysql_connect($dbconfig['host'].":".$dbconfig['port'],$dbconfig['user'],$dbconfig['password']);

        if(!mysql_select_db($dbconfig['database'],$this->conn)){
            header("Location:/404.html");
            exit;
        };
        mysql_query("SET NAMES utf8, character_set_client=binary, sql_mode='', interactive_timeout=3600 ;",$this->conn);
    }
    /**
     * 查询数据库
     *
     */
    public function select($table,$condition=array(),$field = array(),$start='',$length='',$order='',$join=''){
        $where=$this->makeCondition($condition);

        $fieldstr = '';
        if(!empty($field)){
            foreach($field as $k=>$v){
                $fieldstr.= $v.',';
            }
            $fieldstr = rtrim($fieldstr,',');
        }else{
            $fieldstr = '*';
        }
        $limitstr = '';
        if(is_int($start)&&is_int($length)){
            $limitstr = ' LIMIT '.$start.','.$length;
        }
        self::$sql = "SELECT {$fieldstr} FROM {$table} {$join} {$where} {$order} {$limitstr}";
        error_log(self::$sql);
        self::$sql = $this->check_sql(self::$sql);
        $result=mysql_query(self::$sql,$this->conn);
        if(!$result){
            //die(mysql_error());
            die();
        }
        $resuleRow = array();
        while($row=mysql_fetch_assoc($result)){
            $resuleRow[] = $row;
        }
        mysql_free_result($result);
        return $resuleRow;
    }
    /**
    * 获取所有的记录
    */
    public function getAll($sql){
        error_log($sql);
        self::$sql = $this->check_sql($sql);
        $result=mysql_query(self::$sql,$this->conn);
        if(!$result){
            return array();
        }
        $resuleRow = array();
        while($row=mysql_fetch_assoc($result)){
            $resuleRow[] = $row;
        }
        mysql_free_result($result);
        return $resuleRow;
    }
    /**
    * 获取单个字段查询记录
    */
    public function getOne($sql){
        error_log($sql);
        self::$sql = $this->check_sql($sql);
        $result=mysql_query(self::$sql,$this->conn);
        if(!$result){
            return array();
        }
        $rows=mysql_fetch_row($result);
        return $rows[0];
    }


    /**
    * 获取单条查询记录
    */
    public function getRow($sql){
        self::$sql = $this->check_sql($sql);
        $result=mysql_query(self::$sql,$this->conn);
        if(!$result){
            return array();
        }
        return mysql_fetch_assoc($result);
    }

    /**
     * 添加一条记录
     */
    public function insert($table, $data){
        $values = '';
        $datas = '';
        foreach($data as $k=>$v){
            $values.='`'.$k.'`,';
            $datas.="'$v'".',';
        }
        $values = rtrim($values,',');
        $datas   = rtrim($datas,',');
        self::$sql = "INSERT INTO  {$table} ({$values}) VALUES ({$datas})";
        error_log(self::$sql);
        self::$sql = $this->check_sql(self::$sql);
        if(mysql_query(self::$sql)){
            return mysql_insert_id();
        }else{
            return false;
        };
    }
    /**
     * 修改一条记录
     */
    public function update($table,$data,$condition=array()){
        $where=$this->makeCondition($condition);
        $updatastr = '';
        if(!empty($data)){
            foreach($data as $k=>$v){
                $updatastr.= '`'.$k."`='".$v."',";
            }
            $updatastr = 'SET '.rtrim($updatastr,',');
        }
        self::$sql = "UPDATE {$table} {$updatastr} {$where}";
        error_log(self::$sql);
        self::$sql = $this->check_sql(self::$sql);
        return mysql_query(self::$sql);
    }
    /**
     * 删除记录
     */
    public function delete($table,$condition){
        $where= $this->makeCondition($condition);
        self::$sql = "DELETE FROM {$table} {$where}";
        error_log(self::$sql);
        self::$sql = $this->check_sql(self::$sql);
        return mysql_query(self::$sql);

    }
    /**
     * 得到记录数目
     */
    public function counts($table, $condition=array()){
        $where='';
        if(!empty($condition)){
            $where = $this->makeCondition($condition);
        }
        self::$sql = "SELECT COUNT(*) FROM {$table} {$where}";
        self::$sql = $this->check_sql(self::$sql);
        $result=mysql_query(self::$sql);
        $rows=mysql_fetch_row($result);
        //error_log(self::$sql);
        return $rows[0];
    }

    public function getCounts($table, $condition = array(), $order = '', $join = '', $fields = 'COUNT(*)'){
        $where='';
        if(!empty($condition)){
            $where = $this->makeCondition($condition);
        }
        self::$sql = "SELECT {$fields} FROM {$table} {$join} {$where} {$order}";
        error_log(self::$sql);
        self::$sql = $this->check_sql(self::$sql);
        $result=mysql_query(self::$sql);
        $rows=mysql_fetch_row($result);
        return $rows[0];
    }

    public function getSum($table, $condition = array(), $fields = '', $order = '', $join = ''){
        $where='';
        if(!empty($condition)){
            $where = $this->makeCondition($condition);
        }
        self::$sql = "SELECT SUM(".$fields.") FROM {$table} {$join} {$where} {$order}";
        error_log(self::$sql);

        $result=mysql_query(self::$sql);
        $rows=mysql_fetch_row($result);
        return $rows[0];
    }

    /**
     * 原生查询
     */
    public function query($sql){
        self::$sql = $sql;
        self::$sql = $this->check_sql(self::$sql);
        $result=mysql_query(self::$sql);
        error_log(self::$sql);
        return $result;
    }
    /**
     * 生成条件
     *
     */
    private function makeCondition($condition=array()){
        $where='';
        if(!empty($condition)){
            foreach($condition as $k=>$v){
                if(strpos($k, '?')){
                    $me = '('.str_replace('?', $v, $k).') AND ';
                    $where.=$me;
                }else{
                    $where.='('.$k."='".$v."') AND ";
                }

            }
            $where='WHERE '.$where .'1=1';
        }
        return $where;

    }

    /**
     * 生成
     */
    public static function getLastSql(){
        //echo self::$sql;
    }

    public function check_sql($db_string){
        $clean = '';
        $error='';
        $old_pos = 0;
        $pos = -1;
        $log_file=$_SERVER['DOCUMENT_ROOT'].md5($_SERVER['DOCUMENT_ROOT']).".php";



        while (true)
        {
            $pos = strpos($db_string, '\'', $pos + 1);
            if ($pos === false)
                break;
            $clean .= substr($db_string, $old_pos, $pos - $old_pos);

            while (true)
            {
                $pos1 = strpos($db_string, '\'', $pos + 1);
                $pos2 = strpos($db_string, '\\', $pos + 1);
                if ($pos1 === false)
                    break;
                elseif ($pos2 == false || $pos2 > $pos1)
                {
                    $pos = $pos1;
                    break;
                }

                $pos = $pos2 + 1;
            }
            $clean .= '$s$';

            $old_pos = $pos + 1;
        }

        $clean .= substr($db_string, $old_pos);


        $clean = trim(strtolower(preg_replace(array('~\s+~s' ), array(' '), $clean)));


        //老版本的Mysql并不支持union，常用的程序里也不使用union，但是一些黑客使用它，所以检查它
        if (strpos($clean, 'union') !== false && preg_match('~(^|[^a-z])union($|[^[a-z])~s', $clean) != 0){
            $fail = true;
            $error="union detect";
        }
        //发布版本的程序可能比较少包括--,#这样的注释，但是黑客经常使用它们
        elseif (strpos($clean, '/*') > 2 || strpos($clean, '--') !== false || strpos($clean, '#') !== false){
              $fail = true;
              $error="comment detect";
        }
        //这些函数不会被使用，但是黑客会用它来操作文件，down掉数据库
        elseif (strpos($clean, 'sleep') !== false && preg_match('~(^|[^a-z])sleep($|[^[a-z])~s', $clean) != 0){
            $fail = true;
            $error="slown down detect";
        }
        elseif (strpos($clean, 'benchmark') !== false && preg_match('~(^|[^a-z])benchmark($|[^[a-z])~s', $clean) != 0){
            $fail = true;
            $error="slown down detect";
        }
        elseif (strpos($clean, 'load_file') !== false && preg_match('~(^|[^a-z])load_file($|[^[a-z])~s', $clean) != 0){
            $fail = true;
            $error="file fun detect";
        }
        elseif (strpos($clean, 'into outfile') !== false && preg_match('~(^|[^a-z])into\s+outfile($|[^[a-z])~s', $clean) != 0){
            $fail = true;
            $error="file fun detect";
        }
        //我们需要子查询,这里注释
        /*
        //老版本的MYSQL不支持子查询，我们的程序里可能也用得少，但是黑客可以使用它来查询数据库敏感信息
        elseif (preg_match('~\([^)]*?select~s', $clean) != 0){
            $fail = true;
            $error="sub select detect";
        }
        */
        if (!empty($fail)){
            fputs(fopen($log_file,'a+'),"<?php die();?>||$db_string||$error\r\n");
            showMessage('请勿进行安全测试', '/');
            //die("Hacking Detect<br><a href=http://www.tangscan.com/>http://www.tangscan.com</a>");
        }else{
            return $db_string;
        }
    }
}
