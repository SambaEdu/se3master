#!/usr/bin/perl 


## $Id$ ##


## script d'attribution des groupes LDAP de l'absent au remplacant 
## pschwarz Mai 2005 
# Lancé par remplacant.php
# Usage : perl remplace.pl uid_de_l_absent uid_du_remplacant

use Net::LDAP;
require '/etc/SeConfig.ph';

my $absent = $ARGV[0];
my $remplacant = $ARGV[1];
my $urlabsent= "<a href=\"../annu/people.php?uid=$absent\"> $absent </a>";
my $urlremplacant= "<a href=\"../annu/people.php?uid=$remplacant\"> $remplacant </a>";

$stop=1;

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

$ABS = $ldap->search(
		     base     => "ou=People,$baseDn",
		     scope    => 'one',
		     filter   => "uid=$absent"
		    );

if (!($ABS->entries)[0]) {print "<BR>Le professeur $urlabsent n'existe pas.<BR>"; $stop=0;}

if ( $stop)
{$RPL = $ldap->search(
		     base     => "ou=People,$baseDn",
		     scope    => 'one',
		     filter   => "uid=$remplacant"
		    );
if (!($RPL->entries)[0]) {print "<BR>Le professeur $urlremplacant n'existe pas.<BR>";$stop=0;}
}

#foreach $parcoursABS ($ABS->entries) {$nomabsent = $parcoursABS->dn;}
#foreach $parcoursRPL ($RPL->entries) {$nomremplacant = $parcoursRPL->dn;}
if ( $stop)
{if ($absent=~m/$remplacant/) {print "<BR>Le professeur $urlremplacant se remplace lui-même; Vous appliquez les décisions du ministre. C'est bien, poursuivez.<BR>"; $stop=0;}}

if ( $stop)		    
{$grpesABS = $ldap->search(
		     base     => "ou=Groups,$baseDn",
		     scope    => 'one',
		     filter   => "memberuid=$absent"
		    );
$nbre=$grpesABS->count();
if ($nbre < 2) {print "<BR>Le professeur <B>$urlabsent</B> n'appartient à aucun groupe secondaire.<BR>";$stop=0;}}

if ( $stop)		    
{$grpesRPL = $ldap->search(
		     base     => "ou=Groups,$baseDn",
		     scope    => 'one',
		     filter   => "memberuid=$remplacant"
		    );
#On crée un hachage contenant tous les groupes de l'absent??ou du remplacant???
%RPL=();
foreach $people ($grpesRPL->entries) {
  $dnRPL  = $people->dn;
  $dnRPLAFF=$dnRPL;
  $dnRPLAFF=~s/,ou=Groups,$baseDn//;  
  $dnRPLAFF=~s/cn=//;  
  
$RPL{"$dnRPLAFF"}= $dnRPL;
	}
}



if ( $stop)		    
{
$cpt=0;
foreach $people ($grpesABS->entries) {
  $dnABS  = $people->dn;
  $dnABSAFF=$dnABS;
  $dnABSAFF=~s/,ou=Groups,$baseDn//;  
  $dnABSAFF=~s/cn=//;  
  
 # foreach my $grpesABS (values(%RPL)){   
#if (! ( ($dnABS=~m/$grpesABS/) )  )            
#{$CHECKED="";}
#else
#{$CHECKED="CHECKED";}
#}
if ( exists ( $RPL {$dnABSAFF} ) ) 
{print "<BR>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$dnABSAFF";  }
else
{$cpt++;
print "<BR><INPUT NAME=\"GRPE$cpt\" TYPE=\"CHECKBOX\" VALUE=\"$dnABS\" CHECKED>$dnABSAFF";  
}
 				}
} 
$ldap->unbind();

