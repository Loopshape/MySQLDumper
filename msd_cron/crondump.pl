#!/usr/bin/perl -w
########################################################################################
# MySQLDumper CronDump
#
# 2004,2005 by Steffen Kamper
# additional scripting: Detlev Richter
#
# for support etc. visit http://www.mysqldumper.de/board
# (c) GNU General Public License
########################################################################################
# Script-Version
my $pcd_version="1.04";

########################################################################################
# please enter the absolute path of the config-dir
# for using the script without Parameters the mysqldumper.conf will be load
# e.g.: 
#my $absolute_path_of_configdir="/home/user1234/public_html/mysqldumper/work/config/";
#
my $absolute_path_of_configdir="";
my $default_configfile="mysqldumper.conf";
########################################################################################

use strict;
use DBI;
use File::Find;
use File::Basename;
use Compress::Zlib;
use Net::FTP;
use MIME::Lite;
use CGI::Carp qw/ warningsToBrowser fatalsToBrowser /;

########################################################################################
# nothing to edit under this line !!!
########################################################################################

use vars qw($pcd_version  $dbhost  $dbname  $dbuser  $dbpass
$cron_save_all_dbs $cron_db_array $cron_dbpraefix_array $dbpraefix $command_beforedump_array $command_afterdump_array
$compression  $backup_path $logdatei  $completelogdatei $nl $command_beforedump $command_afterdump
$cron_printout  $cronmail  $cronmail_dump $cronmailto $cronmailfrom
$cronftp $ftp_server $ftp_port $ftp_user $ftp_pass $ftp_dir $mp $multipart_groesse $email_maxsize
$auto_delete $cron_del_files_after_days $max_backup_files $max_backup_files_each $perlspeed $optimize_tables_beforedump $result
@key_value $pair $key $value $conffile @confname);


my @trash_files;
my $time_stamp;
my @filearr;
my $sql_file;
my $backupfile;
my $memory_limit=100000;
my $dbh;        # DBI databasehandle
my $sth;        # DBI statementhandle
my @db_array;
my @dbpraefix_array;
my @db_command_beforedump_array;
my @db_command_afterdump_array;
my $db_anz;
my $record_count;
my $filesize;
my $status_start;
my $status_end;
my $sql_text;
my $punktzaehler;
my @backupfiles_name;
my @backupfiles_size;



use vars qw/
$starttime $Sekunden $Minuten $Stunden $Monatstag $Monat $Jahr $Wochentag $Jahrestag $Sommerzeit
    $ri $rct $tabelle  @tables @tablerecords $dt $sql_create @ergebnis @ar $sql_daten $inhalt
        $insert $totalrecords $error_message $cfh $oldbar $print_out $msg $dt $ftp $dateistamm $dateiendung
        $mpdatei $i $BodyNormal $BodyMultipart $BodyToBig $BodyNoAttach $Body $DoAttach $cmt $part $fpath $fname
        $fmtime $timenow $daydiff $datei $inh $gz $search $fdbname @str @dbarray $item %dbanz $anz %db_dat
/;

# Script Start
die "absolute_path_of_configdir is empty !\nYou have to edit the crondump.pl and enter the absolute_path_of_configdir !\n\n" if($absolute_path_of_configdir eq "");


opendir(DIR, $absolute_path_of_configdir) or die "The config-directory you entered is wrong !\n($absolute_path_of_configdir - $!) \n\nPlease edit the crondump.pl and enter the right configuration-path.\n\n";
closedir(DIR);

my $abc=length($absolute_path_of_configdir)-1;
my $defed=substr($absolute_path_of_configdir,$abc,1);
if($defed ne "/") {
    $absolute_path_of_configdir=$absolute_path_of_configdir."/";
}


#include config file
if($ENV{QUERY_STRING}) {
        @key_value = split(/&/,$ENV{QUERY_STRING});
        foreach $pair(@key_value){
           $pair =~ tr/+/ /;
           ($key,$value) = split(/=/,$pair);
           $conffile=$value if($key eq "config");
        }
}
foreach (@ARGV) {
  if($_) {
          $conffile=substr($_,7,length($_)-7)  if(substr($_,0,7) eq "config=");
  }
}

