#!/usr/bin/perl
#
# $Id$ #
#

use CGI qw(:standard :html3);
use Encode::compat;
use Encode qw(encode decode);
use Text::Unaccent;
use Net::LDAP;
use Net::Domain;
use CGI::Cookie;
use DBI;

#$domain = Net::Domain::hostdomain();
#$hostname = Net::Domain::hostfqdn();

require '/etc/SeConfig.ph';

# Pour stoker les UID transmis par le fichier f_uid.txt
%Admin_UID = ();

# Définition des formats DBF
# --------------------------
%format = (
	   'f_ele'  => 'A5A25A25xA8A1x163A5',
	   'f_men'  => 'x6A5x3A8x16A10',
	   'f_div'  => 'xA5A20x7A10',
	   'f_gro'  => 'xA8A20',
	   'f_eag'  => 'A5A8',
	   'f_tmt'  => 'x6A5x20A40',
	   'f_wind' => 'xA10x16A20A15x20A8x2A1',
		 'a_ind'  => 'xA10x16A20A15x20A8x2A1'
	  );
%verif =  (
	   'f_ele'  => '4802,598',
	   'f_men'  => '546,88',
	   'f_div'  => '322,45',
	   'f_gro'  => '290,38',
	   'f_eag'  => '98,14',
	   'f_tmt'  => '290,77',
	   'f_wind' => '1058,306',
		 'a_ind'  => '706,251'
	  );

# Définition des formats TXT
# --------------------------
%formatTxt = (
	   'f_ele'  => '6',
	   'f_men'  => '3',
	   'f_div'  => '3',
	   'f_wind' => '5'
	  );


# Fonction d'écriture de l'entête HTML
# ====================================
sub entete {
  $handle = shift(@_);
  print $handle header(-type=>'text/html') if $handle eq 'STDOUT';
  print $handle
   start_html(-title  => 'Importation de donnzes dans l\'annuaire LDAP',
	       -author => 'olivier.le_monnier@tice.ac-caen.fr',
	       -style  => {-src=>"/gepcgi/style/style.css"}),
    h1("<a style=\"color: #404044; text-decoration: none\" href=\"/$webDir/result.$pid.html\">Importation</a> de donn�es dans l\'annuaire LDAP"),
    hr();
}

# Fonction d'écriture du pied de page HTML
# ========================================
sub pdp {
  $handle = shift(@_);
  print $handle
    "<hr>\n",
    "<div style=\"font-size: xx-small; text-align: right\">\n",
    "<address><a href=\"mailto:LcsDevTeam\@tice.ac-caen.fr\">&lt; LcsDevTeam\@tice.ac-caen.fr &gt;</a></address>\n",
    "Mis à jour : le 5 juillet 2004",
    "</div>\n",
    end_html();
}

sub dbf2txt {
  my $fichier = shift;
  open ( FICHTMP ,"</tmp/ApacheCgi.temp");
  seek FICHTMP, 4, 0;
  # Séparation entête/corps
  read( FICHTMP, $header, 8);
  @param = unpack 'LSS', $header;
  ($nb_enreg, $h_size, $l_enreg) = @param;
  # Vérification des tailles de l'entête et d'un enregistrement
  # -----------------------------------------------------------
  if ($verif{$fichier} ne "$h_size".","."$l_enreg") {
    $res = "$h_size,$l_enreg,$verif{$fichier}";
  } else {
    $res = 0;
    # écriture du fichier nettoyé
    # ---------------------------
    open DATA, ">/tmp/$fichier.temp";
    seek FICHTMP, $h_size+1, 0;
    while ( $nb_enreg ) {
      read( FICHTMP, $record, $l_enreg);
      #print "<tt>$format{$fichier}</tt> : $record<br>\n";
      $data = join '|', (unpack $format{$fichier}, $record);
      print DATA encode("utf8", decode("cp850", $data)), "\n";
      $nb_enreg--;
    }
  }
  close DATA;
  close FICHTMP;
  unlink '/tmp/ApacheCgi.temp';
  # Renvoie 0 ou les valeurs comparées
  # ----------------------------------
  return $res;
}

