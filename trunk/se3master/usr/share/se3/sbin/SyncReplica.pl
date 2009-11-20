#!/usr/bin/perl
# SyncReplica.pl
#
# Auteur : Vincent MATHIEU. Université Nancy 2 CRI
#          Vincent.Mathieu@univ-nancy2.fr
# Version 2.0 Maj le 14 juin 2003
#     passage de la librairie perldap de Mozilla vers la librairie Net::LDAP
#     paramétrage de l'utilitaire plus 'convivial'
#     meilleurs traitement des atributs administratifs
#
#
# cet utilitaire controle les entrees LDAP d'un serveur LDAP maitre, et tente eventuellement de
# mettre a jour un serveur LDAP esclave.
#
# il recoit les parametres suivants :
# -master : obligatoire. le serveur maitre (ex : ldap.univ.fr, ou ldap.univ.fr:392 si autre port TCP)
# -slave : obligatoire. le réplica (ex : ldap2.univ.fr, ou ldap2.univ.fr:392 si autre port TCP)
# -basedn : obligatiore. le DN a partir duquel on travaille
# -option : facultatif. 3 possibliltés :
#           . anonymous . c'est l'option par défaut. dans ce cas, il y a exploration des 2 serveurs LDAP
#                        et generation d'un message en cas de divergence d'existence d'entrees LDAP
#           . only_pass . Il y a a controle de l'existence des entrées LDAP, et du password. Les paramètres ficmaster et ficslave sont alors obligatoires. il ya a MaJ du mot de passe dans l'esclave
#           . full . Toutes les entrees sont comparees, avec tous les attributs. Les parametres ficmaster let ficslave sont obligatoires. il y a Maj de l'entrée esclave (ou suppression, si pas dans le maitre)
# -bindmaster : c'est un fichier qui contient le DN et mot de passe associe pour lire toute information dans le LDAP maitre. Le fichier contient une ligne, constituee du DN, et du    mot de passe separes par le caractere ';'. 
# -bindslave : itou ficmaster, pour le DN ayant le droit de replication sur l'esclave.
#
#la variable TousAttributs est eventuellement a personnaliser.
#

#use strict;
use Net::LDAP;

use vars qw(%DN1 %DN2 $BaseDN $Serv1 $Serv2 @TabOU  $Option $ErrAttrib @TousAttributs);
use vars qw($Port1 $Port2 $CodeRetour $ErrLDAP $ErrGlob $nbTraite);
use vars qw($bindMaster $bindSlave);

$SIG{'ALRM'}  = 'ArretTimeout';

