<?php if (ENABLE_JAVASCRIPT) { ?>
<SCRIPT LANGUAGE="javascript">

function make_filter(str) {
	document.search_form.filter.value = '(&(objectclass=*)(cn='+str+'))';
}
	

</SCRIPT>
<?php }; ?>
<CENTER><H1>Recherche</H1></CENTER>
<FORM NAME="search_form" METHOD="post" ACTION="<?php echo MAINFILE; ?>">
<TABLE>
<TR BGCOLOR="#FFFFFF"><TD>Filter:</TD><TD><INPUT TYPE="text" SIZE="45" NAME="filter" VALUE="(&(objectclass=*)(cn=RemplacerMoi))"></TD></TR>
<TR><TD>Base DN:</TD><TD><INPUT TYPE="text" SIZE="45" NAME="basedn" VALUE="<?php echo $ldap_basedn; ?>"></TD></TR>
<TR><TD>Scope:</TD><TD>
<SELECT NAME="scope">
	<OPTION VALUE="sub">Subtree</OPTION>
	<OPTION VALUE="one">One Level</OPTION>
	<OPTION VALUE="base">Base</OPTION>
</SELECT>
</TABLE>
<CENTER><INPUT TYPE="submit" NAME="submit" VALUE="Search"></CENTER>
</FORM>