sub txtVerif {
  my $fichier = shift;
  open ( FICHTMP ,"</tmp/ApacheCgi.temp");
  open DATA, ">/tmp/$fichier.temp";
  $res = 0;
  while (<FICHTMP>) {
    #leb Ajout
    chomp($ligne = $_); 
    $ligne =~ s/\r//g;
    $ligne =~ s/\s+$//;
    $_ = encode("utf8", decode("cp1252", $ligne))."\n";
    #leb Fin Ajout
    $format = split /\|/, $_;
    if ($format ne $formatTxt{$fichier}) {
      close DATA;
      unlink "/tmp/$fichier.temp";
      $res = 1;
      last;
    }
    print DATA;
  }
  # Renvoie 0 ou 1
  # --------------
  close DATA;
  close FICHTMP;
  unlink '/tmp/ApacheCgi.temp';
  return $res;
}

sub mkUid {

  my ( $prenom, $nom ) = @_;

  # Génération de l'UID suivant la politique
  # définie dans le fichier de conf commun

  if (($uidPolicy == 0) || ($uidPolicy == 1)) {
    $uid = $prenom . "." . $nom;
  } elsif (($uidPolicy == 2) || ($uidPolicy == 3)) {
    $prenom =~ /^(\w)/;
    $uid = $1 . $nom;
  } elsif ($uidPolicy == 5) {
    $uid = $nom . $prenom;
    $uid =~ s/[\s-_]//;
    if (length($uid) > 18) {
    	$uid =~ /^(.{18})/;
	$uid = $1;
    }		
  } elsif ($uidPolicy == 4) {
    $uid = $nom;
    if (length($nom) > 6) {
      $uid =~ /^(.{7})/;
      $uid = $1;
    }
    $prenom =~ /^(\w)/;
    $uid .= $1;
  }
  if ((($uidPolicy == 1) || ($uidPolicy == 2)) && (length($uid) > 19)) {
    $uid =~ /^(.{19})/;
    $uid = $1;
  } elsif (($uidPolicy == 3) && (length($uid) > 8)) {
    $uid =~ /^(.{8})/;
    $uid = $1;
  }
  return(lc($uid));
}

sub sambaAttrs {

  use Crypt::SmbHash;

  my ( $uidNumber, $gid, $password ) = @_;
  $rid = 2 * $uidNumber + 1000;
  $pgrid = 513;
  ( $lmPassword, $ntPassword ) = ntlmgen $password;
  return ( $rid, $pgrid, $lmPassword, $ntPassword );

}

sub getFirstFreeUid {
		my $FFuidNumber = 1001; # n° à partir duquel la recherche est lancée
		my $increment = 1024; # doit etre une puissance de 2
		if (defined(getpwuid($FFuidNumber))) {
				do {
						$FFuidNumber += $increment;
				} while (defined(getpwuid($FFuidNumber)));
				
				$increment = int($increment / 2); 
				$FFuidNumber -= $increment;
				do {
						$increment = int($increment / 2); 
						if (defined(getpwuid($FFuidNumber))) {
								$FFuidNumber += $increment;
						} else {
								$FFuidNumber -= $increment;
						}
				} while $increment > 1;
				# la boucle suivante est normalement exécutée au plus une fois
				while (defined(getpwuid($FFuidNumber))) {
						$FFuidNumber++;
				}
		}
		return $FFuidNumber;
}

sub getFirstFreeGid {
		my $FFgidNumber = 2000; # n° à partir duquel la recherche est lancée
		my $increment = 1024; # doit etre une puissance de 2
		if (defined(getgrgid($FFgidNumber))) {
				do {
						$FFgidNumber += $increment;
				} while (defined(getgrgid($FFgidNumber)));
				
				$increment = int($increment / 2); 
				$FFgidNumber -= $increment;
				do {
						$increment = int($increment / 2); 
						if (defined(getgrgid($FFgidNumber))) {
								$FFgidNumber += $increment;
						} else {
								$FFgidNumber -= $increment;
						}
				} while $increment > 1;
				# la boucle suivante est normalement exécutée au plus une fois
				while (defined(getgrgid($FFgidNumber))) {
						$FFgidNumber++;
				}
		}
		
		return $FFgidNumber;
}

sub incrementUidInit {
  while (@data = getpwent()) {
    $increment{$data[0]} = 1;
  }
  return %increment;
}