{
  my ($NomFicErrs, $DNAdmin, $PassAdmin, $DN, $connLDAP1, $connLDAP2);
  my ($LaDate, $OU, $nbEntrees, $Tmp, $timeout, $mesg, $ind);

  $NomFicErrs = "/tmp/ErrReplica.txt";
  $timeout = 900;      # delai d'execution en secondes, avant erreur
  @TousAttributs = ['*', "structuralObjectClass", "entryUUID", "creatorsName", "createTimestamp",
                      "entryCSN", "modifiersName", "modifyTimestamp", "subschemaSubentry", "hasSubordinates"];

  alarm($timeout);

  $CodeRetour = 0;
  $DNAdmin = "";
  $PassAdmin = "";
  $nbTraite = 0;
  $Option = 0;
  $| = 1;       # flush le stdout

#  ------ recuperation des parametres --------
  for ($ind = 0; $ind < scalar @ARGV; $ind++) 
  {
    if ($ARGV[$ind] eq "-master")
    {
      $ind++;
      ($Serv1, $Port1) = split(":", $ARGV[$ind]);
      $Port1 = 389 if ($Port1 eq "");
    }
    elsif ($ARGV[$ind] eq "-slave")
    {
      $ind++;
      ($Serv2, $Port2) = split(":", $ARGV[$ind]);
      $Port2 = 389 if ($Port2 eq "");
    }
    elsif ($ARGV[$ind] eq "-bindmaster")
    {
      $ind++;
      $bindMaster = $ARGV[$ind];
    }
    elsif ($ARGV[$ind] eq "-bindslave")
    {
      $ind++;
      $bindSlave = $ARGV[$ind];
    }
    elsif ($ARGV[$ind] eq "-basedn")
    {
      $ind++;
      $BaseDN = $ARGV[$ind];
    }
    elsif ($ARGV[$ind] eq "-option")
    {
      $ind++;
      my $opt = $ARGV[$ind];
      if ($opt eq "anonymous")
      {
        $Option = 0;
      }
      elsif($opt eq "only_pass")
      {
        $Option = 1;
      }
      elsif($opt eq "full")
      {
        $Option = 2;
      }
      else
      {
	 &ErrSyntaxe();
      }
    }
  }

  &ErrSyntaxe() if (($Serv1 eq "") || ($Serv2 eq "") || ($BaseDN eq ""));
  &ErrSyntaxe() if (($Option > 0) && (($bindMaster eq "") || ($bindSlave eq "")));
  
  die "impossible de creer le fichier $NomFicErrs" if (! open(FICERR, ">$NomFicErrs"));
  $LaDate = GetDate();

  select FICERR;
  $| = 1;               # fichier vide a chaque ecriture
  select STDOUT;

  print FICERR "\t*** controle replica avec $Serv2:$Port2, le $LaDate ***\n\n";
  print FICERR "   Recherche rapide : entrees inexistantes ou en trop\n\n" if ($Option == 0);
  print FICERR "   Recherche rapide : entrees inexistantes ou en trop et mots de passe\n\n" if ($Option == 1);
  print FICERR "   Recherche complete\n\n" if ($Option == 2);

  if (! ($connLDAP2 = new Net::LDAP($Serv2, port => $Port2 )))
  {
    print FICERR "ERREUR. connexion LDAP refusee sur $Serv2:$Port2\n" ;
    exit 1;
  }
  if ($Option == 0)
  {
     $mesg = $connLDAP2->bind ();
  }
  else
  {
    &RecupAdmin($bindSlave, $DNAdmin, $PassAdmin);
    $mesg = $connLDAP2->bind ( dn => $DNAdmin, password  => $PassAdmin, version => 3 );
  }
  if ($mesg->is_error)
  {
    print FICERR "ERREUR. bind LDAP refuse sur $Serv2:$Port2 pour $DNAdmin\n" ;
    exit 1;
  } 

  $DNAdmin = $PassAdmin = "";
  if (! ($connLDAP1 = new Net::LDAP($Serv1, port => $Port1 )))
  {
    print FICERR "ERREUR. connexion LDAP refusee sur $Serv1:$Port1\n" ;
    exit 1;
  }
  if ($Option == 0)
  {
     $mesg = $connLDAP1->bind ();
  }
  else
  {
    &RecupAdmin($bindMaster, $DNAdmin, $PassAdmin);
    $mesg = $connLDAP1->bind ( dn => $DNAdmin, password  => $PassAdmin, version => 3 );
  }
  if ($mesg->is_error)
  {
    print FICERR "ERREUR. bind LDAP refuse sur $Serv1:$Port1 pour $DNAdmin\n" ;
    exit 1;
  } 

  if (! &GetOU(\$connLDAP1, $BaseDN))
  {
    print FICERR "ERREUR. $BaseDN n'existe pas sur $Serv1\n" ;
    exit 1;
  }

  foreach $OU (@TabOU)
  {
    print FICERR "Traitement de $OU\n" ;
    $nbEntrees = &TraiteOU(\$connLDAP1, \$connLDAP2, $OU);
    print FICERR "$OU : $nbEntrees entrees parcourues\n\n";
    
  }
  
  $connLDAP1->unbind;
  $connLDAP2->unbind;
  $LaDate = GetDate();
  print FICERR "\t*** Fin de traitement de $Serv2:$Port2, le $LaDate ***\n\n";
  exit $CodeRetour;
}


