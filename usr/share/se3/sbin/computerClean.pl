#!/usr/bin/perl

## $Id$ ##

use Net::LDAP;

require '/etc/SeConfig.ph';

# Suppression des comptes utilisateurs
$lcs_ldap = Net::LDAP->new("$slapdIp");
$lcs_ldap->bind(
		dn       => $adminDn,
		password => $adminPw,
		version  => '3'
		);
$res = $lcs_ldap->search(base   => "$computersDn",
			 scope  => 'one',
			 filter => 'objectClass=ieee802Device');
warn $res->error if $res->code;
foreach $entry ($res->entries) {
  $cn = $entry->get_value('cn');
  $l = $entry->get_value('l');
  next if ($l eq 'Maitre' or $l eq 'esclave');
  $res = $lcs_ldap->delete("cn=$cn,$computersDn");
  print "Suppression de la machine $cn\n";
  warn $res->error if $res->code;
}
