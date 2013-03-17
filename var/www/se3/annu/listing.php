<?php
session_start();
set_time_limit (300);

	require "config.inc.php";
	require "functions.inc.php";
	require "ihm.inc.php";


	$login=isauth();

	if (!is_admin("Annu_is_admin",$login)=="Y") {
		// dégage crétin
		echo "dégage...";
	} else {
		$listing = unserialize(stripslashes($_POST['hiddeninput']));

// Tri du listing par classe/nom/prénom
function trieleve($a,$b)
{
	//tri par classe
	$res=strcmp($a["cla"], $b["cla"]);
	
	if ($res == 0) {
		$res=strcmp($a["nom"], $b["nom"]);
		if ($res == 0) {
			$res=strcmp($a["pre"], $b["pre"]);
		}
	}

	return $res;
}

usort($listing, "trieleve");



	
	$content = "<page backtop='15mm' backbottom='15mm' >";


	foreach ($listing as $nkey => $user)
		{
			if (!isset($classe_preced)) { $classe_preced = $user['cla']; }
			if ($user['cla']!=$classe_preced) {
				$classe_preced = $user['cla'];
				$content .="</page><page backtop='15mm' backbottom='15mm' >";
			}

			$content .="<div style='margin-bottom:3mm; border: solid 1px black; padding:3mm; width: 80%; margin: auto'>";
	
			$content .="<table style='width:100%'><tr><td style='width:50%'>";
			$content .=$user['nom']." ";
			$content .=$user['pre']."<br>";
			$content .=$user['cla']."</td>";
		
			$content .="<td style='width:35%'>";
			$content .="identifiant  : <span style='font-family:courier'>".$user['uid']."</span><br>";
			$content .="mot de passe : <span style='font-family:courier'>".$user['pwd']."</span>";
			$content .="</td>";
			$content .="</tr></table>";

			$content .="<span style='font-size:70%'>Le mot de passe est strictement personnel et doit &#234;tre prot&#233;g&#233;. L'utilisateur est responsable de l'usage qui est fait de son identifiant.</span>";	
			$content .="</div>";
		}

	$content .="</page>";


	require_once(dirname(__FILE__).'/../html2pdf/html2pdf.class.php');
	$html2pdf = new HTML2PDF('P','A4','fr', false, 'ISO-8859-15');
	$html2pdf->WriteHTML($content);
	$html2pdf->Output('liste.pdf');

        $pdf = new HTML2PDF('P','A4','fr'); 

	if((isset($_POST['purge_session_data']))&&($_POST['purge_session_data']=='y')) {
		unset($_SESSION['comptes_crees']) ;	
	}
	if((isset($_POST['purge_csv_data']))&&($_POST['purge_csv_data']=='y')) {
		if (file_exists("/tmp/changement_mdp.csv")) {
			unlink("/tmp/changement_mdp.csv"); 
		}	
	}
}
?>
