#!/usr/bin/perl

## $Id$ ##

use Net::LDAP;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 1);

($dnToAdd, $dnWhereToAdd) = @ARGV;

$attribute = typeOfGroup($dnWhereToAdd);

unless ($attribute eq 'member') {
  $dnToAdd =~ /^\w*=([0-9a-zA-Z-_\.]*),/g;
  $dnToAdd = $1;
}

$ldap = Net::LDAP->new(
		       "$slapdIp",
		       port    => "$slapdPort",
		       debug   => "$slapdDebug",
		       timeout => "$slapdTimeout",
		       version => "$slapdVersion"
		      );
$ldap->bind(
	    "$adminDn",
	    password => "$adminPw"
	   );
$res = $ldap->modify(
		      $dnWhereToAdd,
		      add => { $attribute => $dnToAdd }
		     );

die("Erreur LDAP : " . $res->error . ".\n") if ($res->code ne 0);

exit 0;

sub typeOfGroup {
  $dnToSearchIn = shift @_;
  $ldap = Net::LDAP->new(
			 "$slapdIp",
			 port    => "$slapdPort",
			 debug   => "$slapdDebug",
			 timeout => "$slapdTimeout",
			 version => "$slapdVersion"
			);
  $ldap->bind(); # Anonymous BIND
  $res = $ldap->search(
		       base     => "$dnToSearchIn",
		       scope    => 'base',
		       attrs    => ['objectClass'],
		       filter   => 'objectClass=*'
		      );
  die("Erreur LDAP : " . $res->error . ".\n") if ($res->code ne 0);  
  foreach $entry ($res->entries) {
    @classes  = $entry->get_value('objectClass');
  }
  foreach $classe (@classes) {
    $type = $classe if ($classe =~ /posixGroup/i or $classe =~ /groupOfNames/i);
  }
  die ("Erreur de recherche sur $dnToSearchIn pour $type.\n") if (!defined($type));
  
  if ($type =~ /posixGroup/i) {
    $attribute = 'memberUid';
  } elsif ($type =~ /groupOfNames/i) {
    $attribute = 'member'
  } else {
    die ("Erreur de recherche sur $dnToSearchIn.");
  }
  
  return $attribute;
}
