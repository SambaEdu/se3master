<?php


   /**
   
   * Export un csv des comptes existants
   * @Version $Id$ 
   
   * @Projet LCS / SambaEdu 
   
   * @auteurs jLCF jean-luc.chretien@tice.ac-caen.fr
   * @auteurs oluve olivier.le_monnier@crdp.ac-caen.fr
   * @auteurs wawa  olivier.lecluse@crdp.ac-caen.fr
   * @auteurs Equipe Tice academie de Caen

   * @Licence Distribue selon les termes de la licence GPL
   
   * @note 
   */

   /**

   * @Repertoire: annu
   * file: export_csv.php
   */



include "ldap.inc.php";
include "ihm.inc.php";

require_once ("lang.inc.php");
bindtextdomain('se3-annu',"/var/www/se3/locale");
textdomain ('se3-annu');

$testaction=$_GET['download'];


if (!$testaction) {
	include "entete.inc.php";

	// Aide
	$_SESSION["pageaide"]="Annuaire";

	echo "<h1>".gettext("Annuaire")."</h1>";
	
	$filter="Classe_*";
  	$group=search_groups ("(cn=".$filter.")");
  	$uids = search_uids ("(cn=".$filter.")");
  	$people = search_people_groups ($uids,"(sn=*)","group");

        for ($loop=0; $loop < count($people); $loop++) {
      		preg_match("/([0-9]{8})/",$people[$loop]["gecos"],$naiss);
		$ligne_eleve=$ligne_eleve.$people[$loop]["group"].";".$people[$loop]["fullname"].";".$people[$loop]["uid"].";".$naiss[0]."\r\n";
    	}
	$content_dir = '/tmp/';
	$file_temp='export_eleves.csv';
	$get= fopen("/tmp/export_eleves.csv", "w+");
	fputs($get,$ligne_eleve);
	fclose($get);

  	$filter="Profs";
  	$group=search_groups ("(cn=".$filter.")");
  	$uids = search_uids ("(cn=".$filter.")");
  	$people = search_people_groups ($uids,"(sn=*)","group");

        for ($loop=0; $loop < count($people); $loop++) {
      		preg_match("/([0-9]{8})/",$people[$loop]["gecos"],$naiss);
		$ligne_prof=$ligne_prof.$people[$loop]["fullname"].";".$people[$loop]["uid"].";".$naiss[0]."\r\n";
    	}
	$content_dir = '/tmp/';
	$file_temp='export_profs.csv';
	$get= fopen("/tmp/export_profs.csv", "w+");
	fputs($get,$ligne_prof);
	fclose($get);

	echo gettext("Les fichiers export_eleves.csv et export_profs.csv ont &#233;t&#233; g&#233;n&#233;r&#233;s")."<br><br>";
	echo "<a href=\"export_csv.php?download=export_eleves\">".gettext("T&#233;l&#233;charger le fichier &#233;&#232;lves")."</a><br>";
	echo "<a href=\"export_csv.php?download=export_profs\">".gettext("T&#233;l&#233;charger le fichier profs")."</a>";
	
	include ("pdp.inc.php");


} elseif ($testaction=="export_eleves") {
	require ("config.inc.php");
	include "functions.inc.php";
	$login=isauth();
	if ($login == "") header("Location:$urlauth");
	if (is_admin("se3_is_admin",$login)=="Y") {

		header("Content-Type: octet-stream");
		header("Content-Length: ".filesize ("/tmp/export_eleves.csv") );
		header("Content-Disposition: attachment; filename=\"/tmp/export_eleves.csv\"");
		include ("/tmp/export_eleves.csv");
	}


} elseif ($testaction=="export_profs") {
	require ("config.inc.php");
	include "functions.inc.php";
	$login=isauth();
	if ($login == "") header("Location:$urlauth");
	if (is_admin("se3_is_admin",$login)=="Y") {

		header("Content-Type: octet-stream");
		header("Content-Length: ".filesize ("/tmp/export_profs.csv") );
		header("Content-Disposition: attachment; filename=\"/tmp/export_profs.csv\"");
		include ("/tmp/export_profs.csv");

	}
}
?>