# traitement des entrees d'une OU
# param 0 : pointeur sur handle de connexion LDAP
# param 1 : pointeur sur handle de connexion LDAP
# param 2 : OU de recherche
sub TraiteOU
{
  my ($OU, $DN, $nbEntrees, $Ret, $connLDAP1, $connLDAP2, $ListeAttrib, $nb2);
  $connLDAP1 = shift;
  $connLDAP2 = shift;
  $OU = shift;
  undef %DN1;
  undef %DN2;
  $nbEntrees = &RecupLDAP($connLDAP1, $OU, \%DN1);    # recup des DN de la branche du serveur 1
  if ($nbEntrees < 0)
  {
      $nbEntrees = - $nbEntrees;
      print FICERR "\nErreur $nbEntrees lors de lecture de $OU sur $Serv1 : $ErrLDAP\n";
      return -1;
  }
  $nb2 = &RecupLDAP($connLDAP2, $OU, \%DN2);    # recup des DN de la branche du serveur 2
  if ($nb2 < 0)
  {
      $nb2 = - $nb2;
      print FICERR "\nErreur $nb2 lors de lecture de $OU sur $Serv2 : $ErrLDAP\n";
      return -1;
  }

  foreach $DN (keys %DN1)    #  parcours des entrées de serveur 1
  {
    $nbTraite++;
    if (! defined($DN2{$DN}))
    {
      print FICERR "\n  ERREUR. DN $DN,$OU manque dans $Serv2:$Port2\n";
      $CodeRetour = 2;
      if ($Option != 0)   # pas anonyme
      {
        $Ret = &CreeEntree($connLDAP1, $connLDAP2, "$DN,$OU");
        if ($Ret)
        {
          print FICERR "    Creation de $DN,$OU OK dans $Serv2:$Port2\n";
        }
        else
        {
          print FICERR "    Creation de $DN,$OU impossible dans $Serv2:$Port2. $ErrGlob\n";
          $ErrGlob = "";
        }
      }
    }
    else   # les entrees existent dans les 2 serveurs. On les compare
    {
      if ($Option == 1)   # comparaison password
      {
        if ($DN1{$DN} ne $DN2{$DN})
        {
	  $CodeRetour = 2;
          print FICERR "\n  ERREUR. Mot de passe different pour $DN,$OU\n";
          $Ret = &MajPassword($connLDAP2, "$DN,$OU", $DN1{$DN});
          if ($Ret)
          {
            print FICERR "    Mise a jour de $DN,$OU OK dans $Serv2:$Port2\n";
          }
          else
          {
            print FICERR "    Mise a jour de $DN,$OU impossible dans $Serv2:$Port2. $ErrGlob\n";
            $ErrGlob = "";
          }
        }
      }
      elsif ($Option == 2)   # comparaison totale
      {
        $Ret = &CompareDN($connLDAP1, $connLDAP2, "$DN,$OU", \$ListeAttrib);
        if ($Ret < 1)
        {
	  $CodeRetour = 2;
          print FICERR "\n  ERREUR. Divergences avec $DN,$OU sur $ListeAttrib\n";
          if ($Ret == 0)
          {
            print FICERR "    Mise a jour de $DN,$OU OK dans $Serv2:$Port2\n";
          }
          else
          {
            print FICERR "    Mise a jour de $DN,$OU impossible dans $Serv2:$Port2. $ErrGlob\n";
            $ErrGlob = "";
          }
        }
      }
    }
    $DN2{$DN} = "1";
  }
  foreach $DN (keys %DN2)    # recherche des entrées dans serveur 2 pas dans serveur 1
  {
    if ($DN2{$DN} ne "1")
    {
      $CodeRetour = 2;
      print FICERR "  ERREUR. DN $DN,$OU en trop dans $Serv2:$Port2\n";
      if ($Option != 0)    # pas anonyme
      {
        $Ret = SupEntree($connLDAP2, "$DN,$OU");
        if ($Ret)
        {
          print FICERR "    Suppression de $DN,$OU OK dans $Serv2\n";
        }
        else
        {
          print FICERR "    Suppression de $DN,$OU impossible dans $Serv2. $ErrGlob\n";
          $ErrGlob = "";
        }
      }
    }
  }
  return $nbEntrees;
}