sub processGepUser {

  my ( $uniqueNumber, $nom, $prenom, $date, $sexe, $password ) = @_;
  if ($password eq 'undef') { $password = $date }
  ( $uid, $cn, $givenName, $sn, $crypt, $gecos ) = gep2posixAccount( $prenom, $nom, $password, $date, $sexe );
  # S'il existe un UID issu du fichier f_uid on le prend
  $uid = $Admin_UID{$uniqueNumber} || $uid;
  # Recherche EMPLOYEENUMBER correspondant dans l'annuaire
  if ($uniqueNumber =~ /^P(.*)$/) {
    $searchString = $1;
  } else {
    $searchString = $uniqueNumber;
  }
  $res = $lcs_ldap->search(base     => "$peopleDn",
			   scope    => 'one',
			   filter   => "(|(employeeNumber=$searchString)(employeeNumber=$uniqueNumber))");
  warn $res->error if $res->code;
  if (($res->entries)[0] and $uniqueNumber ne 'undef') {
    # S'il existe : actualisation des entrées CN et SN et employeeNumber
    $uid = (($res->entries)[0])->get_value('uid');
    updateUserCSn();
    $cn = encode('latin1', decode('utf8', $cn));
    return ("<tr><td>Entrée <strong>$cn :</strong></td><td>compte $uniqueNumber déjà présent dans l'annuaire : <tt><strong>$uid</strong></tt>.</td></tr>\n");
  } else {
    $id = 1;
    if(($uid eq 'prof')||($uid eq 'docs')||($uid eq 'progs')||($uid eq 'netlogon')||($uid eq 'classes')||($uid eq 'homes')||($uid eq 'admhomes')||($uid eq 'profiles')) {
        #$uid=substr($uid, 0, length($uid)-1)."1";
        $uid=substr($uid, 0, length($uid)-1).++$id;
    }
  DOUBLONS: while (1) {
      if($uid eq 'admse3') {
          if($id<=3) {$id=4;}
          $uid=substr($uid, 0, length($uid)-1).++$id;
      }
      # Recherche d'un uid correspondant dans l'annuaire
      $res = $lcs_ldap->search(base     => "$peopleDn",
															 scope    => 'one',
															 filter   => "uid=$uid");
      warn $res->error if $res->code;
      if (($res->entries)[0]) {
					# S'il existe : vérification de la présence de l'EMPLOYEENUMBER
					$employeeNumber = (($res->entries)[0])->get_value('employeeNumber');
					if (! $employeeNumber and $uniqueNumber ne 'undef') {
							# En l'absence : Comparaison des CN
							$cnLdap = unac_string('utf8', (($res->entries)[0])->get_value('cn'));
							$cnTemp = unac_string('utf8', $cn);
							if ($cnLdap eq $cnTemp) {
									$ldapUid = (($res->entries)[0])->get_value('uid');
									updateUserEntry();
									$cn = encode('latin1', decode('utf8', $cn));
									return ("<tr><td>Entrée <strong>$cn :</strong></td><td>Mise à jour du 'numéro unique', compte <tt><strong>$ldapUid</strong></tt>.</td></tr>\n");
							} else {
									# traitement des déchets...
									$cn = encode('latin1', decode('utf8', $cn));
									return ("<tr><td>Entrée <strong>$cn :</strong></td><td>Risque de conflits, entrée non traitée</td></tr>\n");
							}
					} else {
							# Sinon Gestion des doublons
							if ($uid =~ /^(.*)${id}$/) {
									$uid = $1 . ++$id;
							} else {
									chop($uid) if ((length($uid)>= 8) && ($uidPolicy == 4));
									$uid .= ++$id;
							}
							next DOUBLONS;
					}
      } else {
	# Cr�ation de l'entrée
	addUserEntry($uid, $password);
	$cn = encode('latin1', decode('utf8', $cn));
        my $ValRetour =  "<tr><td>Entrée <strong>$cn :</strong></td><td>Création du compte <tt><strong>$uid</strong></tt>";
        $ValRetour .= " fourni par f_uid" if $Admin_UID{$uniqueNumber} ;
        $ValRetour .= "</td></tr>\n";
        return ($ValRetour);
	last DOUBLONS;
      }
    }
  }
}

