#!/usr/bin/perl

### $Id$ ###

use Net::LDAP;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 1);

($dnToDel, $dnWhereToDel) = @ARGV;

$attribute = typeOfGroup($dnWhereToDel);

unless ($attribute eq 'member') {
  $dnToDel =~ /^(\w*)=([\w\-_\.\d]*),/;
  $dnToDel = $2;
}

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

$res = $ldap->modify(
		      $dnWhereToDel,
		      delete => { $attribute => $dnToDel }
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
  
  foreach $entry ($res->entries) {
    @classes  = $entry->get_value('objectClass');
  }
  foreach $classe (@classes) {
    $type = $classe if ($classe =~ /group/i);
  }
  die ("Erreur de recherche sur $dnToSearchIn.\n") if (!defined($type));
  
  if ($type =~ /posixGroup/i) {
    $attribute = 'memberUid';
  } elsif ($type =~ /sambaGroupMapping/i) {
    $attribute = 'memberUid';
  } elsif ($type =~ /groupOfNames/i) {
    $attribute = 'member'
  } else {
    die ("Erreur de recherche sur $dnToSearchIn.\n");
  }
  
  return $attribute;
}