# comparaison de 2 entrees
# param 0 : pointeur sur handle de connexion serveur1
# param 1 : pointeur sur handle de connexion serveur2
# param 2 : DN a comparer
# param 3 : en retour : pointeur vers liste des attributs differents
# retourne 1 si OK, 0 si different et repare, -1 si different et pas repare
sub CompareDN
{
  my($Entry1, $Entry2, $connLDAP1, $connLDAP2, $DN, $ListeAttrib, $mesg, $retour, %modAttribs);
  my($Attrib, $Nbre, $ind, %BadAttribs, $refTabAttrib1, $refTabAttrib2, $val1, $val2, $trouve);
  $connLDAP1 = shift;
  $connLDAP2 = shift;
  $DN = shift;
  $ListeAttrib = shift;

  $retour = 1;
  $mesg = $$connLDAP1->search(base => $DN, scope => "base", filter => "(objectclass=*)");
  return -1 if (($mesg->is_error) || ($mesg->count == 0));
  $Entry1 = $mesg->shift_entry;
  $mesg = $$connLDAP2->search(base => $DN, scope => "base", filter => "(objectclass=*)");
  return -1 if (($mesg->is_error) || ($mesg->count == 0));
  $Entry2 = $mesg->shift_entry;
       #----  on balaie tous les attributs de l'entree 1, et on marque ceux qui different -----
  foreach $Attrib ( $Entry1->attributes )
  {
    if (! $Entry2->exists($Attrib))
    {
      $refTabAttrib1 = $Entry1->get_value($Attrib, asref => 1);
      my @tabVal = @$refTabAttrib1;
      $modAttribs{$Attrib} = \@tabVal;
    }
    else
    {
      $refTabAttrib1 = $Entry1->get_value($Attrib, asref => 1);
      $refTabAttrib2 = $Entry2->get_value($Attrib, asref => 1);
      if (scalar @$refTabAttrib1 != scalar @$refTabAttrib2) # nombre de valeurs differents
      {
        my @tabVal = @$refTabAttrib1;
        $modAttribs{$Attrib} = \@tabVal;
      }
      else
      {
        $trouve = 0;
        foreach $val1 (@$refTabAttrib1)  # on balaie les valeurs de l'attribut
        {
          foreach $val2 (@$refTabAttrib2)  # on cherche equivalence  
          {
            if ($val1 eq $val2)
            {
              $trouve = 1;
              last;
	    }
          }
          if (! $trouve)
          {
            my @tabVal = @$refTabAttrib1;
            $modAttribs{$Attrib} = \@tabVal;
	  }
        }
      }
    }
  }
       #----  on cherche les attributs de l'entree 2 qui ne seraient pas dans entree 1 -----
  foreach $Attrib ( $Entry2->attributes )
  {
    if (! $Entry1->exists($Attrib))  # on vire dans entree2 si dans entree2 et pas entree1
    {
      my @tabVal;
      $modAttribs{$Attrib} = \@tabVal;
    }
  }
  if ((scalar keys %modAttribs) != 0)   #y a des modifs
  {
    $retour = 0;
    $$ListeAttrib = "";
    foreach $Attrib (keys %modAttribs)
    {
	$$ListeAttrib .= ", $Attrib";
    }
    $$ListeAttrib = substr($$ListeAttrib, 2);
    &MajAttribs($connLDAP2, $DN, \%modAttribs);
    #return -1 if (! &RemplaceEntree($connLDAP1, $connLDAP2, $DN ));
  }
  return $retour;
}

