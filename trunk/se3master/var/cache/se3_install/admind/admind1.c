/*
admind projet SE3
Daemon d'administration Serveur SE3

« wawaChief »       olivier.lecluse@crdp.ac-caen.fr
ErotoKriTOS <(-_°)> jean-luc.chretien@tice.ac-caen.fr

Equipe Tice académie de Caen

V 0.1 maj : 17/11/2002
Distribué selon les termes de la licence GPL
compilation du binaire : gcc -o admind admind1.c
*/

#define _GNU_SOURCE
#include <stdio.h>
#include <unistd.h>
#include <fcntl.h>
#include <limits.h>
#include <syslog.h>
#define OPEN_MAX         256   /* # open files a process may have */

main() {

  int fd;
  int i;

  char str[12];
  char ligne [128];

  FILE *result;
  FILE *tbp;

  openlog("admind_SE3", LOG_PERROR | LOG_PID, LOG_INFO);
  umask (022);
  chdir ("/tmp");

  // Le daemon passe en arrière plan
  if (fork() !=0) exit (0);
  // Creation d'une session
  setsid();

  // Creation d'un fichier lock contenant le n° pid
  fd = open ("/var/run/admind.pid", O_RDWR | O_CREAT,0640);
  if (fd < 0) exit (0);
  if (lockf (fd, F_TLOCK, 0) < 0 ) exit(0);
  snprintf (str, 12, "%d\n", getpid ());
  write (fd, str, strlen (str));

  // Fermeture des descripteurs de fichiers
  // Plus d'affichage des  messages stdout, stderr
  for (i=0; i<OPEN_MAX; i++) close (i);

  while(1) {
    // DEBUG
    // printf ("admind in work ;-)\n");
    tbp = fopen("/home/remote_adm/admin.sh","r");
    if (tbp != NULL) {
      fclose(tbp);
      // DEBUG
      // printf ("presence admin.sh ;-)\n");

      // Analyse syntaxique du script admin.sh
      // A FAIRE !!

      // Execution de la tache d'administration
      result = popen ("/home/remote_adm/admin.sh","r");
        while (fgets(ligne, 127, result)!=NULL) {
          // Redirection des sorties admin.sh vers syslog
          syslog (LOG_INFO, ligne,"\n");
        }
      pclose(result);

      // Effacement du fichier admin.sh
      remove ("/home/remote_adm/admin.sh");
      // Ecriture d'un message syslog  de Fin d'execution
      syslog (LOG_INFO, "Fin execution admin.sh");
    }
    sleep(1);
  }
  closelog ();
}