sub updateUserCSn {

  $res = $lcs_ldap->modify( "uid=$uid,$peopleDn",
			    replace => {
				       cn             => $cn,
				       sn             => $sn,
				       employeeNumber => $uniqueNumber
				      } );
  warn $res->error if $res->code;

}

sub updateUserEntry {

  $res = $lcs_ldap->modify( "uid=$uid,$peopleDn",
			    add => {
				    employeeNumber => $uniqueNumber
				   } );
  warn $res->error if $res->code;

}

sub addUserEntry {

  ($uid, $password) = @_;

  my $uidNumber = getFirstFreeUid($uidNumber);

  my ( $rid, $pgrid, $lmPassword, $ntPassword ) = sambaAttrs( $uidNumber, $gid, $password );
  if ($smbversion eq "samba3") {
  @entry = (
	    'uid',            $uid,
	    'cn',             $cn,
	    'givenname',      $givenName,
	    'sn',             $sn,
            'initials',       $initials,
	    'mail',           "$uid\@$domain",
	    'objectClass',    'top',
	    'objectClass',    'posixAccount',
	    'objectClass',    'shadowAccount',
	    'objectClass',    'person',
	    'objectClass',    'inetOrgPerson',
	    'objectClass',    'sambaSamAccount',
	    'loginShell',     $loginShell,
	    'uidNumber',      $uidNumber,
	    'gidNumber',      $gid,
	    'homeDirectory',  "/home/$uid",
	    'userPassword',   $crypt,
	    'gecos',          $gecos,
	    'sambaSID',      "$domainsid-$rid",
	    'sambaPrimaryGroupSID', "$domainsid-513",
	    'sambaLMPassword',     $lmPassword,
	    'sambaNTPassword',     $ntPassword,
	    'sambaPwdMustChange',  '2147483647',
            'sambaPwdLastSet',     '1',
	    'sambaAcctFlags',      '[U          ]',
            'shadowLastChange',	time

	   );
  } else {
  @entry = (
	    'uid',            $uid,
	    'cn',             $cn,
	    'givenname',      $givenName,
            'initials',        $initials,
	    'sn',             $sn,
	    'mail',           "$uid\@$domain",
	    'objectClass',    'top',
	    'objectClass',    'posixAccount',
	    'objectClass',    'shadowAccount',
	    'objectClass',    'person',
	    'objectClass',    'inetOrgPerson',
	    'objectClass',    'sambaAccount',
	    'loginShell',     $loginShell,
	    'uidNumber',      $uidNumber,
	    'gidNumber',      $gid,
	    'homeDirectory',  "/home/$uid",
	    'userPassword',   $crypt,
	    'gecos',          $gecos,
	    'rid',            $rid,
	    'primaryGroupId', $pgrid,
	    'lmPassword',     $lmPassword,
	    'ntPassword',     $ntPassword,
	    'pwdMustChange',  '2147483647',
	    'acctFlags',      '[U          ]'
	   );
  }
  push @entry, ('employeeNumber', $uniqueNumber) if $uniqueNumber;

  $res = $lcs_ldap->add( "uid=$uid,$peopleDn",
			 attrs => \@entry );
  warn $res->error if $res->code;

}

sub normalize {
  $toNormalize = shift;
  $howMuch = shift;
  if ($toNormalize =~ /\s/ and length($toNormalize) > $howMuch) {
    my @elements = split / /, $toNormalize;
    $normalized = '';
    foreach $element (@elements) {
      if (length($element) > $howMuch) {
	$element = lc($element);
	$element = ucfirst($element);
      }
      $normalized .= "$element ";
    }
    chop $normalized;
    return $normalized
  } elsif (length($toNormalize)> $howMuch) {
    $normalized = lc($toNormalize);
    $normalized = ucfirst($normalized);
  }
}

