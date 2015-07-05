#!/usr/bin/perl

## $Id$ ##

use Net::LDAP;

require '/etc/SeConfig.ph';

$ldap = Net::LDAP->new(
 		       "$slapdIp",
 		       port    => "$slapdPort",
 		       debug   => "$slapdDebug",
 		       timeout => "$slapdTimeout",
 		       version => "$slapdVersion"
 		      );
$ldap->bind(
 	    $adminDn,
 	    password => $adminPw
 	   );

$res = $ldap->search(
		     base   => "ou=People,$baseDn",
		     scope  => 'one',
		     filter => 'uid=*'
		    );

warn $res->error if $res->code != LDAP_SUCCESS;

foreach $entry ($res->entries) {
  
  $dn = $entry->dn;
  $cn = $entry->get_value('cn');
  $sn = $entry->get_value('sn');
  $newCn = "$cn $sn";
  
  $res = $ldap->modify(
		       $dn,
		       replace => {
				   cn => "$newCn",
				  }
		      );
  
  warn $res->error if $res->code != LDAP_SUCCESS;
}
