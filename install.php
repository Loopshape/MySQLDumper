<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="expires" content="0">
	<meta http-equiv="cache-control" content="must-revalidate">
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-15">
	<title>MySQLDumper - Installation</title>
	
<link rel="stylesheet" type="text/css" href="styles.css">
<script language="JavaScript" src="script.js"></script>
<style>
	a {text-decoration:underline;}
</style>
</head>
<body>
<?php

include_once("inc/functions.php");
include_once("inc/mysql.php");
require("inc/runtime.php");

$install_ftp_server=$install_ftp_user_name=$install_ftp_user_pass=$install_ftp_path="";

foreach($_GET as $getvar => $getval){ ${$getvar} = $getval; }
foreach($_POST as $postvar => $postval){ ${$postvar} = $postval; } 
$dbonly=(isset($dbonly)) ? $dbonly : "";
$dbport=(isset($dbport)) ? $dbport : "";
$dbsocket=(isset($dbsocket)) ? $dbsocket : "";
if(!isset($language)) $language="de";
if(isset($dbhost) && isset($dbuser) && isset($dbpass)) {
	$config["dbhost"]=$dbhost;
	$config["dbuser"]=$dbuser;
	$config["dbpass"]=$dbpass;
	$config["dbonly"]=$dbonly;
	$config["dbport"]=$dbport;
	$config["dbsocket"]=$dbsocket;
	$connstr="$dbhost|$dbuser|$dbpass|$dbonly|$dbport|$dbsocket";
} else {
	if(isset($connstr) && !empty($connstr)) {
		$p=explode("|",$connstr);
		$dbhost=$config["dbhost"]=$p[0];
		$dbuser=$config["dbuser"]=$p[1];
		$dbpass=$config["dbpass"]=$p[2];
		$dbonly=$config["dbonly"]=$p[3];
		$dbport=$config["dbport"]=$p[4];
		$dbsocket=$config["dbsocket"]=$p[5];
	} else $connstr="";
}

//Variabeln
$phase=(isset($phase)) ? $phase : 0;
$config["language"]=(isset($language)) ? $language : "de";
$delfiles=Array();

$img_ok='<img src="images/ok.gif" width="16" height="16" alt="ok">';
$img_failed='<img src="images/notok.gif" width="16" height="16" alt="failed">';
$href="install.php?language=$language&phase=$phase&connstr=$connstr";

include_once("language/lang_".$config["language"].".php"); 


if($phase<10) {
	if($phase==0)
	 	$Anzeige=$lang['install'].' - '.$lang["installmenu"];
	else $Anzeige=$lang['install'].' - '.$lang["step"].' '.($phase);
} elseif ($phase>9 && $phase<12) {
	$Anzeige=$lang['install'].' - '.$lang["step"].' '.($phase-7);
} elseif ($phase>19 && $phase<100) {
	$Anzeige=$lang['tools'];
} else {
	$Anzeige=$lang['uninstall'].' - '.$lang["step"].' '.($phase-99);
}


echo '
<div align="center">'.$connstr.'<br>
<a href="install.php"><img src="images/logo.gif" width="160" height="53" alt="'.$lang['install_tomenu'].'"></a><br>
<span class="small"><strong>Version '.$config["version"].'</strong><br><a href="index.php?force=1" class="small">'.$lang['install_forcescript'].'</a></span>
';

echo '<h3>'.$Anzeige.'</h3>';