# MaJ du mot de passe
# param 0 : pointeur sur handle de connexion serveur2
# param 1 : DN a modifier
# param 2 : le mot de passe
sub MajPassword
{
  my($Entry2, $connLDAP2, $DN, $Erreur, $NewPass, $mesg);
  $connLDAP2 = shift;
  $DN = shift;
  $NewPass = shift;
  return 1 if (($NewPass eq "") || ($NewPass eq "azerty"));
  $mesg = $$connLDAP2->search(base => $DN, scope => "base", filter => "(objectclass=*)",
                 attrs => 'userpassword');
  if (($mesg->is_error) || ($mesg->count == 0))
  {
    my $code = $mesg->code;
    my $message = $mesg->error;
    $ErrGlob = "erreur LDAP, code = $code : $message\n";
    return 0;
  }
  $Entry2 = $mesg->shift_entry;
#  if ($Entry2->get_value('userpassword') eq "")   # pas la peine de tester. replace ajoute si existe pas
#  {
#      $mesg = $$connLDAP2->modify($DN, add => { 'userpassword' => $NewPass });
#  }
#  else
  $mesg = $$connLDAP2->modify($DN, replace => { 'userpassword' => $NewPass });
  if ($mesg->is_error)
  {
    my $code = $mesg->code;
    my $message = $mesg->error;
    $ErrGlob = "erreur LDAP, code = $code : $message\n";
    return 0; 
  }
  return 1;
}



# creation d'une entree du maitre vers le replica
# param 0 : pointeur sur handle de connexion serveur1
# param 1 : pointeur sur handle de connexion serveur2
# param 2 : DN a creer
sub CreeEntree
{
  my($Entry, $connLDAP1, $connLDAP2, $DN, $mesg, $code, $message);
  $connLDAP1 = shift;
  $connLDAP2 = shift;
  $DN = shift;

  $mesg = $$connLDAP1->search(base => $DN, scope => "base", filter => "(objectclass=*)",
                 attrs => @TousAttributs);
  if (($mesg->is_error) || ($mesg->count == 0))
  {
    my $code = $mesg->code;
    my $message = $mesg->error;
    $ErrGlob = "erreur recup infos DN, code = $code : $message\n";
    return 0; 
  }
  $Entry = $mesg->shift_entry;
  $mesg = $$connLDAP2->add($Entry);
  if ($mesg->is_error)
  {
    my $code = $mesg->code;
    my $message = $mesg->error;
    $ErrGlob = "erreur creation DN, code = $code : $message\n";
    return 0; 
  }
  return 1;
}

# mise a jour d' attributs dans une entree LDAP locale
# param 0 : pointeur sur handle de connexion LDAP
# param 1 : DN a modifier
# param 1 : pointeur sur un taleau associatif des attributs a modifier / creer / supprimer
# retourne 1 si OK, 0 sinon
sub MajAttribs
{
  my ($connLDAP, $DN, $mesg, $modAttribs);
  $connLDAP = shift;
  $DN = shift;
  $modAttribs = shift;
#  $modAttribs{n2atraliasmail}[0] = "vmathieu";  
#  $modAttribs{n2atraliasmail}[1] = "Vincent.Mathieu";
#  $modAttribs{n2atrpersannu} = "O";
#  $modAttribs{n2atrpersras} = [];
  $mesg = $$connLDAP->modify($DN, replace => { %$modAttribs });
  if ($mesg->is_error)
  {
    my $code = $mesg->code;
    my $message = $mesg->error;
    $ErrGlob = "erreur creation DN, code = $code : $message\n";
    return 0; 
  }
  return 1;
}

