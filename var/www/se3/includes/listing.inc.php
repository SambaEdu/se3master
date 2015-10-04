<?php

if (count($_SESSION['comptes_crees']) >= 1) {
	$serial_listing=rawurlencode(serialize($_SESSION['comptes_crees']));
	$lien="<a href=\"#\" onclick=\"document.getElementById('postlisting').submit(); return false;\" target=\"_blank\">T&#233;l&#233;charger le listing des derniers utilisateurs cr&#233;&#233;s...</a>";

	echo("<table><tr><td><img src='../elements/images/pdffile.png'></td><td>");
	echo($lien);
	echo("<br />Attention, les donn&#233;es ne seront pas conserv&#233;es au del&#224; de la session en cours<br />");

	echo("<form id='postlisting' action='../annu/listing.php' method='post' target='_blank'>");
	echo("<input type='hidden' name='hiddeninput' value='$serial_listing' />");
	echo("<input type='checkbox' name='purge_session_data' value='y' /> Purger les donn&#233;es apr&#232;s t&#233;l&#233;chargement du fichier");
	echo("</form></td></tr></table>");
}
?>
