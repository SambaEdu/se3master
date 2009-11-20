/*
admind projet SE3
Daemon d'administration Serveur SE3

« wawaChief »       olivier.lecluse@crdp.ac-caen.fr
ErotoKriTOS <(-_°)> jean-luc.chretien@tice.ac-caen.fr

Equipe Tice académie de Caen

V 0.1 maj : 09/12/2002
Distribué selon les termes de la licence GPL
compilation du binaire : gcc -o admind admind2.c
*/

#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <fcntl.h>
#include <limits.h>
#include <syslog.h>
#include <sys/file.h>

#define OPEN_MAX         256   /* # open files a process may have */
#define PIDFILE		"/var/run/admind.pid"
#define ADMIND               "/var/remote_adm/admin.sh"
main() {

  int fd, i;

  static char ligne [128], str[12];

  FILE *result;
  FILE *tbp;

  openlog("admind_SE3", LOG_PERROR | LOG_PID, LOG_INFO);
  umask (022);
  chdir ("/tmp");

  // Le daemon passe en arrière plan
  if (fork() !=0) exit (EXIT_SUCCESS);
  // Creation d'une session
  setsid();

  // Fermeture des descripteurs de fichiers
  // Plus d'affichage des  messages stdout, stderr
  for (i=0; i<OPEN_MAX; i++) close (i);

  // Creation d'un fichier lock contenant le n° pid
  // et empechant le lancement de plusieurs admind
  fd = open (PIDFILE, O_RDWR | O_CREAT,0640);
  if (fd < 0) exit (EXIT_FAILURE); // sortie car impossibilite de creer le fichier PIDFILE
  if (lockf (fd, F_TLOCK, 0) < 0 ) {
    syslog (LOG_INFO, "SE3 admind is running !!!\n");
    exit(EXIT_SUCCESS);  // sortie car le fichier est deja locke par un autre process
  }
  snprintf (str, 12, "%d\n", getpid ());
  write (fd, str, strlen (str));

  // Message syslog d'information de demarrage du daemon
  syslog (LOG_INFO, "Starting SE3 admin daemon...\n");

  while(1) {
    tbp = fopen(ADMIND,"r");
    if (tbp != NULL) {
      fclose(tbp);
      // Analyse syntaxique du script admin.sh
      // A FAIRE !!

      // Execution de la tache d'administration
      result = popen (ADMIND,"r");
        while (fgets(ligne, 127, result)!=NULL) {
          // Redirection des sorties admin.sh vers syslog
          syslog (LOG_INFO, ligne,"\n");
        }
      pclose(result);

      // Effacement du fichier admin.sh
      remove (ADMIND);
      // Ecriture d'un message syslog  de Fin d'execution
      syslog (LOG_INFO, "Fin execution admin.sh");
    }
    sleep(1);
  }
}