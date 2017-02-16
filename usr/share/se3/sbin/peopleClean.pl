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
$res = $lcs_ldap->search(base   => "$peopleDn",
			 scope  => 'one',
			 filter => 'uid=*');
warn $res->error if $res->code;
foreach $entry ($res->entries) {
  $uid = $entry->get_value('uid');
  next if ($uid eq 'admin' or $uid eq 'adminse3' or $uid eq 'root' or $uid eq 'webmaster.etab' or $uid eq 'wetab' or $uid eq 'etabw' or $uid eq 'spip.manager' or $uid eq 'unattend');
  $res = $lcs_ldap->delete("uid=$uid,$peopleDn");
  print "Suppression de l'utilisateur $uid\n";
  warn $res->error if $res->code;
}
