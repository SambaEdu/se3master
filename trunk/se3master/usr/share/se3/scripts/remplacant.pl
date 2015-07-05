#!/usr/bin/perl 


## $Id$ ##


## script d'attribution des groupes LDAP de l'absent au remplacant 
## pschwarz Mai 2005 
# Sur une idée de Pierre Marin et le coup de pied/motivation qui va avec ;-)
# Lancé par rempl.php
# Usage : perl remplacant.pl ....................

use Net::LDAP;
require '/etc/SeConfig.ph';

my $remplacant = $ARGV[0];
my $LDAP = $ARGV[1];
my $stop=1;
 
$ldap = Net::LDAP->new(
		       "$slapdIp",
		       port    => "$slapdPort",
		       debug   => "$slapdDebug",
		       timeout => "$slapdTimeout",
		       version => "$slapdVersion"
		      );
$ldap->bind(); # Anonymous BIND

$ldap->bind(
	    $adminDn,
	    password => $adminPw
	   );

$RPL = $ldap->search(
		     base     => "ou=People,$baseDn",
		     scope    => 'one',
		     filter   => "uid=$remplacant"
		    );
if (!($RPL->entries)[0]) {print "<BR>Le professeur $remplacant n'existe pas.<BR>";$stop=0;}


if ( $stop)		    
{
    $AJOUT = $ldap->modify("$LDAP",
			add => {'memberUid' => "$remplacant"});
  $LDAP=~s/,ou=Groups,$baseDn//;  
  $LDAP=~s/cn=//;  
  
print "Ajout dans le groupe <B>$LDAP </B><BR>";
			
			
 }

  
 
$ldap->unbind();

