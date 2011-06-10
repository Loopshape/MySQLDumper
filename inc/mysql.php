<?php


//Feldspezifikationen
$feldtypen=Array("VARCHAR","TINYINT","TEXT","DATE","SMALLINT","MEDIUMINT","INT","BIGINT","FLOAT","DOUBLE","DECIMAL","DATETIME","TIMESTAMP","TIME","YEAR","CHAR","TINYBLOB","TINYTEXT","BLOB","MEDIUMBLOB","MEDIUMTEXT","LONGBLOB","LONGTEXT","ENUM","SET");
$feldattribute=ARRAY("","BINARY","UNSIGNED","UNSIGNED ZEROFILL");
$feldnulls=Array("NOT NULL","NULL");
$feldextras=Array("","AUTO_INCREMENT");
$feldkeys=Array("","PRIMARY KEY","UNIQUE KEY", "FULLTEXT");
$feldrowformat=Array("","FIXED","DYNAMIC","COMPRESSED");

$rechte_daten=Array("SELECT","INSERT","UPDATE","DELETE","FILE");
$rechte_struktur=Array("CREATE","ALTER","INDEX","DROP","CREATE TEMPORARY TABLES");
$rechte_admin=Array("GRANT","SUPER","PROCESS","RELOAD","SHUTDOWN","SHOW DATABASES","LOCK TABLES","REFERENCES","EXECUTE","REPLICATION CLIENT","REPLICATION SLAVE");
$rechte_resourcen=Array("MAX QUERIES PER HOUR","MAX UPDATES PER HOUR","MAX CONNECTIONS PER HOUR");

$sql_keywords=array(  'ALTER', 'AND', 'ADD', 'AUTO_INCREMENT','BETWEEN', 'BINARY', 'BOTH', 'BY', 'BOOLEAN','CHANGE', 'CHARSET','CHECK','COLLATE', 'COLUMNS', 'COLUMN', 'CROSS','CREATE',	'DATABASES', 'DATABASE', 'DATA', 'DELAYED', 'DESCRIBE', 'DESC',  'DISTINCT', 'DELETE', 'DROP', 'DEFAULT','ENCLOSED', 'ENGINE','ESCAPED', 'EXISTS', 'EXPLAIN','FIELDS', 'FIELD', 'FLUSH', 'FOR', 'FOREIGN', 'FUNCTION', 'FROM','GROUP', 'GRANT','HAVING','IGNORE', 'INDEX', 'INFILE', 'INSERT', 'INNER', 'INTO', 'IDENTIFIED','JOIN','KEYS', 'KILL','KEY','LEADING', 'LIKE', 'LIMIT', 'LINES', 'LOAD', 'LOCAL', 'LOCK', 'LOW_PRIORITY', 'LEFT', 'LANGUAGE', 'MEDIUMINT', 'MODIFY','MyISAM','NATURAL', 'NOT', 'NULL', 'NEXTVAL','OPTIMIZE', 'OPTION', 'OPTIONALLY', 'ORDER', 'OUTFILE', 'OR', 'OUTER', 'ON','PROCEEDURE','PROCEDURAL', 'PRIMARY','READ', 'REFERENCES', 'REGEXP', 'RENAME', 'REPLACE', 'RETURN', 'REVOKE', 'RLIKE', 'RIGHT','SHOW', 'SONAME', 'STATUS', 'STRAIGHT_JOIN', 'SELECT', 'SETVAL', 'TABLES', 'TEMINATED', 'TO', 'TRAILING','TRUNCATE', 'TABLE', 'TEMPORARY', 'TRIGGER', 'TRUSTED','UNIQUE', 'UNLOCK', 'USE', 'USING', 'UPDATE', 'UNSIGNED','VALUES', 'VARIABLES', 'VIEW','WITH', 'WRITE', 'WHERE','ZEROFILL','XOR','ALL', 'ASC', 'AS','SET','IN', 'IS', 'IF');
$mysql_doc=Array("Feldtypen" => "http://dev.mysql.com/doc/mysql/de/Column_types.html");


function MSD_mysql_connect()
{
	global $config,$databases;
	$port=(isset($config["dbport"]) && !empty($config["dbport"])) ? ":".$config["dbport"] : "";
	$socket=(isset($config["dbsocket"]) && !empty($config["dbsocket"])) ? ":".$config["dbsocket"] : "";
	$config["dbconnection"] = @mysql_connect($config["dbhost"].$port.$socket,$config["dbuser"],$config["dbpass"]) or die(SQLError("Datenbankverbindung",mysql_error())); 
	if(!defined('MSD_MYSQL_VERSION')) GetMySQLVersion();
}

function GetMySQLVersion()
{
	$res=MSD_query("select version()");
	$row = mysql_fetch_array($res);
	$version=$row[0];
	$new=(substr($version,0,3)>=4.1);
	if(!defined('MSD_MYSQL_VERSION')) define('MSD_MYSQL_VERSION', $version);
	if(!defined('MSD_NEW_VERSION')) define('MSD_NEW_VERSION',$new);
}

function MSD_query($query)
{
	global $config;
	if(!isset($config["dbconnection"]))  MSD_mysql_connect();
	return @mysql_query($query, $config["dbconnection"]);

}

function MSD_mysql_error()
{
	global $config,$databases;
	
}

function SQLError($sql,$error)
{
	global $lang;
	echo '<div align="center"><table border="1" bordercolor="#ff0000" cellspacing="0">
<tr bgcolor="#ff0000"><td style="color:white;font-size:16px;"><strong>MySQL-ERROR</strong></td></tr>
<tr><td style="width:80%;overflow: auto;">'.$lang['sql_error1'].'<br><br><pre>'.highlight_sql($sql).'</pre></td></tr>
<tr><td width="600">'.$lang['sql_error2'].'<br><br><span style="color:red;">'.$error.'</span></td></tr>
</table>
</div>';

}

function Highlight_SQL($sql)
{
	global $sql_keywords;
	$tmp="/".implode("/ /",$sql_keywords)."/";
	$tmp_array=explode(" ",$tmp);
	$tmp="<span style=\"color:#990099;font-weight:bold;\">".implode("</span>###<span style=\"color:#990099;font-weight:bold;\">",$sql_keywords)."</span>";
	$tmp_replace=explode("###",$tmp);
	
	$sql=nl2br(htmlentities($sql));
	$sql=preg_replace("/`(.*?)`/si", "<span style=\"color:red;\">`$1`</span>", $sql);
	str_replace("{","{<ul>",$sql);
	str_replace("}","}</ul>",$sql);
	str_replace("*","<span style=\"color:red;\">*</span>",$sql);
	$sql=preg_replace($tmp_array, $tmp_replace, $sql); 
	
	return $sql;
}
?>
