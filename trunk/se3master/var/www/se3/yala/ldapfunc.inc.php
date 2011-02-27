<?php

define("DEFAULT_LDAP_VERSION", 3);

#
# This is an ldap functions wrapper that adds some more functions
#
class LDAPFunc {
	var $ldap_conn;
	var $bound = 0; # When zero means that we're not bound yet
	var $server = ""; # Will contain the server's address
	var $ldap_version; # Chosen ldap version

	function get_ldap_version() { return $this->ldap_version; }
	function set_ldap_version($str) { $this->ldap_version = $str; }

	function get_server() { return $this->server; }
	function set_server($str) { $this->server = $str; }


	#
	# Simple LDAP functions (we just wrap them)
	#

	# {{{ Constructor - connects to the ldap server
	function LDAPFunc($server, $port, $tls) {
		$this->ldap_conn = @ldap_connect($server, $port) or exitOnError(ERROR_LDAP_CANT_CONNECT);
		if (defined("LDAP_VERSION"))
			$this->set_ldap_version(LDAP_VERSION);
		else
			$this->set_ldap_version(DEFAULT_LDAP_VERSION);

		@ldap_set_option($this->ldap_conn, LDAP_OPT_PROTOCOL_VERSION, $this->get_ldap_version()) or exitOnError(0, "Cannot set ldap version ".$this->get_ldap_version());

		# Adding start_tls, which requires ldap protocol version 3
		if ($tls) {
			if (DEBUG) echo "Starting TLS...<BR>\n";
			if ($this->get_ldap_version == 3){
				@ldap_start_tls($this->ldap_conn) or exitOnError(ERROR_TLS_CANT_CONNECT, ldap_error($this->ldap_conn));
			} else {
				exitOnError(ERROR_TLS_BUT_V3);
			}
		}


		# We might want to know which server it is later.
		$this->set_server($server);
		
	} # }}}

	# {{{ search()
	function search($basedn, $filter, $scope) {
		switch ($scope) {
			case "sub": $sr = @ldap_search($this->ldap_conn, $basedn, $filter) or exitOnError(ERROR_LDAP_CANT_SEARCH); break;
			case "one": $sr = @ldap_list($this->ldap_conn, $basedn, $filter) or exitOnError(ERROR_LDAP_CANT_SEARCH); break;
			case "base": $sr = @ldap_read($this->ldap_conn, $basedn, $filter) or exitOnError(ERROR_LDAP_CANT_SEARCH); break;
			default: exitOnError(ERROR_BAD_OP);
		}

		if (!$sr) exitOnError(ERROR_LDAP_CANT_SEARCH);

		$info = ldap_get_entries($this->ldap_conn, $sr);

		return $info;
	} # }}}

	# {{{ bind() - Binds either anonymously or as a user
	function bind($binddn, $bindpw) {
#		ldap_start_tls($this->ldap_conn) or die("CANT".ldap_error($this->ldap_conn));
		if ($binddn && $bindpw) {
			# Not anonymously;
			$retval = @ldap_bind($this->ldap_conn, $binddn, $bindpw);
		}
		else {
			# Anonymously
			$retval = @ldap_bind($this->ldap_conn);
		}

		if ($retval) $bound = 1;
		return $retval;
	} # }}}

	# Advanced LDAP functions


	/* {{{ getMayMust() is a recursive function which returns all an
	array of all the May & Must attributes of an object and it's parent
	objects */
	function getMayMust($objectclass, $objectclasses_array, $name2oid_array) {

		$objectclass = strtolower($objectclass);
		if (array_key_exists($objectclass, $name2oid_array))
			$oid = $name2oid_array[$objectclass];
		else
			exitOnError(ERROR_SCHEMA_PROBLEM, "unknown objectclass ".$objectclass);
		
		$current_maymust = array();
		if (array_key_exists("may", $objectclasses_array[$oid]))
			$current_maymust["may"] = $objectclasses_array[$oid]["may"];
		else
			$current_maymust["may"] = array();
		
		if (array_key_exists("must", $objectclasses_array[$oid]))
			$current_maymust["must"] = $objectclasses_array[$oid]["must"];
		else
			$current_maymust["must"] = array();
		

		# If we inherit, recurse
		if (array_key_exists("sup", $objectclasses_array[$oid])) { 
			$parents_maymust = $this->getMayMust($objectclasses_array[$oid]["sup"], $objectclasses_array, $name2oid_array);

			# Now merge the current objectass attributes with the 
			# parents' attributes and return that array
			return array_merge_recursive($current_maymust, $parents_maymust);
		}
		else {
			# If we inherit nothing, return only current objectclass'
			# attributes.
			return $current_maymust;
		}
	} /* }}} */