# recopie d'une entree du maitre vers le replica
# param 0 : pointeur sur handle de connexion serveur1
# param 1 : pointeur sur handle de connexion serveur2
# param 2 : DN a creer
# !!!!!!! a priori, ne marche pas :
# avec $Entry->update($$connLDAP2) : code = 82 : No attributes to update et rien dans log LDAP
# avec $$connLDAP2->modify($Entry), il y a bien un MOD LDAP svec succes, mais pas de modif effective
sub RemplaceEntree
{
  my($Entry, $connLDAP1, $connLDAP2, $DN, $mesg, $code, $message);
  $connLDAP1 = shift;
  $connLDAP2 = shift;
  $DN = shift;

  $mesg = $$connLDAP1->search(base => $DN, scope => "base", filter => "(objectclass=*)",
                 attrs => @TousAttributs);
  if (($mesg->is_error) || ($mesg->count == 0))
  {
    $code = $mesg->code;
    $message = $mesg->error;
    $ErrGlob = "code = $code : $message";
    return 0;
  }
  $Entry = $mesg->shift_entry;
  printEntry(\$Entry);
#  $mesg = $Entry->update($$connLDAP2);
  $mesg = $$connLDAP2->modify($Entry);
  if ($mesg->is_error)
  {
    my $code = $mesg->code;
    my $message = $mesg->error;
    $ErrGlob = "erreur modification DN, code = $code : $message";
    return 0; 
  }
  return 1;
}


# suppression d'une entree dans replica
# param 0 : pointeur sur handle de connexion serveur2
# param 2 : DN a supprimer
sub SupEntree
{
  my($Entry, $connLDAP2, $DN, $Erreur, $mesg);
  $connLDAP2 = shift;
  $DN = shift;
  $mesg = $$connLDAP2->delete($DN);
  if ($mesg->is_error)
  {
    my $code = $mesg->code;
    my $message = $mesg->error;
    $ErrGlob = "erreur LDAP, code = $code : $message\n";
    return 0; 
  }
  return 1;
}

# recherche des OU de l'arborescence
# remplit le tableau @TabOU
# param 0 : pointeur sur handle de connexion LDAP
# param 1 : OU de recherche
sub GetOU
{
  my($Entry, $mesg, $connLDAP, $ind, $uneOU, $OU);
  $connLDAP = shift;
  $OU = shift;
  $mesg = $$connLDAP->search(base => $OU, scope => "sub", filter => "(objectclass=OrganizationalUnit)");
  return 0 if ($mesg->is_error);
  return 0 if ($mesg->count == 0);
  $ind = 0;
  foreach $Entry ($mesg->all_entries)
  {
    $uneOU = $Entry->dn();
    $uneOU =~ s/ //sg;
    $TabOU[$ind] = $uneOU;
    $ind++;
  }
  return $ind;
}


#                 recup des DN de l'OU passee en parametre
# param 0 : pointeur sur Handle de connexion LDAP
# param 1 : OU de recherche
# param 2 : pointeur sur tableau associatif contenant les DN
#  retourne le nombre d'entree, ou le code erreur en negatif  si erreur
sub RecupLDAP
{
  my($Entry, $DN, $connLDAP, $TabDN, $ind, $OU, $Serv, $Pass, $Err, $mesg);

  $connLDAP = shift;
  $OU = shift;
  $TabDN = shift;
  $ErrLDAP = "";
  $mesg = $$connLDAP->search(base => $OU, scope => "one", filter => "(!(objectclass=OrganizationalUnit))",
                             attrs => ['userpassword'] );
  if ($mesg->is_error)
  {
      $Err = $mesg->code;
      $ErrLDAP = $mesg->error;
      return - $Err;
  }
  return 0 if ($mesg->count == 0);  
  foreach $Entry ($mesg->all_entries)
  {
    $DN = $Entry->dn();
    $DN =~ s/ //sg;
    $DN =~ s/,$OU//i;
    $Pass = $Entry->get_value('userpassword');
    $Pass = "azerty" if ($Pass eq "");
    $$TabDN{$DN} = $Pass;
  }
  return $mesg->count;
}

# recuperation du DN et Password dans un fichier
# param 0 : nom du fichier
# param 1 : DN admin
# param 2 : PASS admin
sub RecupAdmin
{
  my ($Ligne);
  if (! open(FICPASS, "<$_[0]"))
  {
    print FICERR "Impossible d'ouvrir $_[0]. connexion anonyme\n";
    return 0;
  }
  $Ligne = <FICPASS>;
  close FICPASS;
  chomp($Ligne);
  ($_[1], $_[2]) = split(";", $Ligne);
  if (($_[1] eq "") || ($_[2] eq ""))
  {
    $_[1] = ""; $_[2] = "";
    return 0;
  }
  return 1;
}

