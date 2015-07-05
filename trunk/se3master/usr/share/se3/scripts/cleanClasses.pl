#!/usr/bin/perl -w

#####
# $Id$
# Nettoie l'arborescence des partages Classes
# efface les vieux dossiers ( dossiers eleve/Archive et .eleve ) 
# syntaxe : cleanClasses.pl ALL|Classe
#     - ALL       : pour passer en revue toutes les classes 
#     - NomClasse : par exemple 1-S1 pour nettoyer la Classe Classe_1-S1 
#
#       Denis Bonnenfant (denis.bonnenfant@diderot.org) 
#       
#####


# supprime les Warnings du module Se.pm
BEGIN { $SIG{'__WARN__'} = sub { warn $_[0] if $DOWARN } }
use Se;
$DOWARN = 1; # Warnings activés à nouveau

$PathClasses = '/var/se3/Classes';
die("Syntaxe : cleanClasses.pl ALL|Classe") if ($#ARGV != 0);
($Classe) = @ARGV;
if ($Classe eq 'ALL') {
  $FILTRE = "(cn=Classe_*)";
} else {
  $FILTRE = "(cn=Classe_$Classe)";
}

$lcs_ldap = Net::LDAP->new("$slapdIp");
$lcs_ldap->bind(
        dn       => $adminDn,
        password => $adminPw,
        version  => '3');
$res = $lcs_ldap->search(base => "$groupsDn",
       scope    => 'one',
       filter   => "$FILTRE");
die $res->error if $res->code;

if (($res->entries)[0]) {
  # Au moins une classe a été trouv&e
  foreach $objClasse ($res->entries) {
    $cnClasse = $objClasse->get_value('cn');
    $Classe = $cnClasse;
    $Classe =~ s/^Classe_// ;
    print "Nettoyage  de la classe : $Classe<br>\n";
    #Vérification l'existence du posixGroup Equipe_$Classe
    $resProfs = $lcs_ldap->search(base     => "$groupsDn",
           scope    => 'one',
           filter   => "(&(cn=Equipe_$Classe)(objectClass=posixGroup))");
    warn $resProfs->error if $resProfs->code;
    if (!($resProfs->entries)[0]) {
        warn "Erreur: Le posixGroup Equipe_$Classe n'existe pas!<br>\n";
    } else {
      if ( -d "$PathClasses/$cnClasse") {
        # premiere passe : on efface les anciens élèves
        system("rm -fr $PathClasses/$cnClasse/.* 2>/dev/null");
	# on efface les archives
	print("Effacement des dossiers archives de $cnClasse<br>\n");  
        @eleve = <$PathClasses/$cnClasse/*>;
          foreach $eleve (@eleve) { 
            if ( $eleve =~ m!^$PathClasses/$cnClasse/_! ) {
              print "r&#233;pertoire '$eleve' ignor&#233;.<br>\n"; 
            } else {
              # D.B. On met à jour ls eanciens eleves de la classe
              # test de l'inversion prenom.nom dans le cas de login prenom.nom
	      $login = $eleve ;
              $login =~ s!^$PathClasses/$cnClasse/!! ;
	      @NOM = split(/\./, $login);
	      if ( @NOM == 2 ) {
                # on inverse
                $login = @NOM[1] . "." . @NOM[0];
              } 
	    }    
            if ( -d "$eleve/Archives" ) {
		print("Effacement du dossier Archives de $login,<br>\n");
		system("rm -fr $eleve/Archives");
            }
          }
      }
    }
  }
}
exit 0 ;
