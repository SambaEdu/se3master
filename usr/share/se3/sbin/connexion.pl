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
   # L'entrée $machine existe déjà dans l'annuaire
   if ($ip eq $ipAddress) {
      # L'adresse ip du poste est la même qu'avant : inutile de la réécrire.
      # De plus, il serait très étonnant que l'adresse MAC ait changé.
   } else {
      # L'adresse ip de $machine a changé.
      # Pour ceux qui veulent mettre à jour l'adresse mac dans l'annuaire, décommenter les 2 lignes suivantes.
      #system("/bin/ping -c1 $ipAddress > /dev/null 2>&1");
      #$arp = (split /\s+/,`/usr/sbin/arp -n $ipAddress | grep $ipAddress`)[2];
      # L'adresse MAC n'est correcte que pour les postes dans le même sous-réseau que le serveur.

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
         # Pour ceux qui veulent mettre à jour l'adresse mac dans l'annuaire, décommenter la ligne suivante.
         # , macAddress => $arp
         }
      );
      die("Erreur lors de l'ajoût de l'entrée dans l'annuaire.\n") if ($res->code() != 0);

      # Déconnexion
      # -----------
      $ldap->unbind;

   }
} else {
   # l'entrée $machine n'existe pas dans l'annuaire : il faut l'ajouter
   system("/bin/ping -c1 $ipAddress > /dev/null 2>&1");
   $arp = (split /\s+/,`/usr/sbin/arp -n $ipAddress | grep $ipAddress`)[2];
   # L'adresse MAC n'est correcte que pour les postes dans le même sous-réseau que le serveur.
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
   # Ajoût
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
   die("Erreur lors de l'ajoût de l'entrée dans l'annuaire.\n") if ($res->code() != 0);

   # Déconnexion
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

Crée l'entrée correspondant à la machine dans la branche Computers si elle n existe pas.
Renseigne la base MySql avec les informations %u %m %I et date.

Renvoie 0 (succès) ou un message (gettext :) d erreur.

=cut