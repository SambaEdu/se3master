#!/usr/bin/perl


## $Id$ ##


use Net::LDAP;
use DBI;

require '/etc/SeConfig.ph';

$LCS = 1 if shift @ARGV;
$lcs_db = DBI->connect('DBI:mysql:lcs_db', $mysqlServerUsername, $mysqlServerPw) if $LCS;

# Suppression des comptes utilisateurs
$lcs_ldap = Net::LDAP->new("$slapdIp");
$lcs_ldap->bind(); # Anonyme
		   # dn       => $adminDn,
		   # password => $adminPw,
		   # version  => '3'
		   # );
$res = $lcs_ldap->search(base   => "ou=Trash,$baseDn",
			 scope  => 'one',
			 filter => 'uid=*');
warn $res->error if $res->code;
foreach $entry ($res->entries) {
  $uid = $entry->get_value('uid');  
  next if $uid =~ /^\s/;
  if (-d "/home/$uid") {  
      system("rm -r /home/$uid");
      #print "le rep /home/$uid existe\n";
  }    
  # Recherche du nom de la base données.
  $db_name = $uid;
  $db_name =~ s/-//g;
  $db_name =~ s/_//g;
  $db_name =~ s/\.//g;
  $db_name .= "_db";
  if ($LCS) {
    system("mysqladmin -f -u $mysqlServerUsername -p$mysqlServerPw drop $db_name > /dev/null 2>&1");
    $requete = $lcs_db->prepare("delete from personne where login = '$uid'");
    $requete->execute();
  }
}
$lcs_ldap->unbind;
