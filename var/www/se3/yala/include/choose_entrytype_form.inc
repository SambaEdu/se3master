<?php if (ENABLE_JAVASCRIPT) { ?>
<SCRIPT LANGUAGE="javascript">

function check_form(form) {

	checked = false;

	for (i = 0; i < form.entry_type.length; i++) {
		if (form.entry_type[i].checked == true) checked = true;
	}

	if (!checked) {
		alert("You didn't choose an entry type!");
		return false;
	}

	return true;
}
<?php }; ?>

</SCRIPT>
<FORM NAME="choose_entrytype_form" METHOD="post" ACTION="<?php echo MAINFILE; ?>"<?php if (ENABLE_JAVASCRIPT) echo " onSubmit=\"javascript:return check_form(this);\""; ?>>
<INPUT TYPE="hidden" NAME="dn" VALUE="<?php echo $parent; ?>">
<?php
	reset($entry_types);

	$count = 0;

	while ( list($entry_type, $val) = each($entry_types) ) {
		$count++;
		if (file_exists(IMAGES_PATH."/".$entry_type.".png"))
			$image = "<IMG SRC=\"".IMAGES_URLPATH."/".$entry_type.".png\" ALT=\"".$entry_type."\">";
		else
			$image = "<IMG SRC=\"".IMAGES_URLPATH."/default.png\" ALT=\"".$entry_type."\">";
		echo "<INPUT TYPE=\"radio\" NAME=\"entry_type\" VALUE=\"".$entry_type."\">&nbsp;".$image."&nbsp;".$entry_type."<BR>\n";
	}

	echo "<CENTER><INPUT TYPE=\"submit\" NAME=\"submit\" VALUE=\"Create\"></CENTER><BR>\n";
	# CUSTOM entry type
	echo "<HR><INPUT TYPE=\"radio\" NAME=\"entry_type\" VALUE=\"custom\">&nbsp;<IMG SRC=\"".IMAGES_URLPATH."/default.png\" ALT=\"Custom\">&nbsp;custom<BR>\n";
	echo "<CENTER><SELECT NAME=\"custom_objectclasses[]\" SIZE=\"8\"";
	if (ENABLE_JAVASCRIPT) echo "onChange=\"javascript:choose_entrytype_form.entry_type[".$count."].checked=true;\"";
	echo " MULTIPLE>\n";

	$names = array_keys($name2oid);
	asort($names);
	foreach ($names as $name) {
		echo "<OPTION VALUE=\"".$name."\">".$name."<BR>";
	}
	echo "</SELECT></CENTER>";
?>
<BR><CENTER><INPUT TYPE="submit" NAME="submit" VALUE="Create"></CENTER>
</FORM>