sub isAdmin {

  # Identification de l'utilisateur
  # ===============================
  # Récupération du cookie
    $isAdmin = "Y";
    %cookies = fetch CGI::Cookie;
    $session = $cookies{'SambaEdu3'}->value;
    # Connexion MySql
    $lcs_db = DBI->connect("DBI:mysql:$connexionDb", $mysqlServerUsername, $mysqlServerPw);
    $requete = $lcs_db->prepare("select id, login from sessions where (sess = '$session')");
    $requete->execute();
    ( $id, $login ) = $requete->fetchrow_array;
    $lcs_db->disconnect;
    # Validation
    $admindn = 'uid=' . $login .",". $peopleDn;
    @attrs = ('cn');
    $lcs_ldap = Net::LDAP->new("$ldap_server");
    $lcs_ldap->bind(dn       => "$adminDn",
                    password => "$adminPw");
    $res = $lcs_ldap->search(base     => "cn=se3_is_admin,$droitsDn",
                             scope    => 'subtree',
                             attrs    => \@attrs,
                             filter   => "(member=$admindn)");
    foreach $entry ($res->entries) {
      @cn  = $entry->get('cn');
    }
    if ($cn[0] ne 'se3_is_admin') {
    	$isAdmin = "N";
    }
    $lcs_ldap->unbind();
  return $isAdmin;
}

