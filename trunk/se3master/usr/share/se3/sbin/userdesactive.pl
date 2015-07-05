#!/usr/bin/perl


## $Id$ ##


use Net::LDAP;
use Encode::compat;
use Encode qw(encode decode);
#use Text::Unaccent;

require '/etc/SeConfig.ph';

die("Erreur d'argument.\n") if ($#ARGV != 1);
($uid, $act) = @ARGV;
$dn = "uid=$uid,$peopleDn";

#print $dn;
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
#print $act;
if($act) { $valeur='[U           ]'; } else { $valeur='[UD          ]';}
#print $valeur;
if ($smbversion eq "samba3") {
    $res = $ldap->modify(
                         $dn,
                         replace => {
                                     sambaAcctFlags => $valeur,
                                    }
                        );
} else {
    $res = $ldap->modify(
                         $dn,
                         replace => {
                                     acctFlags => $valeur,
                                    }
                        );
}
  warn $res->error if $res->code != LDAP_SUCCESS;
  
#print $res;
