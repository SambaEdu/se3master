<CENTER>
<TABLE CLASS="toolbar">
<?php
	$html = "<TR>\n";
	if ($yala_bind==1)
		$html .= "<TD ALIGN=\"CENTER\">[&nbsp;<A HREF=\"".MAINFILE."?do=choose_entrytype\" TARGET=\"right\">Nouvelle Entr&eacute;e</A>&nbsp;]</TD>\n";
	$html .= "<TD ALIGN=\"CENTER\">[&nbsp<A HREF=\"".MAINFILE."?do=search_form\" TARGET=\"right\">Recherche</A>&nbsp;]</TD>\n";
	if ($yala_bind==1)
		$html .= "</TR><TR>\n";
	$html .= "<TD ALIGN=\"CENTER\" COLSPAN=\"2\">\n";
	$html .= "[&nbsp;<A HREF='javascript:top.location.reload();'>Rafra&icirc;chir</A>&nbsp;]\n";
	$html .= "</TD></TR>\n";
	echo $html;
?>
</TABLE>
</CENTER>
<HR>
