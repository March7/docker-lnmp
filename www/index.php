<?php
// 建立连接
try{
	$dbh = new PDO("mysql:host=mysql;dbname=mysql", "root", 123456);
	$dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$dbh->exec("SET CHARACTER SET utf8");
	$dbh=null; //断开连接	
}catch(PDOException $e){
	print"Error!:".$e->getMessage()."<br/>";
	die();
}
// 错误检查S
// 输出成功连接
echo "<h1>成功通过 PDO 连接 MySQL 服务器</h1>" . PHP_EOL;

phpinfo();

?>