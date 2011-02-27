#!/usr/bin/perl -w

use Se;

# Algorythme global
# =================
if (!defined ($pid = fork)) {
  die "Impossible de « forker » : $!\n";
} elsif (! $pid) {
  &traitement_fond;
  exit(0);
} else {
  &traitement;
  exit(0);
}

sub traitement {

  open ENCOURS, '>/tmp/EnCours.temp';
  print ENCOURS '1';
  close ENCOURS;

  # Initialisation des variables
  # ----------------------------
  # Uid de départ
  $uidNumber = getFirstFreeUid(1000);
  # Gid de départ
  $gidNumber = getFirstFreeGid(2000);

  &entete(STDOUT);

  if (isAdmin() ne 'Y') {
    print
      "<p><strong>Erreur :</strong> vous n'avez pas les droits nécessaires",
      " pour effectuer l'importation !</p>";
    &pdp(STDOUT);
    exit(0);
  }

  # Récupération, Capitalisation et modification du préfixe
  $prefix = param('prefix') or $prefix = '';
  $prefix =~ tr/a-z/A-Z/;
  $prefix .= '_' unless ($prefix eq '');
  # Récupération de la valeur du flag ANNUELLE
  $annuelle = param('annuelle');

  open INT, '>/tmp/gepInterface.temp' or warn 'Impossible d\'écrire dans /tmp !';
  print INT "$prefix:$annuelle";
  close INT;

  # Lecture du fichier optionnel f_uid
  $fichier = "f_uid";
  $nom = param("$fichier");
  if ($nom eq '') {
    $FileUID = 0;
  } else {
    $FileUID = 1;
    $fileHandle = upload("$fichier");
    open ( FICHTMP ,">/tmp/f_uid.temp");
    while (<$fileHandle>) {
      chomp;
      s/#.*//;                # suppression des commentaires
      s/\s+//;                # suppression des blancs
      next unless length;
      if ( m/^(P?\d+)\|(.+)$/ ) {
        print FICHTMP $1 . '|' . $2 . "\n";
        $Admin_UID{ $1 } = $2;
      }
    }
    close FICHTMP;
  }

  # Écriture des fichiers dans /tmp
  # ===============================
  foreach $fichier (keys(%format)) {
    # Vérification du passage d'un fichier
    # ------------------------------------
    $nom = param("$fichier");
    if ($nom eq '') {
      print STDOUT "Pas de fichier fourni pour ",
	"<span class=\"filename\">$fichier</span> !<br>\n" if ($debug > 1);
      $atLeastOneNotOk = 1;
    } else {
      $fileHandle = upload("$fichier");
      # Suppression des retours chariot
      @gep = <$fileHandle>;
      $gep = join "", @gep;
      # Écriture du résultat dans un fichier texte temporaire
      open ( FICHTMP ,">/tmp/ApacheCgi.temp");
      print FICHTMP $gep;
      close FICHTMP;
      # Appel de la fonction d'ecriture du fichier vérifié et nettoyé (utf8)
      # --------------------------------------------------------------------
      $res = dbf2txt($fichier);
      $atLeastOneNotOk = 1 if $res;
      if ($debug > 1 && $res) {
	$res =~ /^(\d+),(\d+),(\d+),(\d+)$/;
	print
	  "Fichier <span class=\"filename\">$fichier</span> erroné :\n",
	  "<ul>\n",
	  "<li>Taille de l'entête : $1</li>\n",
	  "<li>Longueur d'un enregistrement : $2</li>\n",
	  "<li>Valeurs attendues : $3, $4</li>\n",
	  "</ul>\n";
      }
      unless ($res) {
 	$ok{$fichier} = 1;
 	$atLeastOneOk = 1;
      }
    }
  }

  # Rapport concernant la validité des fichiers
  # ===========================================
  unless ($atLeastOneOk) {
    print "<strong>Aucun fichier valide n'a été fourni !</strong>\n";
    pdp(STDOUT);
    exit 0;
  }

  if ($debug > 1 && $atLeastOneOk) {
    print
      "<h2>Fichiers fournis et valides</h2>\n",
      "<ul style=\"color: green\">\n";
    foreach $fichier (keys(%format)) {
      print "<li><span class=\"filename\" style=\"color: #404044\">$fichier</span></li>\n" if $ok{$fichier};
    }
    print "<li><span class=\"filename\" style=\"color: #404044\">f_uid</span> (" . keys( %Admin_UID ) . " uid lus)</li>\n" if $FileUID;
    print "</ul>\n";
  }
  if ($debug > 1 && $atLeastOneNotOk) {
    print
      "<h2>Fichiers non fournis ou invalides</h2>\n",
      "<ul style=\"color: red\">\n";
    foreach $fichier (keys(%format)) {
      print
	"<li><span class=\"filename\" style=\"color: #404044\">",
	"$fichier</span></li>\n" unless $ok{$fichier};
    }
    print
      "<li><span class=\"filename\" style=\"color: #404044\">",
      "f_uid</span> (" . keys( %Admin_UID ) . " uid lus)</li>\n" unless $FileUID;
    print "</ul>\n";
  }

  print "<hr>\n" if ($debug > 1);

  unless ($ok{'f_div'} or $ok{'f_ele'} or $ok{'f_men'} or $ok{'f_wind'} or $ok{'a_ind'}) {
    print
      "<strong>Aucun des fichiers fournis ne permet de créer ",
      "une entrée <span class=\"abbrev\">ldap</span> !</strong>\n";
    pdp(STDOUT);
    exit 0;
  }

  # Suppression des pages html résultats antérieures
  # ------------------------------------------------
  if (-e "$documentRoot/$webDir/result*") {
    unlink <$documentRoot/$webDir/result*>
      or warn "Le serveur Web n'a pas les droits suffisants",
        " sur le répertoire '$documentRoot/$webDir/result*'.";
    }

  # Écriture du fichier html provisoire de résultat final
  # -----------------------------------------------------
  open (RES, ">$documentRoot/$webDir/result.$pid.html")
    or die "Le serveur Web n'a pas les droits suffisants sur le répertoire '$documentRoot/$webDir/result*'.";
  &entete(RES);
  print RES
    p('<span style="text-align: center; font-weight: bold">Traitement en cours...</span>');
  &pdp(RES);
  close RES;

  print "<h2>Création des entrées <span class=\"abbrev\">ldap</span> suivantes</h2>\n" if $debug;
  if ($ok{'f_ele'} or $ok{'f_wind'} or $ok{'a_ind'}) {
    print
      "<strong>Comptes utilisateur :</strong>\n",
      "<ul style=\"color: green\">\n" if $debug;
    if ($ok{'f_ele'}) {
      print "<li><span class=\"filename\" style=\"color: #404044\">Élèves</span></li>\n" if $debug;
      $createEleves = 1;
    }
    if ($ok{'f_wind'}) {
      print "<li><span class=\"filename\" style=\"color: #404044\">Profs</span></li>\n" if $debug;
      $createProfs = 1;
    }
    if ($ok{'a_ind'}) {
      print "<li><span class=\"filename\" style=\"color: #404044\">Administratifs</span></li>\n" if $debug;
    }
    print "</ul>\n";
  }
  if ($ok{'f_div'} or $ok{'f_ele'} or $ok{'f_men'}) {
    print
      "<strong>Groupes :</strong>\n",
      "<ul style=\"color: green\">\n" if $debug;
    if ($ok{'f_div'} or $ok{'f_ele'}) {
      print "<li><span class=\"filename\" style=\"color: #404044\">Classes</span></li>\n" if $debug;
      print "<li><span class=\"filename\" style=\"color: #404044\">Équipes</span></li>\n" if $debug;
      $createClasses = 1; $createEquipes = 1;
    }
    if ($ok{'f_men'}) {
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Cours</span></li>\n",
	"<li><span class=\"filename\" style=\"color: #404044\">Matières</span></li>\n" if $debug;
      $createCours = 1; $createMatieres = 1;
    }
    print "</ul>\n";
  }

  if ($atLeastOneNotOk) {
    print "<h2>Problèmes liés à l'absence ou à l'invalidité de certains fichiers</h2>\n" if $debug;
    if (! $ok{'f_ele'} or ! $ok{'f_wind'} or ! $ok{'a_ind'}) {
      print
	"<strong>Pas de création des comptes utilisateur :</strong>\n",
	"<ul style=\"color: red\">\n" if $debug;
      print "<li><span class=\"filename\" style=\"color: #404044\">Élèves</span></li>\n"
	if (! $ok{'f_ele'} and  $debug);
      print "<li><span class=\"filename\" style=\"color: #404044\">Profs</span></li>\n"
	if (! $ok{'f_wind'} and $debug);
      print "<li><span class=\"filename\" style=\"color: #404044\">Administratifs</span></li>\n"
	if (! $ok{'a_ind'} and $debug);
      print "</ul>\n";
    }
    if (! $ok{'f_div'} or ! $ok{'f_ele'} or ! $ok{'f_men'}) {
      print
	"<strong>Pas de création des groupes :</strong>\n",
	"<ul style=\"color: red\">\n" if $debug;
      print "<li><span class=\"filename\" style=\"color: #404044\">Classes</span></li>\n",
	"<li><span class=\"filename\" style=\"color: #404044\">Équipes</span></li>\n"
	  if (! $ok{'f_div'} and ! $ok{'f_ele'} and $debug);
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Cours</span></li>\n",
	"<li><span class=\"filename\" style=\"color: #404044\">Matières</span></li>\n"
	  if (! $ok{'f_men'} and $debug);
      print "</ul>\n";
    }
    if ((! $ok{'f_div'} and ($createClasses or $createEquipes))
	or (! $ok{'f_tmt'} and $createMatieres)
	or (! $ok{'f_gro'} and $createCours))
      {
	print
	  "<strong>Pas de description disponible pour les groupes ",
	  "(utilisation du mnémonique) :</strong>\n",
	  "<ul style=\"color: red\">\n" if $debug;
	if (! $ok{'f_div'}) {
	  print "<li><span class=\"filename\" style=\"color: #404044\">Classes</span></li>\n"
	    if ($createClasses and $debug);
	  print "<li><span class=\"filename\" style=\"color: #404044\">Équipes</span></li>\n"
	    if ($createEquipes and $debug);
	}
	print
	  "<li><span class=\"filename\" style=\"color: #404044\">Matières</span></li>\n"
	    if (! $ok{'f_tmt'} and $createMatieres and $debug);
	print
	  "<li><span class=\"filename\" style=\"color: #404044\">Cours (en groupe)</span></li>\n"
	    if (! $ok{'f_gro'} and $createCours and $debug);
	print "</ul>\n";
      }
    if (($createCours and (! $ok{'f_eag'} or ! $ok{'f_ele'})) or ($createClasses and ! $ok{'f_ele'})) {
      print
	"<strong>Pas de membres pour les groupes :</strong>\n",
	"<ul style=\"color: red\">\n" if $debug;
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Cours ",
	"(en classe complète)</span></li>\n"
	  if ($createCours and ! $ok{'f_eag'} and $ok{'f_ele'} and $debug);
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Cours ",
	"(en groupe)</span></li>\n"
	  if ($createCours and $ok{'f_eag'} and ! $ok{'f_ele'} and $debug);
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Cours</span></li>\n"
	  if ($createCours and ! $ok{'f_eag'} and ! $ok{'f_ele'} and $debug);
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Classes</span></li>\n"
	  if ($createClasses and ! $ok{'f_ele'} and ! $ok{'f_eag'} and $debug);
      print
	"<li><span class=\"filename\" style=\"color: #404044\">Équipes</span></li>\n"
	  if ($createEquipes and ! $ok{'f_wind'} and $debug);
      print "</ul>\n";
    }
  }

  print
    "<div style=\"font-size: large; text-align: left; padding: 1em;",
    " background-color: lightgrey\">Le traitement pouvant être particulièrement long,",
    " il va maintenant continuer en tâche de fond.<br>\n",
    'Le rapport final d\'importation sera accessible à l\'adresse :<br>',
    "<div style=\"text-align: center; font-family: monospace\">",
    "<a href=\"$hostname/$webDir/result.$pid.html\">",
    "$hostname/$webDir/result.$pid.html</a></div>\n",
    "Une fois le traitement terminé, utilisez l'annuaire pour vérifier la validité des résultats.",
    "</div>\n";

  &pdp(STDOUT);

  unlink('/tmp/EnCours.temp');

}

