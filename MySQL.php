<?php
define ( 'DB_HOST', 'localhost' ); // 服务器
define ( 'DB_PORT', 3306);
define ( 'DB_USER', "root" ); // 数据库用户名
define ( 'DB_PASSWORD', "123" ); // 数据库密码
define ( 'DB_NAME', "test" ); // 默认数据库
define ( 'DB_CHARSET', 'utf8' ); // 数据库字符集
class MySQL{
    private $host; // 服务器
    private $port; // 服务器
    private $username; // 数据库用户名
    private $password; // 数据密码
    private $dbname; // 数据库名
    private $conn; // 数据库连接变量
    private $bind_marker = '?';
    private $_like_escape_chr = '!';
    /**
     * MySQL constructor.
     */
    public function __construct($dbname = DB_NAME,$host = DB_HOST,$port=DB_PORT, $username = DB_USER, $password = DB_PASSWORD)
    {
        $this->dbname=$dbname;
        $this->host=$host;
        $this->port=$port;
        $this->username = $username;
        $this->password=$password;
        $this->open();
    }
    /**
     * 打开数据库连接
     */
    private function open() {
        $this->conn = mysql_connect ( $this->host.":".$this->port, $this->username, $this->password ,true);
        mysql_select_db ( $this->dbname );
        mysql_query ( "SET CHARACTER SET utf8" );
    }
    /**
     * 关闭数据连接
     */
    public function close() {
        mysql_close ( $this->conn );
    }
    public function query($sql,$params=array()){
        $sql = $this->bind($sql,$params);
        $rs = mysql_query ( $sql, $this->conn );
        $data = array ();
        while ( $row = mysql_fetch_array ( $rs ) ) {
            $data [] = $row;
        }
        return $data;
    }
    public function one($sql,$params=array()) {
        $data = $this->query($sql,$params);
        return $data[0];
    }
    /**
     * 删除记录
     */
    public function delete($sql,$params=array()) {
        $this->query($sql,$params);
        return mysql_affected_rows($this->conn);
        //return mysql_query ( $sql );
    }
    /**
     * 更新表中的属性值
     */
    public function update($sql,$params=array()) {
        $this->query($sql,$params);
        return mysql_affected_rows($this->conn);
    }
    public function insert($sql,$params=array()) {
        $this->query($sql,$params);
        return mysql_insert_id ( $this->conn );
    }

    private function bind($sql,$binds){
        if (empty($binds) OR empty($this->bind_marker) OR strpos($sql, $this->bind_marker) === FALSE)
        {
            return $sql;
        }
        elseif ( ! is_array($binds))
        {
            $binds = array($binds);
            $bind_count = 1;
        }
        else
        {
            // Make sure we're using numeric keys
            $binds = array_values($binds);
            $bind_count = count($binds);
        }

        // We'll need the marker length later
        $ml = strlen($this->bind_marker);

        // Make sure not to replace a chunk inside a string that happens to match the bind marker
        if ($c = preg_match_all("/'[^']*'/i", $sql, $matches))
        {
            $c = preg_match_all('/'.preg_quote($this->bind_marker, '/').'/i',
                str_replace($matches[0],
                    str_replace($this->bind_marker, str_repeat(' ', $ml), $matches[0]),
                    $sql, $c),
                $matches, PREG_OFFSET_CAPTURE);

            // Bind values' count must match the count of markers in the query
            if ($bind_count !== $c)
            {
                return $sql;
            }
        }
        elseif (($c = preg_match_all('/'.preg_quote($this->bind_marker, '/').'/i', $sql, $matches, PREG_OFFSET_CAPTURE)) !== $bind_count)
        {
            return $sql;
        }

        do
        {
            $c--;
            $escaped_value = $this->escape($binds[$c]);
            if (is_array($escaped_value))
            {
                $escaped_value = '('.implode(',', $escaped_value).')';
            }
            $sql = substr_replace($sql, $escaped_value, $matches[0][$c][1], $ml);
        }
        while ($c !== 0);

        return $sql;
    }
    public function escape($str)
    {
        if (is_array($str))
        {
            $str = array_map(array(&$this, 'escape'), $str);
            return $str;
        }
        elseif (is_string($str) OR (is_object($str) && method_exists($str, '__toString')))
        {
            return "'".$this->escape_str($str)."'";
        }
        elseif (is_bool($str))
        {
            return ($str === FALSE) ? 0 : 1;
        }
        elseif ($str === NULL)
        {
            return 'NULL';
        }

        return $str;
    }
    public function escape_str($str, $like = FALSE)
    {
        if (is_array($str))
        {
            foreach ($str as $key => $val)
            {
                $str[$key] = $this->escape_str($val, $like);
            }

            return $str;
        }

        $str = $this->_escape_str($str);

        // escape LIKE condition wildcards
        if ($like === TRUE)
        {
            return str_replace(
                array($this->_like_escape_chr, '%', '_'),
                array($this->_like_escape_chr.$this->_like_escape_chr, $this->_like_escape_chr.'%', $this->_like_escape_chr.'_'),
                $str
            );
        }

        return $str;
    }
    protected function _escape_str($str)
    {
        return str_replace("'", "''", $this->remove_invisible_characters($str));
    }
    function remove_invisible_characters($str, $url_encoded = TRUE)
    {
        $non_displayables = array();

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded)
        {
            $non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do
        {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        }
        while ($count);

        return $str;
    }
}