	# {{{ getSubSchemaDN() function returns the dn of the subschema
	function getSubSchemaDN() {
		if (DEBUG) echo "Reading schema from LDAP<BR>";
		$sr = ldap_read($this->ldap_conn, "", "(objectClass=*)", array("subschemaSubentry"));
		if (!$sr) die("Search error! ".ldap_error($this->ldap_conn));
		$info = @ldap_get_entries($this->ldap_conn, $sr);
		if ($info && $info["count"] && array_key_exists("subschemasubentry", $info[0]) && $info[0]["subschemasubentry"]["count"])
			return $info[0]["subschemasubentry"][0];
		else
			return "cn=subschema"; # Should be working usually
	} # }}}
	
	/* {{{	getSchemaHash() returns a hash of the whole schema:
		objectclasses and their may and must attributes,
		and maybe more stuff later
	*/
	function getSchemaHash(&$name2oid, &$objectclasses) {
	
	/* objectclasses is an associative array, for every object. The key is the OID. examples:
	objectclasses["1.2.3.4"]
	objectclasses["1.2.3.5"]
	etc */

	/* Each objectclass is an associative array itself.. here's its structure:
	objectclasses["1.2.3.4"]["must"]	- an array of all the MUST variables
	objectclasses["1.2.3.4"]["may"]		- an array of all the MAY variables
	objectclasses["1.2.3.4"]["sup"]		- an array of all the superior objectclasses
	objectclasses["1.2.3.4"]["desc"]	- description of the object 
	*/

	/*
	name2oid["posixaccount"] = "1.2.3.4";
	*/
	 
	
		$subschema_dn = $this->getSubSchemaDN();
		if (!$subschema_dn) die("Cannot get the subschema DN!");
		$sr = ldap_read($this->ldap_conn, $subschema_dn, "(objectClass=*)", array("objectClasses"));
		if (!$sr) die("Search error! ".ldap_error($this->ldap_conn));
		$info = ldap_get_entries($this->ldap_conn, $sr);
		if (!$info || !$info["count"]) die("Cannot get schema information from root DSE!: ".ldap_error($this->ldap_conn));
	
		/* Process the objectclasses */
		for ($i = 0; $i < $info["count"]; $i++) {
			if (array_key_exists("objectclasses", $info[$i])) {
				for ($j = 0; $j < $info[$i]["objectclasses"]["count"]; $j++) {
					$line = $info[$i]["objectclasses"][$j];
					
					if (ereg('^$', $line)) continue;
	
					# alright.. Now we begin parsing the weird objectclass line:

					# FIRST, OID!
					# We allow strings here as well, due to some schemas + ldap servers which support this kludge (or is it legal?)
					if (ereg("^[\([:space:]]*([\.-_[:alnum:]]+)[\)[:space:]]+", $line, $matches)) {
						$oid = $matches[1];
#						print "OID: ".$matches[1]."<BR>";
					}
					else {
						die("<BR>WEIRD line: ".$line."end<BR><HR>"); 
					}

					# NAME
					if (ereg("NAME[[:space:]]+\(([^\)]+)", $line, $matches)) {
						$objectclasses[$oid]["names"] = split("[[:space:]]+", str_replace("'", "", trim($matches[1])));
#						print "NAME: ".str_replace("'","",$matches[1])."<BR>";
					} else { 
						if (ereg("NAME[[:space:]]+\'([^\']+)", $line, $matches)) {
							$objectclasses[$oid]["names"] = array($matches[1]);
#							print "NAME: ".$matches[1]."<BR>";
						} else {
							die("<BR>WEIRD line: $line<BR><HR>"); 
						}
					}

					# SUP
					if (ereg("SUP[[:space:]]+([[:alpha:]]+)", $line, $matches)) {
						$objectclasses[$oid]["sup"] = $matches[1];
#						print "SUP: ".$matches[1]."<BR>";
					}

					# DESC
					if (ereg("DESC[[:space:]]+\'([^\']+)", $line, $matches)) {
						$objectclasses[$oid]["desc"] = $matches[1];
#						print "DESC: ".$matches[1]."<BR>";
					}

					# MAY
					if (ereg("MAY[[:space:]]+\(([^\)]+)", $line, $matches)) {
						$objectclasses[$oid]["may"] = split('[[:space:]]*\$[[:space:]]*', trim($matches[1]));
#						print "MAY: ".$matches[1]."<BR>";
					} else {
						if (ereg("MAY[[:space:]]+([[:alpha:]]+)", $line, $matches)) {
							$objectclasses[$oid]["may"] = array($matches[1]);
#							print "MAY: ".$matches[1]."<BR>";
						}
					}

					# MUST
					if (ereg("MUST[[:space:]]+\(([^\)]+)", $line, $matches)) {
						$objectclasses[$oid]["must"] = split('[[:space:]]*\$[[:space:]]*', trim($matches[1]));
#						print "MUST: ".$matches[1]."<BR>";
					} else {
						if (ereg("MUST[[:space:]]+([[:alpha:]]+)", $line, $matches)) {
							$objectclasses[$oid]["must"] = array($matches[1]);
#							print "MUST: ".$matches[1]."<BR>";
						}
					}



					foreach ($objectclasses[$oid]["names"] as $name) {
						$name2oid[strtolower($name)] = $oid;
					}
				}
			}
		}
		
	} /* }}} */

}

?>