sub traitement_fond {

  # Attente de fin du traitement préparatoire
  sleep(3);
  $inc=0;
  while (1) {
    sleep 1;
    $inc++;
    if ($inc == 30) {
      # Fermeture des entrées/sorties standard
      close(STDIN); close(STDOUT);
      open RES, ">$documentRoot/$webDir/result.$$.html";
      &entete(RES);
      print RES
	"<strong>Le traitement préparatoire des fichiers GEP semble avoir été interrompu.<br>",
	"Le traitement des fichiers prêts va tout de même se poursuivre.<br>",
	"ATTENTION : votre importation risque de ne pas être complète...<br></strong>";
      last;
    }
    if (! -f '/tmp/EnCours.temp') {
      # Fermeture des entrées/sorties standard
      close(STDIN); close(STDOUT);
      open RES, ">$documentRoot/$webDir/result.$$.html";
      &entete(RES);
      print RES
	"<strong>Le traitement préparatoire s'est terminé avec succès.</strong><br>";
      last;
    }
  }

  open INT, '</tmp/gepInterface.temp';
  $ligne = <INT>;
  ($prefix, $annuelle) = split /:/, $ligne;
  close INT;
  $prefix = '' unless $prefix;

  annuelle() if ($annuelle);

  # Création des entrées
  # ====================

  # Initialisation des variables
  # ----------------------------
  # Uid de départ
  $uidNumber = getFirstFreeUid(1000);
  # Gid de départ
  $gidNumber = getFirstFreeGid(2000);
  # Gid des utilisateurs LCS
  $gid = $defaultgid;

  unless
    (-f '/tmp/f_ele.temp'
     or -f '/tmp/f_wind.temp'
     or -f '/tmp/a_ind.temp'
     or -f '/tmp/f_men.temp'
     or -f '/tmp/f_div.temp') {
      exit 0;
    }

  # Connexion LDAP
  # ==============
  $lcs_ldap = Net::LDAP->new("$slapdIp");
  $lcs_ldap->bind(
		  dn       => $adminDn,
		  password => $adminPw,
		  version  => '3'
		 );

  # lecture de f_uid.temp
  if (-f '/tmp/f_uid.temp') {
    print RES "<h2>Lecture des identifiants 'uid' prédéfinis des utilisateurs </h2>\n<table>\n";
    open UID, '</tmp/f_uid.temp';
    while (<UID>) {
      chomp;
      s/#.*//;                # suppression des commentaires
      s/\s+//;                # suppression des blancs
      next unless length;
      if ( m/^(P?\d+)\|(.+)$/ ) {
        $Admin_UID{ $1 } = $2;
      }
    }
    print RES "<tr><td>" . keys( %Admin_UID ) . " uid lus</td></tr>\n";
    print RES "</table>\n";
    close UID;
  }

  # Profs
  # -----
  if (-f '/tmp/f_wind.temp') {
    print RES "<h2>Création des comptes 'Profs'</h2>\n<table>\n";
    open PROFS, '</tmp/f_wind.temp';
    while (<PROFS>) {
      chomp($ligne = $_);
      ($numind, $nom, $prenom, $date, $sexe)  = (split /\|/, $ligne);
      $uniqueNumber = 'P' . $numind;
      $res = processGepUser( $uniqueNumber, $nom, $prenom, $date, $sexe, 'undef' );
      print RES $res if ($res =~ /Cr/ or ($debug > 1 and $res !~ /Cr/));
      unless ($res =~ /conflits/) {
	# Ajoût de l'uid au groupe Profs
	$res = $lcs_ldap->search(base     => "$profsDn",
				 scope    => 'base',
				 filter   => "memberUid=$uid");
	unless (($res->entries)[0]) {
	  $res = $lcs_ldap->modify( $profsDn,
				    add => { 'memberUid' => $uid } );
	warn $res->error if $res->code;
	}
      }
    }
    print RES "</table>\n";
    close PROFS;
  }

  # Administratifs
  # --------------
  if (-f '/tmp/a_ind.temp') {
    print RES "<h2>Création des comptes 'Administratifs'</h2>\n<table>\n";
    open ADMINS, '</tmp/a_ind.temp';
    while (<ADMINS>) {
      chomp($ligne = $_);
      ($numind, $nom, $prenom, $date, $sexe)  = (split /\|/, $ligne);
      $uniqueNumber = 'A' . $numind;
      $res = processGepUser( $uniqueNumber, $nom, $prenom, $date, $sexe, 'undef' );
      print RES $res if ($res =~ /Cr/ or ($debug > 1 and $res !~ /Cr/));
      unless ($res =~ /conflits/) {
	# Ajoût de l'uid au groupe Administratifs
	$res = $lcs_ldap->search(base     => "cn=Administratifs,$groupsDn",
				 scope    => 'base',
				 filter   => "memberUid=$uid");
	unless (($res->entries)[0]) {
	  $res = $lcs_ldap->modify( "cn=Administratifs,$groupsDn",
				    add => { 'memberUid' => $uid } );
	warn $res->error if $res->code;
	}
      }
    }
    print RES "</table>\n";
    close ADMINS;
  }

  # Classes
  # -------
  if (-f '/tmp/f_div.temp') {
    print RES "<h2>Création des groupes 'Classe' et 'Equipe'</h2>\n<table>\n";
    open DIV, '</tmp/f_div.temp';
    while (<DIV>) {
      chomp($ligne = $_);
      ($divcod, $divlib, $profUniqueNumber) = (split/\|/, $ligne);
      $profUniqueNumber = 'P' . $profUniqueNumber;
      $divcod =~ s/\s/_/;
      $divlib = normalize($divlib,4);
      $libelle{$divcod} = $divlib;
      $res = $lcs_ldap->search(base     => "$peopleDn",
			       scope    => 'one',
			       filter   => "employeeNumber=$profUniqueNumber");
      $profPrincUid = '';
      if (($res->entries)[0]) {
	$profPrincUid = (($res->entries)[0])->get_value('uid');
      }
      # Recherche de l'existence de la classe
      $res = $lcs_ldap->search(base     => "cn=Classe_$prefix$divcod,$groupsDn",
			       scope    => 'base',
			       filter   => "cn=*");
      if (($res->entries)[0]) {
	if (! (($res->entries)[0])->get_value('description') and $divlib) {
	  print RES "<tr><td><strong><tt>$divcod</tt> :</strong></td><td>Mise à jour de la description du groupe 'Classe' : <em>$divlib</em></td></tr>\n" if $debug > 1;
	  $res2 = $lcs_ldap->modify( "cn=Classe_$prefix$divcod,$groupsDn",
				     add => { description => $divlib } );
	  warn $res2->error if $res2->code;
	}
      } else {
	$gidNumber = getFirstFreeGid($gidNumber);
	@classEntry = (
		       'cn',          "Classe_$prefix$divcod",
		       'objectClass', 'top',
		       'objectClass', 'posixGroup',
		       'gidNumber',   $gidNumber,
		      );
	push @classEntry, ('description', $divlib) if $divlib;
	$res = $lcs_ldap->add( "cn=Classe_$prefix$divcod,$groupsDn",
			       attrs => \@classEntry );
	warn $res->error if $res->code;
	print RES "<tr><td><strong><tt>$divcod</tt> :</strong></td><td>Création du groupe 'Classe' <em>$divlib</em></td></tr>\n" if $debug;
      }
      # Recherche de l'existence de l'équipe
      $res = $lcs_ldap->search(base     => "cn=Equipe_$prefix$divcod,$groupsDn",
			       scope    => 'base',
			       filter   => "cn=*");
      if (($res->entries)[0]) {
	if (! (($res->entries)[0])->get_value('description') and $divlib) {
	  print RES "<tr><td><strong><tt>$divcod</tt> :</strong></td><td>Mise à jour de la description du groupe 'Equipe' : <em>$divlib</em></td></tr>\n" if $debug > 1;
	  $res2 = $lcs_ldap->modify( "cn=Equipe_$prefix$divcod,$groupsDn",
				     add => { description => $divlib } );
	  warn $res2->error if $res2->code;
	}
	if (! (($res->entries)[0])->get_value('owner') and $profPrincUid) {
	  print RES "<tr><td><strong><tt>$divcod</tt> :</strong></td><td>Mise à jour du propriétaire du groupe 'Equipe' : <em>$divlib</em></td></tr>\n" if $debug > 1;
	  $res2 = $lcs_ldap->modify( "cn=Equipe_$prefix$divcod,$groupsDn",
				     add => { owner => "uid=$profPrincUid,$peopleDn" } );
	  warn $res2->error if $res2->code;
	}
	next;
      } else {
	@equipeEntry = (
		       'cn',          "Equipe_$prefix$divcod",
		       'objectClass', 'top',
		       'objectClass', 'groupOfNames',
		       'member',      ''
		      );
	push @equipeEntry, ('description', $divlib) if $divlib;
	push @equipeEntry, ('owner', "uid=$profPrincUid,$peopleDn") if $profPrincUid;
	$res = $lcs_ldap->add( "cn=Equipe_$prefix$divcod,$groupsDn",
			       attrs => \@equipeEntry );
	warn $res->error if $res->code;
	print RES "<tr><td><strong><tt>$divcod</tt> :</strong></td><td>Création du groupe 'Equipe' <em>$divlib</em></td></tr>\n" if $debug > 1;
      }
    }
    print RES "</table>\n";
  }

  # Eleves
  # -----
  if (-f '/tmp/f_ele.temp') {
    print RES "<h2>Création des comptes 'Eleves'";
    print RES " <span style=\"font-size: small\">(et des groupes 'Classes' et 'Equipes' associés)</span>"
      unless (-f '/tmp/f_div.temp');
    print RES "</h2>\n<table>\n";
    open ELEVES, '</tmp/f_ele.temp';
    while (<ELEVES>) {
      chomp($ligne = $_);
      ($uniqueNumber, $nom, $prenom, $date, $sexe, $divcod)  = (split /\|/, $ligne);
      $divcod =~ s/\s/_/g;
      next if $divcod eq '';
      unless (-f '/tmp/f_div.temp') {
	# Création des classes
	$res = $lcs_ldap->search(base     => "cn=Classe_$prefix$divcod,$groupsDn",
				 scope    => 'base',
				 filter   => "cn=*");
	unless (($res->entries)[0]) {
	  $gidNumber = getFirstFreeGid($gidNumber);
	  @classEntry = (
			 'cn',          "Classe_$prefix$divcod",
			 'objectClass', 'top',
			 'objectClass', 'posixGroup',
			 'gidNumber',   $gidNumber,
			);
	  $res = $lcs_ldap->add( "cn=Classe_$prefix$divcod,$groupsDn",
				 attrs => \@classEntry );
	  warn $res->error if $res->code;
	}
	# Création des Équipes
	$res = $lcs_ldap->search(base     => "cn=Equipe_$prefix$divcod,$groupsDn",
				 scope    => 'base',
				 filter   => "cn=*");
	unless (($res->entries)[0]) {
	  @equipeEntry = (
			 'cn',          "Equipe_$prefix$divcod",
			 'objectClass', 'top',
			 'objectClass', 'groupOfNames',
			 'member',      ''
			);
	  $res = $lcs_ldap->add( "cn=Equipe_$prefix$divcod,$groupsDn",
				 attrs => \@equipeEntry );
	  warn $res->error if $res->code;
	}
      }
      $res = processGepUser($uniqueNumber, $nom, $prenom, $date, $sexe, 'undef');
      print RES $res if ($res =~ /Cr/ or ($debug > 1 and $res !~ /Cr/));
      unless ($res =~ /conflits/) {
	# Ajoût de l'uid au groupe Eleves
	$res = $lcs_ldap->search(base     => "$elevesDn",
				 scope    => 'base',
				 filter   => "memberUid=$uid");
	unless (($res->entries)[0]) {
	  $res = $lcs_ldap->modify(
				   $elevesDn,
				   add => { 'memberUid' => $uid }
				  );
	  warn $res->error if $res->code;
	}
	# Remplissage des classes
	$res = $lcs_ldap->search(base     => "cn=Classe_$prefix$divcod,$groupsDn",
				 scope    => 'base',
				 filter   => "memberUid=$uid");
	unless (($res->entries)[0]) {
	  $res = $lcs_ldap->modify(
				   "cn=Classe_$prefix$divcod,$groupsDn",
				   add => { 'memberUid' => $uid }
				  );
	  warn $res->error if $res->code;
	}
      }
    }
    print RES "</table>";
    close ELEVES;
  }

  # Analyse du fichier F_TMT.DBF
  # ----------------------------
  if (-f '/tmp/f_tmt.temp') {
    print RES "<h2>Analyse du fichier de matières</h2>\n<table>\n" if $debug > 1;
    open F_TMT, "</tmp/f_tmt.temp";
    while (<F_TMT>) {
      chomp ($ligne = $_);
      ($matimn, $matill) = (split/\|/, $ligne);
      $matimn =~ s/\s/_/g;
      if ($matill) {
	$matill = normalize($matill,0);
	# Alimentation du tableau %libelle
	$libelle{$matimn} = $matill;
	print RES "<tr><td>Matière <tt><strong>$matimn</strong></tt> :</td><td>Libellé : <em>$libelle{$matimn}</em></td></tr>\n" if $debug > 1;
      }
    }
    close F_TMT;
    print RES "</table>\n" if $debug > 1;
  }

  # Analyse du fichier F_GRO.DBF
  # ----------------------------
  if (-f '/tmp/f_gro.temp') {
    print RES "<h2>Analyse du fichier de groupes</h2>\n<table>\n" if $debug > 1;
    open F_GRO, "</tmp/f_gro.temp";
    while (<F_GRO>) {
      chomp ($ligne = $_);
      ($grocod, $grolib) = (split/\|/, $ligne);
      $grocod =~ s/\s/_/g;
      if ($grolib) {
	$grolib = normalize($grolib,0);
	# Alimentation du tableau %libelle
	$libelle{$grocod} = $grolib;
	print RES "<tr><td>Groupe <tt><strong>$grocod</strong></tt> :</td><td>Libellé : <em>$libelle{$grocod}</em></td></tr>\n" if $debug > 1;
      }
    }
    close F_GRO;
    print RES "</table>\n" if $debug > 1;
  }

  # Analyse du fichier F_EAG.DBF
  # ----------------------------
  if (-f '/tmp/f_eag.temp') {
    print RES "<h2>Analyse du fichier des élèves par groupe</h2>\n<table>\n" if $debug > 1;
    open F_EAG, "</tmp/f_eag.temp";
    while (<F_EAG>) {
      chomp ($ligne = $_);
      ($uniqueNumber, $grocod) = (split/\|/, $ligne);
      $grocod =~ s/\s/_/g;
      # Alimentation du tableau %member associant un code classe
      # ou groupe avec une liste d'uid (login) d'élèves
      $res = $lcs_ldap->search(base     => "$peopleDn",
			       scope    => 'one',
			       filter   => "employeeNumber=$uniqueNumber");
      if (($res->entries)[0]) {
	$uid = (($res->entries)[0])->get_value('uid');
	$member{$grocod} .= "$uid ";
	print RES "<tr><td>Élève <tt><strong>$uid</strong></tt> :</td><td>Groupe : <em><tt>$grocod</tt></em></td></tr>\n" if $debug > 1;      }
    }
    close F_EAG;
    print RES "</table>\n" if $debug > 1;
  }

  # Analyse du fichier F_MEN
  # ------------------------
  if (-f '/tmp/f_men.temp') {
    open F_MEN, "</tmp/f_men.temp";
    print RES "<h2>Création des groupes 'Cours' et 'Matiere'</h2>\n<table>\n";
    while (<F_MEN>) {
      chomp ($ligne = $_);
      ($matimn, $elstco, $uniqueNumber) = (split/\|/, $ligne);
      $matimn =~ s/\s/_/g;
      $elstco =~ s/\s/_/g;
      # Génération du nom du cours (mnémoniqueMatière_codeGroupe)
      $cours = $matimn . '_' . $elstco;
      if ($uniqueNumber) {
        $uniqueNumber = 'P' . $uniqueNumber;
	$res = $lcs_ldap->search(base     => "$peopleDn",
				 scope    => 'one',
				 filter   => "employeeNumber=$uniqueNumber");
	if (($res->entries)[0]) {
	  $profUid = (($res->entries)[0])->get_value('uid');
	}  else {
	  $profUid = '';
	}
      } else {
	$profUid = '';
      }
      if ($libelle{$matimn}) {
	$description = $libelle{$matimn};
      } else {
	$description = $matimn;
      }
      if ($libelle{$elstco}) {
	$description .= " / " . $libelle{$elstco};
      } else {
	$description .= " / " . $elstco;
      }
      $res = $lcs_ldap->search(base     => "cn=Cours_$prefix$cours,$groupsDn",
			       scope    => 'base',
			       filter   => "objectClass=*");
      if (($res->entries)[0]) {
	# Mise à jour le cas échéant de la description
	if ((($res->entries)[0]->get_value('description') =~ /$matimn/
	     and $description !~ /$matimn/)
	    or (($res->entries)[0]->get_value('description') =~ /$elstco/
		and $description !~ /$elstco/)) {
	  $res2 = $lcs_ldap->modify( "cn=Cours_$prefix$cours,$groupsDn",
				     replace => { description => $description } );
	  warn $res2->error if $res2->code;
	  print RES "<tr><td>Cours <span class=\"abbrev\">gep</span> <strong>$cours</strong> : </td><td>Mise à jour de la description du groupe 'Cours'</td></tr>\n" if $debug > 1;
	}
      } else {
	$gidNumber = getFirstFreeGid($gidNumber);
	@coursEntry = (
		       'cn',          "Cours_$prefix$cours",
		       'objectClass', 'top',
		       'objectClass', 'posixGroup',
		       'gidNumber',   $gidNumber,
		       'description', $description,
		      );
	push @coursEntry, ('memberUid', $profUid) if $profUid;
	$res = $lcs_ldap->add( "cn=Cours_$prefix$cours,$groupsDn",
			       attrs => \@coursEntry );
	warn $res->error if $res->code;
	print RES "<tr><td>Cours <span class=\"abbrev\">gep</span> <strong>$cours</strong> : </td><td>Création du groupe 'Cours'</td></tr>\n" if $debug;
      }
      # Ajout du prof le cas échéant
      if ($profUid) {
	$res = $lcs_ldap->search(base     => "cn=Cours_$prefix$cours,$groupsDn",
				 scope    => 'base',
				 filter   => "memberUid=$profUid");
	if (! ($res->entries)[0]) {
	  $res = $lcs_ldap->modify( "cn=Cours_$prefix$cours,$groupsDn",
				    add => { memberUid => $profUid } );
	  warn $res->error if $res->code;
	}
      }
      # Ajout des autres membres du cours
      $res = $lcs_ldap->search(base     => "cn=Classe_$prefix$elstco,$groupsDn",
			       scope    => 'base',
			       filter   => "cn=*");
      if ($member{$elstco}) {
	# Cas d'un groupe
	chop($members = $member{$elstco});
 	foreach $member (split / /, $members) {
	  $res = $lcs_ldap->search(base     => "cn=Cours_$prefix$cours,$groupsDn",
				   scope    => 'base',
				   filter   => "memberUid=$member");
	  if (! ($res->entries)[0]) {
	    $res = $lcs_ldap->modify( "cn=Cours_$prefix$cours,$groupsDn",
				      add => { memberUid => $member } );
	    warn $res->error if $res->code;
	  }
	  print RES "<tr><td>Cours <span class=\"abbrev\">gep</span> <strong>$cours</strong> : </td><td>Ajoût des élèves du groupe</td></tr>\n" if $debug > 1;
	}
      } else {
	# cas d'une classe
 	$res = $lcs_ldap->search(base     => "cn=Classe_$prefix$elstco,$groupsDn",
 				 scope    => 'base',
 				 filter   => "objectClass=*");
	if (($res->entries)[0]) {
	  @members = ($res->entries)[0]->get_value('memberUid');
	  foreach $member (@members) {
	    $res = $lcs_ldap->search(base     => "cn=Cours_$prefix$cours,$groupsDn",
				     scope    => 'base',
				     filter   => "memberUid=$member");
	    if (! ($res->entries)[0]) {
	      $res = $lcs_ldap->modify( "cn=Cours_$prefix$cours,$groupsDn",
					add => { memberUid => $member } );
	      warn $res->error if $res->code;
	    }
	  }
	}
      }

      if ($profUid) {
	# Remplissage de l'équipe pédagogique de la classe
	$res = $lcs_ldap->search(base     => "cn=Equipe_$prefix$elstco,$groupsDn",
				 scope    => 'base',
				 filter   => "objectClass=*");
	if (($res->entries)[0]) {
	  $res = $lcs_ldap->search(base     => "cn=Equipe_$prefix$elstco,$groupsDn",
				   scope    => 'base',
				   filter   => "member=uid=$profUid,$peopleDn");
	  unless (($res->entries)[0]) {
	    $res = $lcs_ldap->modify( "cn=Equipe_$prefix$elstco,$groupsDn",
				      add => { member => "uid=$profUid,$peopleDn" } );
	    warn $res->error if $res->code;
	  }
	}
 	# Remplissage et/ou création du GroupOfNames Matiere
 	# Si la matière n'existe pas encore
 	$res = $lcs_ldap->search(base     => "cn=Matiere_$prefix$matimn,$groupsDn",
 				 scope    => 'base',
 				 filter   => "objectClass=*");
 	if (! ($res->entries)[0]) {
 	  @matiereEntry = (
 			   'cn',          "Matiere_$prefix$matimn",
 			   'objectClass', 'top',
 			   'objectClass', 'groupOfNames',
 			   'member',   '',
 			  );
 	  push @matiereEntry, ('description', $libelle{$matimn}) if $libelle{$matimn};
 	  $res = $lcs_ldap->add( "cn=Matiere_$prefix$matimn,$groupsDn",
 				 attrs => \@matiereEntry );
 	} elsif (! ($res->entries)[0]->get_value('description') and $libelle{$matimn}) {
 	  # Maj Libellé Matière
	  $res = $lcs_ldap->modify( "cn=Matiere_$prefix$matimn,$groupsDn",
				    add => { description => $libelle{$matimn} } );
	  warn $res->error if $res->code;
 	}
 	# Avec ses membres
 	$res = $lcs_ldap->search(base     => "cn=Matiere_$prefix$elstco,$groupsDn",
 				 scope    => 'base',
				 filter   => "member=uid=$profUid,$peopleDn");
 	unless (($res->entries)[0]) {
	  $res = $lcs_ldap->modify( "cn=Matiere_$prefix$matimn,$groupsDn",
 				    add => { member => "uid=$profUid,$peopleDn" } );
 	}
      }
    }
    print RES "</table>\n";
    close F_MEN;
  }

  unlink </tmp/*.temp>;
  $lcs_ldap->unbind;
  &pdp(RES);
  close RES;

  system ("/usr/bin/lynx --dump $documentRoot/$webDir/result.$$.html | mail $melsavadmin -s 'Importation GEP'");

}
