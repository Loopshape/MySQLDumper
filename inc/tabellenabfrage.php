<?php

$tblr=($tblfrage_refer=="dump") ? "Backup" : $tblr="Restore";
$filename=(isset($_GET['filename'])) ? $_GET['filename'] : "";

//Informationen zusammenstellen
if($tblr=="Backup") {
	//Info aus der Datenbank lesen
	MSD_mysql_connect(); 
	$res=mysql_query("SHOW TABLE STATUS FROM `".$databases["db_actual"]."`");
	$numrows=mysql_num_rows($res);
	$button_name='dump_tbl';
	$button_caption=$lang['startdump'];
	$tbl_zeile="";
	for($i=0;$i<$numrows;$i++) {
		$row=mysql_fetch_array($res);
		
		$tbl_zeile.='<tr><td class="sm"><input type="checkbox" name="chk_tbl" value="'.$row["Name"].'">'.$row["Name"].'</td>';
		$tbl_zeile.='<td class="sm"><strong>'.$row["Rows"].'</strong> '.$lang['datawith'].' <strong>'.byte_output($row["Data_length"]+$row["Index_length"]).'</strong>, '.$lang['lastbufrom'].' <span class="Blau">'.$row["Update_time"].'</span></td></tr>';
	}
} else {
	//Restore - Header aus Backupfile lesen
	$button_name='restore_tbl';
	$button_caption="";
	$gz = (substr($filename,-3))==".gz" ? 1 : 0; 
	if ($gz)
	{
		$fp = gzopen ($fpath.$filename, "r");
		$statusline=gzgets($fp,40960);
		$offset= gztell($fp);
	} else 	{
		$fp = fopen ($fpath.$filename, "r");
		$statusline=fgets($fp,5000);
		$offset= ftell($fp);
	}
	//Header auslesen
	$sline=ReadStatusline($statusline);
	$anzahl_tabellen=$sline[0];
	$anzahl_eintraege=$sline[1];
	$part=($sline[2]=="") ? 0 : substr($sline[2],3);
	$EXTINFO= $sline[6];
	if($EXTINFO=="") {
		$tbl_zeile.='<tr><td class="sm" colspan="2">'.$lang['not_supported'].'</td>';
	} else {
		for($i=0;$i<$anzahl_tabellen;$i++) {
			if ($gz)
			{
				gzseek($fp,$offset);
				$statusline=gzgets($fp,40960);
				$offset= gztell($fp);
			} else 	{
				fseek($fp,$offset);
				$statusline=fgets($fp,5000);
				$offset= ftell($fp);
			}
			$s=explode("|",$statusline);
			$tbl_zeile.='<tr><td class="sm"><input type="checkbox" name="chk_tbl" value="'.$s[1].'">'.$s[1].'</td>';
			$tbl_zeile.='<td class="sm"><strong>'.$s[2].'</strong> '.$lang['datawith'].' <strong>'.byte_output($s[3]).'</strong>, '.$lang['lastbufrom'].' <span class="Blau">'.$s[4].'</span></td></tr>';
		}	
		if($gz) gzclose ($fp); else fclose ($fp);
	}
}
echo '<h3>'.$tblr.' - '.$lang['tableselection'].'</h3><h6>'.$lang['db'].': '.$databases["db_actual"].'</h6><div align="center">';
echo '<form name="frm_tbl" action="filemanagement.php" method="post" onSubmit="return chkFormular()"><table width="90%" border="1">';
echo '<tr><td colspan="2" class="hd">&nbsp;&nbsp;<input type="Button" class="Formbutton2" onclick="Sel(true);" value="'.$lang['deselectall'].'">&nbsp;&nbsp;<input type="Button" class="Formbutton2" onclick="Sel(false);" value="'.$lang['selectall'].'">';
echo '<img src="images/blank.gif" alt="" width="80" height="1" border="0"><input type="submit" class="Formbutton" style="width:180px;" name="'.$button_name.'" value="'.$button_caption.'"></td></tr>';
echo $tbl_zeile;
echo '</table><input type="hidden" name="tbl_array" value=""><input type="hidden" name="filename" value="'.$filename.'"></form></div>';


?>