# ----------------------------------------------------------------------------
# --            Fonction GetDate                                            --
# retourne la date du jour en format JJ/MM/AAAA                              -
# ----------------------------------------------------------------------------
sub GetDate
{
    my ($dateE, $an);
    my ($sec, $min, $heure, $mjour, $mois, $annee, $sjour, $ajour, $isdst) = localtime(time);
    $mois++;
    $an = 1900 + $annee;
    $mois = "0" . $mois if (length($mois) == 1);
    $mjour = "0" . $mjour if (length($mjour) == 1);
    $heure = "0" . $heure if (length($heure) == 1);
    $min = "0" . $min if (length($min) == 1);
    $dateE = "$mjour/$mois/$an - $heure:$min";
    return $dateE;
}

# ----------------------------------------------------------------------------
# --            Fonction printEntry                                         --
# --  sert au debugging                                                     -- 
# --  imprime l'entree dont la reference est passee en parametre            --
# --  usage :   &printEntry(\$Entry);                                       --
# ----------------------------------------------------------------------------
sub printEntry
{
    my $Entry = shift;
    print "DN: ",$$Entry->dn,"\n";
    foreach my $attr ( sort $$Entry->attributes )
    {
        next if ( $attr =~ /;binary$/ );
        my $refTab = $$Entry->get_value($attr, asref => 1);
        foreach my $item (@$refTab)
        {
           print "  $attr : $item\n";
        }
    }
}

# ----------------------------------------------------------------------------
# --            Fonction ErrSyntaxe                                         --
# ----------------------------------------------------------------------------
sub ErrSyntaxe
{
    my $mess = shift;
    print "Erreur de syntaxe $mess\n\n";
    print "3 parametres obligatoires :\n";
    print "  . -master : le serveur LDAP maitre. si port <> 389, preciser celui-ci en fin de chaine. separateur = ':'\n";
    print "  . -slave : le serveur esclave. meme syntaxe\n";
    print "  . -basedn : le 'base DN' de l'operation\n";
    print "et 3 parametres facultatifs\n";
    print "  . -option. Peut prendre 3 valeurs :\n";
    print "       . anonymous. Option implicite. bind anonyme dans les 2 serveurs. Juste controle d'existence des entrees\n";
    print "       . only_pass. Controle et reparation des entrees inexistentes, ou mots de passe differents\n";
    print "       . full. Controle et reparation de la totalite\n";
    print "  . -bindmaster. nom d'un fichier qui contient le DN et mot de passe de bind dans le maitre\n";
    print "  . -bindslave. nom d'un fichier qui contient le DN et mot de passe de bind dans l'esclave\n";
    print "les 2 dernieres options sont necessaires si le parametre option est different d'anonymous\n\n";
    print "Un exemple :\n";
    print "SyncReplica.pl -master ldap.univ.fr:390 -slave ldap2.univ-nancy2.fr:392 -basedn dc=univ,dc=fr -bindmaster ./fic1 -bindslave ./fic2 -option full\n\n";
    exit 1;
}


# ----------------------------------------------------------------------------
# --            Fonction ArreTimeout                                        --
# --  cette fonction est appelee si le delai de timeout est depasse         -- 
# ----------------------------------------------------------------------------
sub ArretTimeout
{
    my ($LaDate);
    $LaDate = GetDate();
    print FICERR "*********************************************************************\n";
    print FICERR "*   arret anormal de la procedure sur timeout                       *\n";
    print FICERR "*   arret a $LaDate                                     *\n";
    print FICERR "*   $nbTraite entrees parcourues au total                           *\n";
    print FICERR "*********************************************************************\n\n";
    exit 1;
}
