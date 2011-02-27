<CENTER><H1>Modrdn</H1></CENTER>
<FORM NAME="form" METHOD="post" ACTION="<?php echo MAINFILE; ?>">
<INPUT TYPE="hidden" NAME="entry" VALUE="<?php echo $entry; ?>">
<TABLE BORDER="0" CELLSPACING="2" CELLPADDING="2">
<TR BGCOLOR="#FFFFFF"><TD><ACRONYM TITLE="The DN before the modification">Original DN</ACRONYM>:</TD><TD><?php echo $entry; ?></TD></TR>
<TR BGCOLOR="#FFFFFF"><TD><ACRONYM TITLE="If you want to rename, type another Relative DN">New RDN</ACRONYM>:</TD><TD><INPUT TYPE="text" SIZE="45" NAME="newrdn" VALUE="<?php echo $rdn; ?>"></TD></TR>
<TR BGCOLOR="#FFFFFF"><TD><ACRONYM TITLE="Delete the older name? (Probably yes)">Delete old RDN</ACRONYM>:</TD><TD><INPUT TYPE="text" SIZE="2" LENGTH="1" NAME="deleteoldrdn" VALUE="1"></TD></TR>
<TR BGCOLOR="#FFFFFF"><TD><ACRONYM TITLE="A new parent IF you want to move this entry">New Superior:</ACRONYM></TD><TD><INPUT TYPE="text" SIZE="45" NAME="newsuperior" VALUE="<?php echo $superior; ?>"></TD></TR>
</TABLE>
<CENTER><INPUT TYPE="submit" NAME="submit" VALUE="Modrdn"></CENTER>
</FORM>
<CENTER><FONT SIZE="-1">TIP: Put the mouse over an unknown term in order to get help</FONT></CENTER>
