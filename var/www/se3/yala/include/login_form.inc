<CENTER>
<BR><IMG SRC="images/yala-logo-small.jpg" ALT="YALA logo"><BR>
<CENTER>v<?php echo YALA_VERSION; ?></FONT></CENTER><BR>
<FORM METHOD="post" ACTION="<?php echo MAINFILE; ?>">
<TABLE STYLE="border: thin solid #DDE5FF;">
	<TR><TD><B><ACRONYM TITLE="The LDAP server to connect to. either IP address or name">Server</ACRONYM>:</B></TD><TD><INPUT TYPE="text" SIZE="45" NAME="ldap_server" VALUE="<?php echo $ldap_server; ?>"></TD></TR>
	<TR><TD><B><ACRONYM TITLE="The port which the LDAP daemon listens to. usually 389 or 636">Port</ACRONYM>:</B></TD><TD><INPUT TYPE="text" SIZE="45" NAME="ldap_port" VALUE="<?php echo $ldap_port; ?>"></TD></TR>
	<TR><TD><B><ACRONYM TITLE="The root of the tree we'll be looking under">Base DN</ACRONYM>:</B></TD><TD><INPUT TYPE="text" SIZE="45" NAME="ldap_basedn" VALUE="<?php echo $ldap_basedn; ?>"></TD></TR>
	<TR><TD><B><ACRONYM TITLE="Distinguished Name to bind (=login) as">Bind DN</ACRONYM>:</B></TD><TD><INPUT TYPE="text" SIZE="45" NAME="ldap_binddn" VALUE="<?php echo $ldap_binddn; ?>"></TD></TR>
	<TR><TD><B><ACRONYM TITLE="Password (credentials) of the above DN">Bind Password</ACRONYM>:</B></TD><TD><INPUT TYPE="password" SIZE="45" NAME="ldap_bindpw"></TD></TR>
	<TR><TD><B><ACRONYM TITLE="Use Transport Layer Security encryption for this connection">TLS</ACRONYM>:</B></TD><TD><INPUT TYPE="checkbox" NAME="ldap_tls"<?php if ($ldap_tls) echo " checked"; ?>>Use <ACRONYM TITLE="Transport Layer Security">TLS</ACRONYM> for this connection</TD></TR>
</TABLE>
<INPUT TYPE="submit" NAME="submit" VALUE="Login">&nbsp;<INPUT TYPE="submit" NAME="submit" VALUE="Anonymous Login">
</FORM>
</CENTER>