switch ($phase) {

	case 0: // Anfang - Sprachauswahl
		echo '<table cellpadding="20" cellspacing="20">
		<tr>
			<td align="center"><img src="images/germany.gif" alt="" width="50" height="30"border="0"></td>
			<td align="center"><img src="images/usa.gif" alt="" width="50" height="30"border="0"></td>
		</tr><tr>
			<td align="center"><a href="install.php?language=de&phase='.($phase+1).'"><strong>installiere MySQLDumper</strong></a></td>
			<td align="center"><a href="install.php?language=en&phase='.($phase+1).'"><strong>install MySQLDumper</strong></a></td>
		</tr><tr>
			<td colspan="2" align="center"><h6>Tools</h6></td>
		</tr><tr>	
			<td align="center"><a href="install.php?language=de&phase=100">MySQLDumper deinstallieren</a></td>
			<td align="center"><a href="install.php?language=en&phase=100">uninstall MySQLDumper</a></td>
		 </tr>';
		 if(file_exists($config["paths"]["config"]."config.gz")) {
		 	echo '<tr><td align="center"><a href="install.php?language=de&phase=20">vorhandene Konfigurationssicherung importieren</a></td>
			<td align="center"><a href="install.php?language=en&phase=20">import existing configurationbackup</a></td>
			</tr>';
		 }
		if(file_exists($config["paths"]["config"]."parameter.php")){
			echo '<tr><td align="center"><a href="install.php?language=de&phase=21">Konfigurationssicherung hochladen und importieren</a></td>
			<td align="center"><a href="install.php?language=en&phase=21">upload configuration backup and import</a></td>
		 </tr>';
		}
		 if(file_exists("config.php") && file_exists($config["paths"]["config"]."parameter.php")) {
		 	zipConfig();
			echo '<tr>
				<td align="center"><a href="'.$config["paths"]["config"].'config.gz">Konfigurationssicherung runterladen</a></td>
				<td align="center"><a href="'.$config["paths"]["config"].'config.gz">Download Configuration Backup</a></td>
				</tr>';
		 }
		 echo '</table>';
		 break;
	case 1: // checken
		if(isset($trychmod) && $trychmod==1) @chmod("config.php","0777");
		echo '<h4>'.$lang['dbparameter'].'</h4>';
		if(!is_writable("config.php")) {
			echo '<p class="warning">'.$lang['confignotwritable'].'</p>';
			echo '<a href="'.$href.'">'.$lang['tryagain'].'</a>';
			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="install.php">'.$lang['install_tomenu'].'</a>';
		} else {
			$tmp=file("config.php");
			$stored=0;
			if(!(isset($dbhost) && isset($dbuser) && isset($dbpass))) {
				//lese aus config
				for($i=0;$i<count($tmp);$i++) {
					if(substr($tmp[$i],0,17)=='$config["dbhost"]') {
						$config["dbhost"] = extractValue($tmp[$i]);
						$stored++;
					}
					if(substr($tmp[$i],0,17)=='$config["dbport"]') {
						$config["dbport"] = extractValue($tmp[$i]);
						$stored++;
					}
					if(substr($tmp[$i],0,19)=='$config["dbsocket"]') {
						$config["dbsocket"] = extractValue($tmp[$i]);
						$stored++;
					}
					if(substr($tmp[$i],0,17)=='$config["dbuser"]') {
						$config["dbuser"] = extractValue($tmp[$i]);
						$stored++;
					}
					if(substr($tmp[$i],0,17)=='$config["dbpass"]') {
						$config["dbpass"] = extractValue($tmp[$i]);
						$stored++;
					}
					if(substr($tmp[$i],0,17)=='$config["dbonly"]') {
						$config["dbonly"] = extractValue($tmp[$i]);
						$stored++;
					}
					
					if($stored==6) break;
				}
			}
			if(!isset($config["dbport"])) $config["dbport"]="";
			if(!isset($config["dbsocket"])) $config["dbsocket"]="";
			
			$exp=(!isset($expert)) ? '<div align="right"><a href="'.$href.'&expert=1" class="uls">'.$lang['expert'].'</a></div>' : '';
			echo '<table><tr><td class="hd" colspan="2">'.$lang['dbparameter'].$exp.'</td></tr>';
			echo '<form action="install.php?language='.$language.'&phase='.$phase.'" method="post">';
			echo '<tr><td>'.$lang['db_host'].':</td><td><input type="text" name="dbhost" value="'.$config["dbhost"].'" size="60" maxlength="100"></td></tr>';
			echo '<tr><td>'.$lang['db_user'].':</td><td><input type="text" name="dbuser" value="'.$config["dbuser"].'" size="60" maxlength="100"></td></tr>';
			echo '<tr><td>'.$lang['db_pass'].':</td><td><input type="password" name="dbpass" value="'.$config["dbpass"].'" size="60" maxlength="100"></td></tr>';
			if(isset($expert)) {
			  echo '<tr><td><input type="hidden" name="expert" value="1">'.$lang['db_only'].':</td><td><input type="text" name="dbonly" value="'.$config["dbonly"].'" size="60" maxlength="100"></td></tr>';
			  echo '<tr><td>Port:</td><td><input type="text" name="dbport" value="'.$config["dbport"].'" size="5" maxlength="5">&nbsp;&nbsp;'.$lang['install_help_port'].'</td></tr>';
			  echo '<tr><td>Socket:</td><td><input type="text" name="dbsocket" value="'.$config["dbsocket"].'" size="5" maxlength="5">&nbsp;&nbsp;'.$lang['install_help_socket'].'</td></tr>';			
			}
			echo '<tr><td>'.$lang['testconnection'].':</td><td><input type="submit" name="dbconnect" value="'.$lang['connecttomysql'].'"></td></tr>';
			if(isset($dbconnect)) {
				
				echo '<tr><td class="hd" colspan="2">'.$lang['dbconnection'].'</td></tr>';
				echo '<tr><td colspan="2">';
				MSD_mysql_connect(); 
					
				if(!$config["dbconnection"]) {
					echo '<h5 style="color:red;">'.$lang['connectionerror'].'</h5><span>&nbsp;';
				} else {
					echo '<h5>'.$lang['connection_ok'].'</h5><span class="smallgrey">';
					$connection="ok";
					$connstr="$dbhost|$dbuser|$dbpass|$dbonly|$dbport|$dbsocket";
					echo '<input type="hidden" name="connstr" value="'.$connstr.'">';
					SearchDatabases(1);
					if(empty($databases["Name"])) echo '<input type="hidden" name="expert" value="1">';		
				}
				echo '</span></td></tr>';
			}
			echo '</form></table><br>';
			
			if(isset($connection) && $connection=="ok" && !empty($databases["Name"])) {
			
			echo '<form action="install.php?language='.$language.'&phase='.($phase+1).'" method="post">';
			echo '<input type="hidden" name="dbhost" value="'.$config["dbhost"].'">
			<input type="hidden" name="dbuser" value="'.$config["dbuser"].'">
			<input type="hidden" name="dbpass" value="'.$config["dbpass"].'">
			<input type="hidden" name="dbonly" value="'.$config["dbonly"].'">';
			echo '<input type="hidden" name="connstr" value="'.$connstr.'">';
			echo '<input type="submit" name="submit" value=" '.$lang['saveandcontinue'].' "></form>';
			}
		}
		break;
	case 2: //
		echo '<h4>MySQLDumper - '.$lang['confbasic'].'</h4>';
		$tmp=file("config.php");
		$stored=0;
		for($i=0;$i<count($tmp);$i++) {
			if(substr($tmp[$i],0,17)=='$config["dbhost"]') {
				$tmp[$i]='$config["dbhost"] = \''.$config["dbhost"].'\';'."\n";
				$stored++;
			}
			if(substr($tmp[$i],0,17)=='$config["dbport"]') {
				$tmp[$i]='$config["dbport"] = \''.$config["dbport"].'\';'."\n";
				$stored++;
			}
			if(substr($tmp[$i],0,19)=='$config["dbsocket"]') {
				$tmp[$i]='$config["dbsocket"] = \''.$config["dbsocket"].'\';'."\n";
				$stored++;
			}
			if(substr($tmp[$i],0,17)=='$config["dbuser"]') {
				$tmp[$i]='$config["dbuser"] = \''.$config["dbuser"].'\';'."\n";
				$stored++;
			}
			if(substr($tmp[$i],0,17)=='$config["dbpass"]') {
				$tmp[$i]='$config["dbpass"] = \''.$config["dbpass"].'\';'."\n";
				$stored++;
			}
			if(substr($tmp[$i],0,17)=='$config["dbonly"]') {
				$tmp[$i]='$config["dbonly"] = \''.$config["dbonly"].'\';'."\n";
				$stored++;
			}
			if($stored==6) break;
		}
		$ret=true;
		if ($fp=fopen("config.php", "wb"))
		{ 
			if (!fwrite($fp,implode($tmp,""))) $ret=false; 
			if (!fclose($fp)) $ret=false; 
		}
		if(!$ret) {
			echo '<p class="warnung">'.$lang['import12'].'</p>';
		} else {
			if(ini_get('safe_mode')==1) {
				$nextphase=(extension_loaded("ftp")) ? 10 : 9; 
			} else $nextphase=$phase+2;
			echo $lang['install_step2finished'];
			echo '<br /><hr width="60%" /><br />';
			echo '<form action="install.php?language='.$language.'&phase='.$nextphase.'" method="post"><input type="hidden" name="connstr" value="'.$connstr.'"><input type="submit" name="continue2" value=" '.$lang['install_step2_1'].' "></form>';
			echo '<br /><hr width="60%" /><br />';
			echo '<form action="install.php?language='.$language.'&phase='.($phase+1).'" method="post"><input type="hidden" name="connstr" value="'.$connstr.'"><input type="submit" name="continue1" value=" '.$lang['editconf'].' "></form>';
		}
		
		break;
	case 3: //
		if(ini_get('safe_mode')==1) $nextphase=10; else $nextphase=$phase+1;
		echo '<h4>'.$lang['editconf'].'</h4>';
		if($config["language"]=="en") echo '<strong>important!</strong> change the line $config["language"]=\'de\' to $config["language"]=\'en\'<br>';
		echo '<form action="install.php?language='.$language.'&phase='.$nextphase.'" method="post">
		<textarea name="configfile" style="font-size:11px;color:blue;width:700px;height:300px;overflow:scroll;">';
		$f=file("config.php");
		for($i=0;$i<count($f);$i++) { echo stripslashes($f[$i]);}
		echo '</textarea><br><input type="reset" name="reset" value="'.$lang["reset"].'">&nbsp;&nbsp;
		<input type="submit" name="submit" value="'.$lang["save"].'">&nbsp;&nbsp;
		<input type="submit" name="nosave" value="'.$lang["osweiter"].'">';
		echo '<input type="hidden" name="connstr" value="'.$connstr.'">';
		echo '</form>';
		
		break;
	case 4: //Verzeichnisse
	
		if(isset($_POST["submit"])) {
			$ret=true;
			if ($fp=fopen("config.php", "wb"))
			{ 
				if (!fwrite($fp,stripslashes(stripslashes($_POST["configfile"])))) $ret=false; 
				if (!fclose($fp)) $ret=false; 
			}
			else $ret=false;
			if($ret==false) {
				echo '<br><strong>'.$lang['errorman'].' config.php '.$lang['manuell'].'.';
				die;
			}
		}
		
		echo '<h4>'.$lang['createdirs'].'</h4>';
		
		SetFileRechte("work/");@chmod("work",0777);
		SetFileRechte("work/config/");@chmod("work/config",0777);
		SetFileRechte("work/log/");@chmod("work/log",0777);
		SetFileRechte("work/backup/");@chmod("work/backup",0777);
		SetFileRechte("work/structure/");@chmod("work/structure",0777);
		$iw[0]=IsWritable("work");
		$iw[1]=IsWritable("work/config");
		$iw[2]=IsWritable("work/log");
		$iw[3]=IsWritable("work/backup");
		$iw[4]=IsWritable("work/structure");
		
		echo '<form action="install.php?language='.$language.'&phase=4" method="post"><table><tr>';
		echo '<tr><td class="hd2">'.$lang['dir'].'</td><td class="hd2">'.$lang['rechte'].'</td><td class="hd2">'.$lang['status'].'</td></tr>';
		echo '<tr><td><strong>work</strong></td><td>'.Rechte("work").'</td><td>'.(($iw[0]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/config</strong></td><td>'.Rechte("work/config").'</td><td>'.(($iw[1]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/log</strong></td><td>'.Rechte("work/log").'</td><td>'.(($iw[2]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/backup</strong></td><td>'.Rechte("work/backup").'</td><td>'.(($iw[3]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/structure</strong></td><td>'.Rechte("work/structure").'</td><td>'.(($iw[4]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td colspan="3" align="right"><input type="hidden" name="connstr" value="'.$connstr.'"><input type="submit" name="dir_check" value=" '.$lang['check'].' "></td></tr>';
		if($iw[0] && $iw[1] && $iw[2] && $iw[3] && $iw[4])
			echo '<tr><td colspan="2">'.$lang['dirs_created'].'<br><br><input type="Button" value=" '.$lang['install_continue'].' " onclick="location.href=\'install.php?language='.$language.'&phase=5&connstr='.$connstr.'\'"></td></tr>';
		echo '</table>';
		break;
	case 5:
		echo '<h4>'.$lang['laststep'].'</h4>';
		
		
		//SearchDatabases(0);
		SetDefault();
		echo '<br>'.$lang['installfinished'];
			
		if(file_exists($config["paths"]["config"]."config.gz")) {
			echo '<br><br><a href="install.php?language='.$language.'&phase=20">'.$lang['import1'].'</a>';
		}
		echo '<br><br><a href="install.php?language='.$language.'&phase=21">'.$lang['import2'].'</a>';
		
		break;
	case 9:
		
		clearstatcache();
		$iw[0]=IsWritable("work");
		$iw[1]=IsWritable("work/config");
		$iw[2]=IsWritable("work/log");
		$iw[3]=IsWritable("work/backup");
		$iw[4]=IsWritable("work/structure");
		echo '<h4>'.$lang['ftpmode'].'</h4>';
		echo '<p align="left" style="padding-left:100px; padding-right:100px;">'.$lang['safemodedesc'].'</p>';
		
		echo '<form action="install.php?language='.$language.'&phase=9" method="post"><input type="hidden" name="connstr" value="'.$connstr.'"><table>';
		echo '<tr><td class="hd2" colspan="2">'.$lang['idomanual'].'</td></tr>';
		echo '<tr><td colspan="2">'.$lang['dofrom'].'<br><div class="small">'.Realpfad('./').'</div></td></tr>';
		echo '<tr><td><strong>work</strong></td><td>'.(($iw[0]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/config</strong></td><td>'.(($iw[1]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/log</strong></td><td>'.(($iw[2]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/backup</strong></td><td>'.(($iw[3]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/structure</strong></td><td>'.(($iw[4]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td colspan="3" align="right"><input type="submit" name="dir_check" value=" '.$lang['check'].' "></td></tr>';
		if($iw[0] && $iw[1] && $iw[2] && $iw[3] && $iw[4])
			echo '<tr><td colspan="2">'.$lang['dirs_created'].'<br><input type="Button" value=" '.$lang['install_continue'].' " onclick="location.href=\'install.php?language='.$language.'&phase=4&connstr='.$connstr.'\'"></td></tr>';
		echo '</table>';
		
		break;
	case 10: //safe_mode FTP
		
		clearstatcache();
		$iw[0]=IsWritable("work");
		$iw[1]=IsWritable("work/config");
		$iw[2]=IsWritable("work/log");
		$iw[3]=IsWritable("work/backup");
		$iw[4]=IsWritable("work/structure");
		if(!isset($install_ftp_port) || $install_ftp_port<1) $install_ftp_port=21;
		echo '<h4>'.$lang['ftpmode'].'</h4>';
		echo '<p align="left" style="padding-left:100px; padding-right:100px;">'.$lang['safemodedesc'].'</p>';
		
		echo '<form action="install.php?language='.$language.'&phase=10" method="post"><input type="hidden" name="connstr" value="'.$connstr.'"><table width="80%"><tr><td width="50%" valign="top"><table>';
		echo '<tr><td class="hd2" colspan="2">'.$lang['idomanual'].'</td></tr>';
		echo '<tr><td colspan="2">'.$lang['dofrom'].'<br><div class="small">'.Realpfad('./').'</div></td></tr>';
		echo '<tr><td><strong>work</strong></td><td>'.(($iw[0]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/config</strong></td><td>'.(($iw[1]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/log</strong></td><td>'.(($iw[2]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/backup</strong></td><td>'.(($iw[3]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td><strong>work/structure</strong></td><td>'.(($iw[4]) ? $img_ok : $img_failed).'</td></tr>';
		echo '<tr><td colspan="3" align="right"><input type="submit" name="dir_check" value=" '.$lang['check'].' "></td></tr>';
		if($iw[0] && $iw[1] && $iw[2] && $iw[3] && $iw[4])
			echo '<tr><td colspan="2">'.$lang['dirs_created'].'<br><input type="Button" value=" '.$lang['install_continue'].' " onclick="location.href=\'install.php?language='.$language.'&phase=4&connstr='.$connstr.'\'"></td></tr>';
		echo '</table></td><td width="50%" valign="top">';
		echo '<table><tr><td class="hd2" colspan="2">'.$lang['ftpmode2'].'</td></tr>';
		echo '<tr><td>FTP-Server</td><td><input type="text" name="install_ftp_server" value="'.$install_ftp_server.'"></td></tr>';
		echo '<tr><td>FTP-Port</td><td><input type="text" name="install_ftp_port" value="'.$install_ftp_port.'" size="4"></td></tr>';
		echo '<tr><td>FTP-User</td><td><input type="text" name="install_ftp_user_name" value="'.$install_ftp_user_name.'"></td></tr>';
		echo '<tr><td>FTP-'.$lang['db_pass'].'</td><td><input type="text" name="install_ftp_user_pass" value="'.$install_ftp_user_pass.'"></td></tr>';
		echo '<tr><td>'.$lang['info_scriptdir'].'</td><td><input type="text" name="install_ftp_path" value="'.$install_ftp_path.'"></td></tr>';
		echo '<tr><td colspan="2" align="right"><input type="submit" name="ftp_connect" value="'.$lang['connect'].'"></td></tr></form>';
		if(isset($ftp_connect)) {
			echo '<tr><td class="smallgrey">'.$lang['connect_to'].' `'.$install_ftp_server.'` Port '.$install_ftp_port.' ... <br>';
			$tftp=TesteFTP($install_ftp_server,$install_ftp_port,$install_ftp_user_name,$install_ftp_user_pass,$install_ftp_path);
			echo $tftp;
			echo '</td><td colspan="2" align="right">&nbsp;';
			if(substr($tftp,-5)=="</strong>") {
				echo '<form action="install.php?language='.$language.'&phase=11" method="post"><input type="hidden" name="connstr" value="'.$connstr.'">';
				echo '<input type="hidden" name="install_ftp_server" value="'.$install_ftp_server.'"><input type="hidden" name="install_ftp_port" value="'.$install_ftp_port.'"><input type="hidden" name="install_ftp_user_name" value="'.$install_ftp_user_name.'"><input type="hidden" name="install_ftp_user_pass" value="'.$install_ftp_user_pass.'"><input type="hidden" name="install_ftp_path" value="'.$install_ftp_path.'">';
				echo '<input type="submit" name="submit" value=" '.$lang['createdirs2'].' "></form>';
			}
			echo '</td></tr>';	
		}
		echo '</table></td></tr>';
		
		echo '</table>';
		
		
		break;
		
	case 11: //FTP-Create Dirs
		echo '<h4>'.$lang['ftpmode'].'</h4>';
		if(CreateDirsFTP()==1) {
			SetDefault();
			echo DirectoryWarnings();
			echo '<br>'.$lang['installfinished'];
			
			if(is_writable($config["paths"]["config"])) {
				if(file_exists($config["paths"]["config"]."config.gz")) {
					echo '<br><br><a href="install.php?language='.$language.'&phase=20&connstr='.$connstr.'">'.$lang['import1'].'</a>';
				}
				echo '<br><a href="install.php?language='.$language.'&phase=21&connstr='.$connstr.'">'.$lang['import2'].'</a>';
			}
		}
		break;
	case 20: //import
		echo '<h4>'.$lang['import'].'</h4>';
		
		$import=importConfig($config["paths"]["config"]."config.gz");
		if($import==0) {
			echo '<h5>'.$lang['import3'].'</h5>';
			SetDefault();
			echo '<h5>'.$lang['import4'].'</h5>';
			echo '<a href="index.php">'.$lang['import5'].'</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="install.php">'.$lang['import6'].'</a>';
		} elseif($import==1) {
			echo '<p class="warnung">'.$lang['import11'].'</p>';
		} elseif($import==2) {
			echo '<p class="warnung">'.$lang['import12'].'</p>';
		}
		break;
		
		
		
		break;	
	case 21: //upload + import
		echo '<h4>'.$lang['import2'].'</h4>';
		echo '<form action="install.php?language='.$language.'&phase=22" method="POST" enctype="multipart/form-data">';
		echo '<table><tr><td align="center" colspan="2">';
		echo '<input type="file" name="upfile"></td><td align="center"><input type="submit" name="upload" value="'.$lang["fm_fileupload"].'">';
		echo '</td></tr></table></form>';
		
		break;
	case 22: //posting from upload
		echo '<h4>'.$lang['import7'].'</h4>';
		$backlink='<a href=install.php?language='.$language.'&phase=21">'.$lang['import8'].'</a>';
		if (isset($_POST["upload"])) 
		{ 
			$error=false; 
		   	if (!($_FILES["upfile"]["name"])) {
		     	echo "<font color=\"red\">".$lang["fm_uploadfilerequest"].'</font><br><br>'.$backlink; 
		    	exit;
			} 
			
			if (file_exists($config["paths"]["config"].$_FILES["upfile"]["name"])) unlink($config["paths"]["config"].$_FILES["upfile"]["name"]);
	        
			if ($_FILES["upfile"]["name"]!='config.gz') 
	        { 
	        	echo "<font color=\"red\">".$lang["import9"]."</font><br><br>".$backlink;
				exit;
	        } 
			if (move_uploaded_file($_FILES["upfile"]["tmp_name"],$config["paths"]["config"].$_FILES["upfile"]["name"]))
			{
				chmod($config["paths"]["config"].$upfile_name,0755); 
				
		    } else { 
				echo "<font color=\"red\">".$lang["fm_uploadmoveerror"]."<br>".$backlink; 
				exit;
		    } 
		}
		echo '<h5>'.$lang['import10'].'</h5>';
		$import=importConfig($config["paths"]["config"]."config.gz");
		if($import==0) {
			echo '<h5>'.$lang['import3'].'</h5>';
			SetDefault();
			echo '<h5>'.$lang['import4'].'</h5>';
			echo '<a href="index.php">'.$lang['import5'].'</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="install.php">'.$lang['import6'].'</a>';
		} elseif($import==1) {
			echo '<p class="warnung">'.$lang['import11'].'</p>';
		} elseif($import==2) {
			echo '<p class="warnung">'.$lang['import12'].'</p>';
		}
		break;
	case 100: //uninstall
		echo $lang['ui1'].'<br><br>';
		echo zipConfig().'<br><br>';
		echo $lang['ui2']."<br><br>";
		echo '<a href="install.php">'.$lang['ui3'].'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="install.php?language='.$language.'&phase=101">'.$lang['ui4'].'</a>';
		break;
	case 101:
		echo '<h4>'.$lang['ui5'].'</h4>';
		$paths=Array();
		$w=substr($config["paths"]["work"],0,strlen($config["paths"]["work"])-1);
		if(is_dir($w)) $res = rec_rmdir ($w); else $res=0;
		// wurde das Verzeichnis korrekt gel�scht
	    if($res==0) {
	        // das Verzeichnis wurde korrekt gel�scht
			echo '<p>'.$lang['ui6'].'</p>';
			echo $lang['ui7']."<br>\"".Realpfad("./")."\"<br> ".$lang['manuell'].".<br><br>";
			echo '<a href="../">'.$lang['ui8'].'</a>';
			
	    }else {
			echo '<p class="Warnung">'.$lang['ui9'].'"'.$paths[count($paths)-1].'"';
				
		}
		break;
	}

?>

</div>
</body>
</html>


<?
//eigene Funktionen
// rec_rmdir - loesche ein Verzeichnis rekursiv
// Rueckgabewerte:
//   0  - alles ok
//   -1 - kein Verzeichnis
//   -2 - Fehler beim Loeschen
//   -3 - Ein Eintrag eines Verzeichnisses war keine Datei und kein Verzeichnis und
//        kein Link
function rec_rmdir ($path) {
	global $paths;
	$paths[]=$path;
    // schau' nach, ob das ueberhaupt ein Verzeichnis ist
    if (!is_dir ($path)) {
        return -1;
    }
    // oeffne das Verzeichnis
    $dir = @opendir ($path);
    // Fehler?
    if (!$dir) {
        return -2;
    }
    
    // gehe durch das Verzeichnis
    while ($entry = @readdir($dir)) {
        // wenn der Eintrag das aktuelle Verzeichnis oder das Elternverzeichnis
        // ist, ignoriere es
        if ($entry == '.' || $entry == '..') continue;
        // wenn der Eintrag ein Verzeichnis ist, dann 
        if (is_dir ($path.'/'.$entry)) {
            // rufe mich selbst auf
            $res = rec_rmdir ($path.'/'.$entry);
            // wenn ein Fehler aufgetreten ist
            if ($res == -1) { // dies duerfte gar nicht passieren
                @closedir ($dir); // Verzeichnis schliessen
                return -2; // normalen Fehler melden
            } else if ($res == -2) { // Fehler?
                @closedir ($dir); // Verzeichnis schliessen
                return -2; // Fehler weitergeben
            } else if ($res == -3) { // nicht unterstuetzer Dateityp?
                @closedir ($dir); // Verzeichnis schliessen
                return -3; // Fehler weitergeben
            } else if ($res != 0) { // das duerfe auch nicht passieren...
                @closedir ($dir); // Verzeichnis schliessen
                return -2; // Fehler zurueck
            }
        } else if (is_file ($path.'/'.$entry) || is_link ($path.'/'.$entry)) {
            // ansonsten loesche diese Datei / diesen Link
            $res = @unlink ($path.'/'.$entry);
            // Fehler?
            if (!$res) {
                @closedir ($dir); // Verzeichnis schliessen
                return -2; // melde ihn
            }
        } else {
            // ein nicht unterstuetzer Dateityp
            @closedir ($dir); // Verzeichnis schliessen
            return -3; // tut mir schrecklich leid...
        }
    }
    
    // schliesse nun das Verzeichnis
    @closedir ($dir);
    
    // versuche nun, das Verzeichnis zu loeschen
    $res = @rmdir ($path);
    
    // gab's einen Fehler?
    if (!$res) {
        return -2; // melde ihn
    }
    
    // alles ok
    return 0;
}

function Rechte($file)
{
	clearstatcache();
	return @substr(decoct(fileperms($file)),-3);
}

function zipConfig()
{
	global $config;
	
	$cfname=$config["paths"]["config"]."config.gz";
	if(file_exists($cfname)) unlink($cfname);
	
	$h1="### Configuration Summary - MySQLDumper ".$config["version"]."\n\n";
	$h2="###FILE_config.php\n";
	$h3="###FILE_".$config["paths"]["config"]."sql_statements\n";
	
	$cf=$h1.$h2;
	$tmp=file("config.php");
	while(substr($tmp[0],0,18)!='$config["direct_connection"]') {
		array_shift($tmp);
		if(count($tmp)==0) break;
	}
	
	array_shift($tmp);
	$cf.=implode($tmp,"")."\n".$h3;
	if(file_exists($config["paths"]["config"]."sql_statements")) {
		$tmp=file($config["paths"]["config"]."sql_statements");
		$cf.=implode($tmp,"")."\n";
	}
	if($config["zlib"]) {
		$fp = gzopen ($cfname,"ab");
		gzwrite ($fp,$cf); 
		gzclose ($fp); 
	} else {
		$fp = fopen ($cfname,"ab");
		fwrite ($fp,$cf); 
		fclose ($fp); 
	}
		
	//return '<a href="'.$cfname.'">Download Config from '.$config["paths"]["config"].'</a>';
	
}

function importConfig($importfile)
{
	global $config;
	
	$cf1=Array();
	$imp1=Array();
	$sql1=Array();
	$tmp=Array();
	
	if(!file_exists($importfile)) exit;
	$tmp=file("config.php");
	$imp=gzfile($importfile);
	
	for($i=0;$i<count($tmp);$i++) {
		$cf1[]=$tmp[$i];
		if(substr($tmp[$i],0,18)=='$config["direct_connection"]') break;
	} 
	
	
	for($i=3;$i<count($imp);$i++) {
		
		if(substr($imp[$i],0,8)!="###FILE_") {
			$imp1[]=$imp[$i];
		} else {
			$last=$i+1;
			break;
		}
		
	}
	for($i=$last;$i<count($imp);$i++) {
		$sql1[]=$imp[$i];
	}
	$cf=array_merge($cf1,$imp1);
	
	//jetzt schreiben
	$ret=true;
	if(file_exists($config["paths"]["config"])) {
		if ($fp=fopen($config["paths"]["config"]."sql_statements", "wb"))
		{ 
			if (!fwrite($fp,implode($sql1,""))) $ret=false; 
			if (!fclose($fp)) $ret=false; 
		}
	}
	if($ret==false){
		return 1;
	} else {
		if ($fp=fopen("config.php", "wb"))
		{ 
			if (!fwrite($fp,implode($cf,""))) $ret=false; 
			if (!fclose($fp)) $ret=false; 
		}
	}
	if($ret==false){
		return 2;
	} else { 
		return 0;
	}
}

function extractValue($s)
{
	$r=trim(substr($s, strpos($s,"=")+1));
	$r=substr($r,0,strlen($r)-1);
	if(substr($r,-1)=="'")$r=substr($r,0,strlen($r)-1);
	if(substr($r,0,1)=="'")$r=substr($r,1);
	return $r;
}

function CreateDirsFTP() {

	global $install_ftp_server,$install_ftp_port,$install_ftp_user_name, $install_ftp_user_pass,$install_ftp_path,$l;
	
	 // Herstellen der Basis-Verbindung 
	 echo '<hr>'.$lang['connect_to'].' `'.$install_ftp_server.'` Port '.$install_ftp_port.' ...<br>';
    $conn_id = ftp_connect($install_ftp_server); 
    // Einloggen mit Benutzername und Kennwort 
    $login_result = ftp_login($conn_id, $install_ftp_user_name, $install_ftp_user_pass); 
    // Verbindung �berpr�fen 
    if ((!$conn_id) || (!$login_result)) { 
            echo $lang['ftp_notconnected']; 
            echo $lang['connwith']." $tinstall_ftp_server ".$lang['asuser']." $install_ftp_user_name ".$lang['notpossible']; 
            return 0; 
    } else {
		
		//Wechsel in betroffenes Verzeichnis 
		echo $lang['changedir'].' `'.$install_ftp_path.'` ...<br>';
	    ftp_chdir($conn_id,$install_ftp_path); 
		// Erstellen der Verzeichnisse 
		echo $lang['dircr1'].' ...<br>';
	    ftp_mkdir($conn_id,"work"); 
	    ftp_site($conn_id, "CHMOD 0777 work"); 
		echo $lang['changedir'].' `work` ...<br>';
		ftp_chdir($conn_id,"work"); 
		echo $lang['indir'].' `'.ftp_pwd($conn_id).'`<br>';
		echo $lang['dircr5'].' ...<br>';
		ftp_mkdir($conn_id,"config"); 
	    ftp_site($conn_id, "CHMOD 0777 config"); 
		echo $lang['dircr2'].' ...<br>';
		ftp_mkdir($conn_id,"backup"); 
	    ftp_site($conn_id, "CHMOD 0777 backup"); 
		echo $lang['dircr3'].' ...<br>';
		ftp_mkdir($conn_id,"structure"); 
	    ftp_site($conn_id, "CHMOD 0777 structure"); 
		echo $lang['dircr4'].' ...<br>';
		ftp_mkdir($conn_id,"log"); 
	    ftp_site($conn_id, "CHMOD 0777 log"); 
		 
	    // Schlie�en des FTP-Streams 
	    ftp_quit($conn_id); 
		return 1;
	}
}

function ftp_mkdirs($config,$dirname)
{
   $dir=split("/", $dirname);
   for ($i=0;$i<count($dir)-1;$i++)
   {
       $path.=$dir[$i]."/";
       @ftp_mkdir($config["dbconnection"],$path);    
   }
   if (@ftp_mkdir($config["dbconnection"],$dirname))
       return 1;
} 

function IsWritable($dir)
{
	$testfile=$dir . "/.writetest";
	if ($writable = @fopen ($testfile, 'w')) {
    	@fclose ($writable);
    	@unlink ($testfile);
    }
	return $writable;
}

function SearchDatabases($printout)
{
	global $databases,$config,$lang;
		
	if(!isset($config["dbconnection"])) MSD_mysql_connect(); 
	if(isset($config["dbonly"]) && $config["dbonly"]!='') {
		$success=@mysql_select_db($config["dbonly"],$config["dbconnection"]);
		if($success) {
		$databases["db_actual"]=$config["dbonly"];
		$databases["Name"][0]=$config["dbonly"];
		$databases["praefix"][0] = "";
		$databases["command_before_dump"][0] = "";
		$databases["command_after_dump"][0] = "";
		$databases["db_selected_index"]=0;
		if($printout==1) echo "... found db `".$config["dbonly"]."`<br>";		
		} else echo '<div style="color:red;">ERROR: no Database `'.$config["dbonly"]."` found !</div>"; 
	} else {
		$db_list = @mysql_list_dbs($config["dbconnection"]); 
		$i=0;
		if($db_list && @mysql_num_rows($db_list)>0) {
			$databases["db_selected_index"] = 0;
			while ($row = @mysql_fetch_object($db_list)) 
			{
				$databases["Name"][$i]=$row->Database;
				$databases["praefix"][$i] = "";
				$databases["command_before_dump"][$i] = "";
				$databases["command_after_dump"][$i] = "";
				
				if($printout==1) echo "... found db `$row->Database`<br>";		
			}	
			$databases["db_actual"]=$databases["Name"][0];
			$databases["db_selected_index"]=0;
		} else {
			if($printout==1) echo $lang['dbonlyneed'].'<br>';		
		}
	}
}
?>
