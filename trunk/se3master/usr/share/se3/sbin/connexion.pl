#!/usr/bin/perl

use Net::LDAP;
use DBI;
use POSIX;

require '/etc/SeConfig.ph';

die("Erreur d'argument\n") if ($#ARGV != 2);
($user, $machine, $ipAddress) = @ARGV;

# Recherche LDAP de la machine dans la branche ou=Computers
# ---------------------------------------------------------
$ldap = Net::LDAP->new(
   "$slapdIp",
   port => "$slapdPort",
   debug => "$slapdDebug",
   timeout => "$slapdTimeout",
   version => "$slapdVersion"
);
$ldap->bind(); # Anonymous BIND
$res = $ldap->search(
   base => "cn=$machine,$computersDn",
   scope => 'base',
   attrs => 'cn',
   filter => "cn=$machine"
);

#print "res->code = ". $res->code .", res->entries0 = ". ($res->entries)[0] ."\n";
$ip = 0;
if (($res->entries)[0]) {
   $cn = ($res->entries)[0]->get_value('cn');
   $ip = ($res->entries)[0]->get_value('ipHostNumber');
   $mac = ($res->entries)[0]->get_value('macAddress');
   #print "cn=$cn, ip=$ip, mac=$mac\n"
}

$ldap->unbind();


if ($ip) {
   # L'entr�e $machine existe d�j� dans l'annuaire
   if ($ip eq $ipAddress) {
      # L'adresse ip du poste est la m�me qu'avant : inutile de la r��crire.
      # De plus, il serait tr�s �tonnant que l'adresse MAC ait chang�.
   } else {
      # L'adresse ip de $machine a chang�.
      # Pour ceux qui veulent mettre � jour l'adresse mac dans l'annuaire, d�commenter les 2 lignes suivantes.
      #system("/bin/ping -c1 $ipAddress > /dev/null 2>&1");
      #$arp = (split /\s+/,`/usr/sbin/arp -n $ipAddress | grep $ipAddress`)[2];
      # L'adresse MAC n'est correcte que pour les postes dans le m�me sous-r�seau que le serveur.

      $ldap = Net::LDAP->new(
         "$slapdIp",
         port => "$slapdPort",
         debug => "$slapdDebug",
         timeout => "$slapdTimeout",
         version => "$slapdVersion"
      );
      $ldap->bind(
         $adminDn,
         password => $adminPw
      );
      # Modif
      # -----
      $res = $ldap->modify("cn=$machine,$computersDn", replace => {
         ipHostNumber => $ipAddress
         # Pour ceux qui veulent mettre � jour l'adresse mac dans l'annuaire, d�commenter la ligne suivante.
         # , macAddress => $arp
         }
      );
      die("Erreur lors de l'ajo�t de l'entr�e dans l'annuaire.\n") if ($res->code() != 0);

      # D�connexion
      # -----------
      $ldap->unbind;

   }
} else {
   # l'entr�e $machine n'existe pas dans l'annuaire : il faut l'ajouter
   system("/bin/ping -c1 $ipAddress > /dev/null 2>&1");
   $arp = (split /\s+/,`/usr/sbin/arp -n $ipAddress | grep $ipAddress`)[2];
   # L'adresse MAC n'est correcte que pour les postes dans le m�me sous-r�seau que le serveur.
   # Pour les autres, on obtient $arp='--'.

   #print "arp=$arp\n";
   $ldap = Net::LDAP->new(
      "$slapdIp",
      port => "$slapdPort",
      debug => "$slapdDebug",
      timeout => "$slapdTimeout",
      version => "$slapdVersion"
   );
   $ldap->bind(
      $adminDn,
      password => $adminPw
   );
   # Ajo�t
   # -----
   $res = $ldap->add(
   "cn=$machine,$computersDn",
   attrs => [
      cn => $machine,
      objectClass => 'top',
      objectClass => 'ipHost',
      objectClass => 'ieee802Device',
	  objectClass => 'organizationalRole',	
      ipHostNumber => $ipAddress,
      macAddress => $arp
   ]);
   die("Erreur lors de l'ajo�t de l'entr�e dans l'annuaire.\n") if ($res->code() != 0);

   # D�connexion
   # -----------
   $ldap->unbind;

}

# Renseignement de la base MySql
# ------------------------------

$connexion_db = DBI->connect("DBI:mysql:$connexionDb@$mysqlServerIp", $mysqlServerUsername, $mysqlServerPw);
   $requete = $connexion_db->prepare(
   "insert into connexions
   set username='$user',
   ip_address='$ipAddress',
   netbios_name = '$machine',
   logintime=now();"
);
$requete->execute();
$requete->finish;
$connexion_db->disconnect;

exit 0;

=head1 NOM

connexion.pl

=head1 SYNTAXE

connexion.pl utilisateur nom_netbios_machine adresse_ip

=head1 DESCRIPTIF

Cr�e l'entr�e correspondant � la machine dans la branche Computers si elle n existe pas.
Renseigne la base MySql avec les informations %u %m %I et date.

Renvoie 0 (succ�s) ou un message (gettext :) d erreur.

=cut