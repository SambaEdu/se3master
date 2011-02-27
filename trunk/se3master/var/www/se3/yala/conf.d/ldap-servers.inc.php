<?php

#
# LDAP default stuff
#

define("LDAP_SERVER",	"$ldap_server");
define("LDAP_PORT",	"$ldap_port");
define("LDAP_BASEDN",	"$ldap_base_dn");
define("LDAP_BINDDN",	"$adminRdn,$ldap_base_dn");

# No good reason to use LDAPv2, unless an old PHP or LDAP Server
define("LDAP_VERSION",	"3");
# Use TLS to encrypt the ldap connection. Must be supported by server.
define("LDAP_TLS", FALSE);
?>