sub annuelle {
  # Préparation d'une importation annuelle
  # --------------------------------------
  print RES "<h2>Préparation à l'importation annuelle</h2>\n";
  # Connexion LDAP
  $lcs_ldap = Net::LDAP->new("$slapdIp");
  $lcs_ldap->bind(
		  dn       => $adminDn,
		  password => $adminPw,
		  version  => '3'
		 );
  # 1.  Suppression des groupes 'Cours', 'Equipe', 'Classe' et 'Matiere'
  $res = $lcs_ldap->search(base     => "$groupsDn",
			   scope    => 'one',
			   filter   => "(|(cn=Classe_*)(cn=Equipe_*)(cn=Cours_*)(cn=Matiere_*))");
  warn $res->error if $res->code;
  if (($res->entries)[0]) {
    foreach $entry ($res->entries) {
      $cn = $entry->get_value('cn');
      $res = $lcs_ldap->delete("cn=$cn,$groupsDn");
      print RES "Suppression du groupe <tt><strong>$cn</strong></tt>.<br>\n" if $debug > 1;
      warn $res->error if $res->code;
    }
  }
  # 2.  Modification du DN des groupes Eleves, Profs et Administratifs
  $res = $lcs_ldap->search(base     => "$groupsDn",
			   scope    => 'one',
			   filter   => 'cn=Eleves');
  warn $res->error if $res->code;
  $elevesGid = (($res->entries)[0])->get_value('gidNumber');
  $res = $lcs_ldap->moddn( $elevesDn,
			   newrdn => "${elevesRdn}Old" );
  warn $res->error if $res->code;
  $elevesRdn =~ /cn=(.+)$/;
  @elevesEntry = (
		  'cn',          "$1",
		  'objectClass', 'top',
		  'objectClass', 'posixGroup',
		  'gidNumber',   $elevesGid,
		 );
  $res = $lcs_ldap->add( "$elevesDn",
			 attrs => \@elevesEntry );
  warn $res->error if $res->code;
  $res = $lcs_ldap->search(base     => "$groupsDn",
			   scope    => 'one',
			   filter   => 'cn=Profs');
  warn $res->error if $res->code;
  $profsGid = (($res->entries)[0])->get_value('gidNumber');
  $res = $lcs_ldap->moddn( $profsDn,
			   newrdn => "${profsRdn}Old" );
  warn $res->error if $res->code;
  $profsRdn =~ /cn=(.+)$/;
  @profsEntry = (
		 'cn',          "$1",
		 'objectClass', 'top',
		 'objectClass', 'posixGroup',
		 'gidNumber',   $profsGid,
		);
  $res = $lcs_ldap->add( "$profsDn",
			 attrs => \@profsEntry );
  warn $res->error if $res->code;
  $res = $lcs_ldap->search(base     => "$groupsDn",
			   scope    => 'one',
			   filter   => 'cn=Administratifs');
  warn $res->error if $res->code;
  $adminsGid = (($res->entries)[0])->get_value('gidNumber');
  $res = $lcs_ldap->moddn( "cn=Administratifs,$groupsDn",
			   newrdn => 'cn=AdministratifsOld' ); #leb ajout de 'cn='
  warn $res->error if $res->code;
  @adminsEntry = (
		  'cn',          'Administratifs',
		  'objectClass', 'top',
		  'objectClass', 'posixGroup',
		  'gidNumber',   $adminsGid,
		 );
  #leb Ajout
  $res = $lcs_ldap->add( "cn=Administratifs,$groupsDn",
       attrs => \@adminsEntry );
  warn $res->error if $res->code;
  #leb Fin Ajout
  
  # 3.  Recopie des member de ElevesOld, ProfsOld et AdministratifsOld dont
  #     l'employeeNumber est vide vers les branches renouvellées
  $res = $lcs_ldap->search(base     => "$peopleDn",
			   scope    => 'one',
			   filter   => "(!(employeeNumber=*))");
  foreach $entry ($res->entries) {
    $uid = $entry->get_value('uid');
    $res2 = $lcs_ldap->search(base     => "${elevesRdn}Old,$groupsDn",
			      scope    => 'base',
			      filter   => "memberUid=$uid");
    if (($res2->entries)[0]) {
      $res2 = $lcs_ldap->modify( $elevesDn,
				 add => { 'memberUid' => $uid } );
      warn $res2->error if $res->code;
      next;
    } else {
      $res2 = $lcs_ldap->search(base     => "${profsRdn}Old,$groupsDn",
			        scope    => 'base',
 			        filter   => "memberUid=$uid");
      if (($res2->entries)[0]) {
        $res2 = $lcs_ldap->modify( $profsDn,
				   add => { 'memberUid' => $uid } );
        warn $res2->error if $res->code;
	next;
      } else {
        $res2 = $lcs_ldap->search(base     => "cn=AdministratifsOld,$groupsDn",
			          scope    => 'base',
			          filter   => "memberUid=$uid");
        if (($res2->entries)[0]) {
          $res2 = $lcs_ldap->modify( "cn=Administratifs,$groupsDn",
				     add => { 'memberUid' => $uid } );
          warn $res2->error if $res->code;
	}
      }
    }
  }
  # 4.  Suppression des branches OLD
  $res = $lcs_ldap->delete("${profsRdn}Old,$groupsDn");
  $res = $lcs_ldap->delete("${elevesRdn}Old,$groupsDn");
  $res = $lcs_ldap->delete("cn=AdministratifsOld,$groupsDn");
}
sub gep2posixAccount {
  
  my ( $prenom, $nom, $password, $date, $sexe ) = @_;

  @noms = ();
  $sn = '';

  #  Minusculisation  et nettoyage du nom et du prénom
  # $nom =~ tr/A-Z/a-z/;
  $nom = lc($nom);
  $nom =~ s/'//; #';
  if ($nom =~ /\s/) {
    @noms = (split / /,$nom);
    $nom1 = $noms[0];
    if (length($noms[0]) < 4) {
      $nom1 .= "_" . $noms[1];
      $separator = ' ';
    } else {
      $separator = '-';
    }
    foreach $nom_partiel (@noms) {
      $sn .= ucfirst($nom_partiel) . $separator;
    }
    chop $sn;
  } else {
    $nom1 = $nom;
    $sn = ucfirst($nom);
  }
  $nom =~ /^(\w)(.*)/;
  $firstletter_nom = $1;
  $firstletter_nom = uc($firstletter_nom);

  $prenom =~ /^(\S*)/;
  $prenom1 = $1;
  $prenom1 = lc($prenom1);
  $prenom1 =~ s/'//; #';
  $prenom1 =~ s/\.//; #';

  $uid = mkUid(unac_string('utf8', ($prenom1)), unac_string('utf8', ($nom1)));

  # Génération du mot de passe crypté
#  $salt  = chr (rand(75) + 48);
#  $salt .= chr (rand(75) + 48);
#  $crypt = crypt $password, $salt;
  $crypt = `/usr/sbin/slappasswd -h {MD5} -s '$password'`;

  # Génération de cn, givenName et sn
  $cn = ucfirst($prenom1);
  $cn = "$cn $sn";  
  $givenName = $prenom1;
  $initials = $prenom1 . $firstletter_nom;

  # Génération du gecos
  if ($sexe eq '1') { $sexe = 'M' }
  if ($sexe eq '2') { $sexe = 'F' }

  $unacn = unac_string('utf8', ($cn));
  $gecos = "$unacn,$date,$sexe,N";

  @data = ( $uid, $cn, $givenName, $sn, $crypt, $gecos );
  return @data;
}