if(!$conffile) { $conffile=$default_configfile; }
require("$absolute_path_of_configdir$conffile");
@confname=split(/\//,$conffile);
# Output Headers
PrintHeader();
PrintOut("Config '".$confname[$#confname]."' was loaded.<br><br>");



# SignalHandler einrichten f�r Browser-Stop-Button,
# unterbrochene Socketverbindungen, Crtl-C
# Datenbankverbindung wird dann noch ordnungsgem�ss geschlossen.
$SIG{HUP} = $SIG{PIPE} = $SIG{INT} =\&closeScript;


#teste Zugriff auf logfile
PrintOut("Starting Crondump ...     ");
write_log(": starting perl crondump $pcd_version\n");
PrintOut("<font color=#0000FF>ok, logging on<br></font>");


#Auto-Delete ausf�hren
if($auto_delete>0)
{
        #Autodelete Days
        if($cron_del_files_after_days>0)
        {
                PrintOut("<hr>Autodelete: search for backups older than <font color=\"#0000FF\">$cron_del_files_after_days</font> days ...<br>");
                find(\&AutoDeleteDays, $backup_path);
                DeleteFiles (\@trash_files);
        }
        #Autodelete Count
        if($max_backup_files>0)
        {
                PrintOut("Autodelete: search for more backups than <font color=\"#0000FF\">$max_backup_files</font>  ...<hr>");
                find(\&AutoDeleteCount, $backup_path);
                DoAutoDeleteCount();
                DeleteFiles (\@trash_files);
        }
}
#Jetzt den Dump anschmeissen
# mal schauen, obs mehrere DB's sind
if($cron_save_all_dbs==0) {
    $command_beforedump=$command_beforedump_array;
        $command_afterdump=$command_afterdump_array;
        ExecuteCommand(1);
        DoDump();
        ExecuteCommand(2);
        PrintOut("<br><strong>Crondump finished.</strong><br>");
        closeScript();
}  else {
    @db_array=split(/\|/,$cron_db_array);
    @dbpraefix_array=split(/\|/,$cron_dbpraefix_array);
    @db_command_beforedump_array=split(/\|/,$command_beforedump_array);
    @db_command_afterdump_array=split(/\|/,$command_afterdump_array);
    $db_anz=@db_array;
    PrintOut("<h4>backup $db_anz Databases ...</h4>");
    for(my $ii = 0; $ii < $db_anz; $ii++) {
        $dbname=$db_array[$ii];
                $dbpraefix=($dbpraefix_array[$ii]) ? $dbpraefix_array[$ii] : "";
                $command_beforedump=($db_command_beforedump_array[$ii]) ? $db_command_beforedump_array[$ii] : "";
                $command_afterdump=($db_command_afterdump_array[$ii]) ? $db_command_afterdump_array[$ii] : "";
        PrintOut("<hr><h5>Start backup ".($ii+1)." of $db_anz with database `$dbname` ".(($dbpraefix ne "") ? "(with praefix <span style=\"color:blue\">$dbpraefix</span>)" : "")."</h5>");
        ExecuteCommand(1);
                DoDump();
                ExecuteCommand(2);
    }
        PrintOut("<hr><hr><hr><strong>ALL $db_anz BACKUPS ARE COMPLETE !!!</strong><hr><hr><hr>");
        $cron_save_all_dbs=2;
        closeScript();
}


##############################################
# Subroutinen                                #
##############################################
sub DoDump
{
        ($Sekunden, $Minuten, $Stunden, $Monatstag, $Monat, $Jahr, $Wochentag, $Jahrestag, $Sommerzeit) = localtime(time);
        $Jahr+=1900;$Monat+=1;$Jahrestag+=1;
        my $CTIME_String = localtime(time);
        $time_stamp=$Jahr."_".sprintf("%02d",$Monat)."_".sprintf("%02d",$Monatstag)."_".sprintf("%02d",$Stunden)."_".sprintf("%02d",$Minuten);
        $starttime=        sprintf("%02d",$Monatstag).".".sprintf("%02d",$Monat).".".$Jahr."  ".sprintf("%02d",$Stunden).":".sprintf("%02d",$Minuten);

        # Verbindung mit mSQL herstellen, $dbh ist das Database Handle
        PrintOut("connect to database`$dbname`&nbsp;&nbsp;&nbsp;");
        $dbh = DBI->connect("DBI:mysql:$dbname:$dbhost","$dbuser","$dbpass")|| die   "Database connection not made: $DBI::errstr";
        # herausfinden welche Mysql-Version verwendet wird
        $sth = $dbh->prepare("SELECT VERSION()");
        $sth->execute;
        my @mysql_version=$sth->fetchrow;
        my @v=split(/\./,$mysql_version[0]);
        if($v[0]>=4 && $v[1]>=1 ){
                #mysql Version >= 4.1
                %db_dat = (name => 0,
                           rows => 4,
                           data_length =>6,
                           index_length =>8,
                           update_time =>11 );
        }
        else{
                #mysql Version < 4.1
                %db_dat = (name => 0,
                           rows => 3,
                           data_length =>5 ,
                           index_length =>7,
                           update_time =>11 );
        }

        PrintOut("<font color=#0000FF>ok</font><br><strong>MySQL-Version $v[0].$v[1].$v[2]</strong><br>start Backup <strong>".$starttime."</strong><br>");
        #Statuszeile erstellen
        my $t;my $r;
        $t=0;$r=0;
        $sth = $dbh->prepare("SHOW TABLE STATUS FROM `$dbname`");
        $sth->execute;
        my $st_e="\n";
        #Arrays l�schen
        undef(@tables);
        undef(@tablerecords);
        my $opttbl=0;

                while ( @ar=$sth->fetchrow) {
            if($optimize_tables_beforedump==1) {
                    #tabelle optimieren
                    my $sth_to = $dbh->prepare("OPTIMIZE Table `$ar[$db_dat{name}]`");
                       $sth_to->execute; $opttbl++;
            }
            if($dbpraefix eq ""){
                $t++;$r+=$ar[$db_dat{rows}];
                        push(@tables,$ar[$db_dat{name}]);
                        push(@tablerecords,$ar[$db_dat{rows}]);
                        $st_e.="# TABLE\|$ar[$db_dat{name}]\|$ar[$db_dat{rows}]\|".($ar[$db_dat{data_length}]+$ar[$db_dat{index_length}])."\|$ar[$db_dat{update_time}]\n";
            } else {
               if (substr($ar[$db_dat{name}],0,length($dbpraefix)) eq $dbpraefix) {
                   $t++; $r+=$ar[$db_dat{rows}];
                           push(@tables,$ar[$db_dat{name}]);
                           push(@tablerecords,$ar[$db_dat{rows}]);
                           $st_e.="# TABLE\|$ar[$db_dat{name}]\|$ar[$db_dat{rows}]\|".($ar[$db_dat{data_length}]+$ar[$db_dat{index_length}])."\|$ar[$db_dat{update_time}]\n";
               }
            }
        }
        PrintOut("<span style=\"color:blue;font-size:11px;\">$opttbl tables were optimized</span><br>") if($opttbl>0) ;
        PrintOut("found $t tables with $r records.<br><br><br>");

        $status_start="# Status:$t:$r:";
        $status_end=":$dbname:perl:$pcd_version";
        $status_end.="::EXTINFO$st_e\n# Dump created on $CTIME_String by PERL Cron-Script\n# Dump by MySQLDumper 1.14 Preview (http://www.mysqldumper.de/board/)\n\n";


        if($mp>0) {
                $sql_text=$status_start."MP_$mp".$status_end;
        } else {
                $sql_text=$status_start.$status_end;
        }
        NewFilename();
        #WriteToFile($sql_text);
        #$sql_text="";

        #@tables=$dbh->tables;
        $totalrecords=0;
        for (my $tt=0;$tt<$t;$tt++) {
          $tabelle=$tables[$tt];
          # definition auslesen
          if($dbpraefix eq "" or ($dbpraefix ne "" && substr($tabelle,0,length($dbpraefix)) eq $dbpraefix)) {

           PrintOut("dumping table `$tabelle` ");
           $a="DROP TABLE IF EXISTS `".$tabelle."`;\n";
           $sql_text.=$a;
           $sql_create="Show create table `".$tabelle."`";
           $sth = $dbh->prepare($sql_create);
           $sth->execute;
           @ergebnis=$sth->fetchrow;
           $sth->finish;
           $a=$ergebnis[1].";\n";
           $sql_text.=$a;
           WriteToFile($sql_text);
           $sql_text="";
           PrintOut("<font color=blue>* </font>");
           $punktzaehler=64;
           # daten auslesen
           $rct=$tablerecords[$tt];
           for (my $ttt=0;$ttt<$rct;$ttt+=$perlspeed) {
                    $insert = "INSERT INTO $tabelle VALUES (";

                   $sql_daten="SELECT * FROM `$tabelle` Limit ".$ttt.",".$perlspeed.";";
                   $sth = $dbh->prepare($sql_daten);
                   $sth->execute;
                   while ( @ar=$sth->fetchrow) {
                    $a=$insert;
                    foreach $inhalt(@ar)
                     {$a.= $dbh->quote($inhalt).", ";}
                    $a=substr($a,0, length($a)-2).");";
                    $sql_text.= $a."\n";
                        if($memory_limit>0 && length($sql_text)>$memory_limit) {
                                WriteToFile($sql_text);
                                $sql_text="";
                                if($mp>0 && $filesize>$multipart_groesse) {        NewFilename();}
                        }
                }
           }
           #jetzt wegschreiben
           WriteToFile($sql_text);
       $sql_text="";
           PrintOut("<br>&nbsp;&nbsp;&nbsp;<em>$tablerecords[$tt] inserted records (backupfile now $filesize Bytes)</em><br>");
           $totalrecords+=$tablerecords[$tt];
           if($mp>0 && $filesize>$multipart_groesse) {NewFilename();}
          }
        }
        # Ende
        PrintOut("<hr>Backup of Database `$dbname` complete.<br>");
        write_log(": Perl Cron-Dump `$backupfile` finished.\n");

        # Jetzt der Versand per Mail
        if($cronmail==1)
        {
                PrintOut("sending mail ...<br>");
                send_mail();
                write_log(": Mail was sent to $cronmailto.\n");
                PrintOut("Mail was sent to $cronmailto.<br>");
        }

        # Jetzt der Versand per FTP
        if($cronftp==1)
        {
                PrintOut("sending ftp ...<br>");
                send_ftp($sql_file);
        }
}

#Wird aufgerufen, wenn Fehler passieren
sub err_trap
{
    $error_message = shift(@_);
    print "Perl Cronscript ERROR: $error_message \n";
        close;
        #write_log(": Perl Cronscript ERROR: $error_message \n");
    #PrintOut("ERROR: $error_message \n");
    #close;
}

sub PrintHeader
{
    PrintOut("Content-type: text/html\n\n");
        PrintOut("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n");
    PrintOut("<html><head><title>MySQLDumper - Perl CronDump [Version $pcd_version]</title></head><body><h3>MySQLDumper - Perl CronDump [Version $pcd_version]</h3>\n");
}

sub PrintOut
{
        $print_out = shift(@_);
        if($cron_printout==1)
    {
                local ($oldbar) = $|;
                $cfh = select (STDOUT);
                 $| = 1;

                print "$print_out\n";

                $| = $oldbar;
                 select ($cfh);
        }
        open(DATEI,">>$completelogdatei") || err_trap("can't open file ($completelogdatei).");
        print DATEI "$print_out\n" || err_trap("can't write to file ($completelogdatei).");
        close(DATEI)|| err_trap("can't close file ($completelogdatei).");
}

sub write_log
{
        $msg = shift(@_);
        ($Sekunden, $Minuten, $Stunden, $Monatstag, $Monat, $Jahr, $Wochentag, $Jahrestag, $Sommerzeit) = localtime(time);
        $Jahr+=1900; $Monat+=1;$Jahrestag+=1;
        $dt=sprintf("%02d",$Monatstag).".".sprintf("%02d",$Monat).".".sprintf("%02d",$Jahr)." ".sprintf("%02d",$Stunden).":".sprintf("%02d",$Minuten).":".sprintf("%02d",$Sekunden);
        open(DATEI,">>$logdatei") || err_trap("can't open file ($logdatei).");
        print DATEI "$dt $msg" || err_trap("can't write to file ($logdatei).");
        close(DATEI)|| err_trap("can't close file ($logdatei).");
}

sub send_ftp
{
    $ftp = Net::FTP->new($ftp_server, Port => $ftp_port, Timeout => 360, Debug   => 1)    or err_trap( "FTP-ERROR: Can't connect: $@\n");
    $ftp->login($ftp_user, $ftp_pass)   or err_trap("FTP-ERROR: Couldn't login\n");
    $ftp->binary();
    $ftp->cwd($ftp_dir)     or err_trap("FTP-ERROR: Couldn't change directory\n");
        if($mp==0)
        {
                $ftp->put($sql_file)    or err_trap("FTP-ERROR: Couldn't put $sql_file\n");
                write_log(": FTP-Transfer `$backupfile` to $ftp_server completed.\n");
                PrintOut("FTP-Transfer `$backupfile` to $ftp_server completed.<br>");
        }
        else
        {
                $dateistamm=substr($backupfile,0,index($backupfile,"part_"))."part_";
                $dateiendung=($compression==1)?".sql.gz":".sql";
                $mpdatei="";
                for ($i=1;$i<$mp;$i++) {
                        $mpdatei=$dateistamm.$i.$dateiendung;
                        $ftp->put($backup_path.$mpdatei)    or err_trap("Couldn't put $backup_path.$mpdatei\n");
                        write_log(": FTP-Transfer `$mpdatei` to $mpdatei completed.\n");
                        PrintOut("FTP-Transfer `$mpdatei` to $ftp_server completed.<br>");
                }
        }
}

sub send_mail
{
       MIME::Lite->send("sendmail", "/usr/lib/sendmail -t -oi -oem");
        $BodyNormal='In der Anlage findest Du die Sicherung Deiner MySQL-Datenbank.<br>Sicherung der Datenbank '.$dbname.'<br><br>Viele Gr&uuml;sse<br><br>MySQLDumper<br><a href="http://www.mysqldumper.de/">www.mysqldumper.de</a>';
        $BodyMultipart='Es wurde eine Multipart-Sicherung erstellt. Die Sicherungen werden nicht als Anhang mitgeliefert!<br>Sicherung der Datenbank `'.$dbname.'`<br><br>Folgene Dateien wurden erzeugt:<br><br>';
        $BodyToBig='Die Sicherung &uuml;berschreitet die Maximalgr&ouml;sse von '.$email_maxsize.' Bytes  und wurde daher nicht angeh&auml;ngt.<br>Sicherung der Datenbank '.$dbname.'<br><br>Viele Gr&uuml;sse<br><br>MySQLDumper<br><a href="http://www.mysqldumper.de/">www.mysqldumper.de</a><br><br>Folgene Datei wurde erzeugt:<br><br>';
        $BodyNoAttach='Das Backup wurde nicht angeh&auml;ngt.<br>Sicherung der Datenbank `'.$dbname.'`<br>';
        $Body="";
        $DoAttach=1;

        if($mp==0)
        {
                if(($email_maxsize>0 && $filesize>$email_maxsize) || $cronmail_dump==0)
                {
                        if($cronmail_dump==0)
                        {
                                $Body=$BodyNoAttach.$backupfile."&nbsp;&nbsp;&nbsp;".$filesize;
                        } else {
                                $Body=$BodyToBig.$backupfile."&nbsp;&nbsp;&nbsp;".$filesize;
                        }
                        $DoAttach=0;
                }
                else
                {
                        $Body=$BodyNormal;
                }
        }
        else
        {
                $Body=$BodyMultipart;
                $dateistamm=substr($backupfile,0,index($backupfile,"part_"))."part_";
                $dateiendung=($compression==1)?".sql.gz":".sql";
                $mpdatei="";
                for ($i=1;$i<$mp;$i++) {
                                $mpdatei=$dateistamm.$i.$dateiendung;
                                $Body.="$mpdatei (".(stat($backup_path.$mpdatei))[7]." Bytes)<br>";
                }
                $Body.='<br><br><br>Viele Gr&uuml;sse<br><br>MySQLDumper<br><a href="http://www.mysqldumper.de/">www.mysqldumper.de</a>';
                $DoAttach=0;
        }


    $msg = new MIME::Lite ;
        $msg = build MIME::Lite
        From    => $cronmailfrom ,
        To      => $cronmailto ,
        Subject => "MySQLDumper Perl Crondump" ,
        Type    => 'text/html',
        Data    => $Body;
        if($DoAttach==1)
        {
                   attach $msg
               Type     => "application/gzip" ,
               Path     => "$sql_file" ,
               Encoding => "base64",
               Filename => "$backupfile" ;
        }

        $msg->send || err_trap("Error sending mail !");

}


sub check_emailadr
{
        $cmt = shift(@_);
    if ($cmt =~ /^([\w\-\+\.]+)@([\w\-\+\.]+)$/)
                { return (1) }
        else
            { return (0) }
}

sub NewFilename
{
    $part="";
    if($mp>0)
    {
        $part="_part_$mp";
        $mp++;
    }
    if($compression==0)
    {
        $sql_file=$backup_path.$dbname."_".$time_stamp."_crondump_perl".$part.".sql";
        $backupfile=$dbname."_".$time_stamp."_crondump_perl".$part.".sql";
    }
    else
    {
        $sql_file=$backup_path.$dbname."_".$time_stamp."_crondump_perl".$part.".sql.gz";
        $backupfile=$dbname."_".$time_stamp."_crondump_perl".$part.".sql.gz";
    }
    if($mp==0)
    {
       PrintOut("<br>Start Perl Cron-Dump with file `$backupfile`<br>");
       write_log(": Start Perl Cron-Dump  with file `$backupfile` \n");
    }
    if($mp==2)
    {
       PrintOut("<br>Start Perl Multipart-Cron-Dump with file `$backupfile`<br>");
       write_log(": Start Perl Multipart-Cron-Dump with file `$backupfile` \n");
    }
    if($mp>2)
    {
       PrintOut("<br>Continue Perl Multipart-Cron-Dump with file `$backupfile`<br>");
       write_log(": Continue Perl Multipart-Cron-Dump with file `$backupfile` \n");
    }

        if($mp>0) {
                $sql_text=$status_start."MP_".($mp-1).$status_end;
        } else {
                $sql_text=$status_start.$status_end;
        }
        WriteToFile($sql_text);
        $sql_text="";
        $punktzaehler=0;
        push(@backupfiles_name,$sql_file);
}

sub WriteToFile {
        $inh=shift;
    if(length($inh)>0) {
            if($compression==0){
                    open(DATEI,">>$sql_file");
                    print DATEI $inh;
                    close(DATEI);
            } else {
               $gz = gzopen($sql_file, "ab") || err_trap("Cannot open : ".$gz->gzerror());
               $gz->gzwrite($inh)  || err_trap("Error writing: ".$gz->gzerror());    ;
               $gz->gzclose ;
            }
                PrintOut("<font color=red>.</font>");
                $filesize= (stat($sql_file))[7];
                $punktzaehler++;
                if($punktzaehler>180){
                        $punktzaehler=0;
                        PrintOut("<br>");
                }
        }
}

sub AutoDeleteDays {
    $fpath=$File::Find::name;
    $fname=basename($fpath);
    $fmtime=(stat($fname))[9];
        if($fmtime) {
        $timenow=time;
        $daydiff=($timenow-$fmtime)/60/60/24;
        if (((/\.gz$/) || (/\.sql$/)) && ($daydiff>$cron_del_files_after_days)) {
           print "$fpath $daydiff <br>" if (-f $fname);
           push(@trash_files,$fpath);
        }
        }
}

sub AutoDeleteCount {
        $fpath=$File::Find::name;
        $fname=basename($fpath);
        $fmtime=(stat($fname))[9];
        if($fmtime) {
                ($Sekunden, $Minuten, $Stunden, $Monatstag, $Monat, $Jahr)=localtime($fmtime);
                $Jahr+=1900; $Monat+=1;
                $Monat="0".$Monat if(length($Monat)==1);
                $search="_".$Jahr."_".$Monat."_";
                $fdbname=substr($fname,0,index($fname,$search));
                if ((/\.gz$/) || (/\.sql$/))  {
                        push(@filearr,"$fmtime|$fdbname|$fpath");
                }
        }
}

sub DoAutoDeleteCount
{
        my @str;
        my @dbarray;
        my $item;
        my %dbanz;
        my $anz=@filearr;
        @filearr=sort(@filearr);
        if($max_backup_files_each==0)
        {
                PrintOut("Autodelete by Count ($max_backup_files) => found $anz Backups<br>");
                 for($i = 0; $i < ($anz-$max_backup_files); $i++) {
                           @str=split(/\|/,$filearr[$i]);
                           push(@trash_files, $str[2]);
                 }
        }
        else
        {
                PrintOut("Autodelete by Count each DB ($max_backup_files) => found $anz Backups<br>");
                foreach $item (@filearr)
        {
            @str=split(/\|/,$item);
            $dbanz{$str[1]}++;
            push(@trash_files, $str[2]) if($dbanz{$str[1]}>$max_backup_files);
        }
        }
}

sub DeleteFiles
{

   if(@trash_files==0)
   {
               PrintOut("<font color=red><b>no file to delete.</b></font><br>");
   } else {
                foreach $datei(@trash_files)
                {
                        PrintOut("<font color=red><b>".$datei." deleted.</b></font><br>");
                         write_log( ": Perl Cronsript Autodelete - $datei deleted.\n" ) ;
                }
   }
   unlink @trash_files ;
   undef(@trash_files);
}

sub ExecuteCommand
{
        my $cmt = shift(@_);
        my (@cad, $errText, $succText, $cd2, $commandDump);

        if($cmt==1) {  #before dump
            $commandDump=$command_beforedump;
                $errText="Error while executing Query before Dump";
            $succText="executing Query before Dump was successful";
                write_log(": Executing Command before dump\n");
        } else {
                $commandDump=$command_afterdump;
            $errText="Error while executing Query after Dump";
            $succText="executing Query after Dump was successful";
                write_log(": Executing Command after dump\n");
        }
        if(length($commandDump)>0) {
                #jetzt ausf�hren
                if(substr($commandDump,0,7) ne "system:") {
                        $dbh = DBI->connect("DBI:mysql:$dbname:$dbhost","$dbuser","$dbpass")|| die   "Database connection not made: $DBI::errstr";
                    $dbh->{PrintError} = 0;
                    if(index($commandDump,";")>=0) {
                            @cad=split(/;/,$commandDump);
                        for($i=0;$i<@cad;$i++) {
                                if($cad[$i] ne ""){
                                    write_log(": Executing Command ($cad[$i]\n");
                                                $sth = $dbh->prepare($cad[$i]);
                                $sth->execute;
                                $sth->finish;
                             }
                        }
                    } else {
                                write_log(": Executing Command ($commandDump)\n");
                        if($commandDump) {
                                $sth = $dbh->prepare($commandDump);
                            $sth->execute;
                            $sth->finish;
                        }
                    }
                    if($@){
                                my $ger=$@;
                                PrintOut("<p style=\"color:red;\">$errText ($commandDump):<br>$ger</p>");
                        write_log(": $errText ($commandDump): $ger\n");
                    } else {
                        PrintOut("<p style=\"color:blue;\">$succText</p>");
                        write_log(": $succText\n");
                    }
                } else {
                   #Systembefehl
                        $commandDump=substr($commandDump,7);
                        system($commandDump);
                        PrintOut("<p style=\"color:blue;\">$succText ($commandDump)</p>");
                                write_log(": $succText ($commandDump)\n");
                }
        }
}

sub closeScript
{
        my $Start; my $Jetzt; my $Totalzeit;
		$Start = $^T; $Jetzt = (time); 
		$Totalzeit=$Jetzt - $Start;
		($Sekunden, $Minuten, $Stunden, $Monatstag, $Monat, $Jahr, $Wochentag, $Jahrestag, $Sommerzeit) = localtime(time);
        $Jahr+=1900;$Monat+=1;$Jahrestag+=1;
        $starttime=        sprintf("%02d",$Monatstag).".".sprintf("%02d",$Monat).".".$Jahr."  ".sprintf("%02d",$Stunden).":".sprintf("%02d",$Minuten);
        if($cron_save_all_dbs!=1) {
                PrintOut("closing script <strong>$starttime</strong>, bye<hr><em>total time used: $Totalzeit sec.</em></body></html>\n");
                # Datenbankverbindung schliessen
                $sth->finish() if (defined $sth);
                ($dbh->disconnect() || warn $dbh->errstr) if (defined $dbh);
        }

}