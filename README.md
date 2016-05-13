# php-mysql-utils
一个简单的php mysql工具类
```PHP
$mysql = new MySQL();
//简单查询
$data = $mysql->query("select * from test");
//带参数查询
$data = $mysql->query("select * from test where id=? and name=?",[1,'小明']);
//查询一条数据
$test = $mysql->one("select * from test where id=?",[1]);
//包含 in 的查询
$data = $mysql->query("select * from test where id in ? and name=?",[[1,2,3,4,6],'小明']);
//插入，返回主键ID
$id = $mysql->insert("insert into test (id,name)values(?,?)",[1,'小明']);
//删除,返回影响行数
$state = $mysql->delete("delete from test where id=?",[1]);
//更新,返回影响行数
$state = $mysql->update("update test set name=? where id=?",['小明',1]);
//执行完所有操作后不要忘记关闭连接
$mysql->close();

```
