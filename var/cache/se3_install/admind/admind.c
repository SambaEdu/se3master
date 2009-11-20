/*
admind projet SE3

Equipe Tice académie de Caen
V 0.1 maj : 15/11/2002
Distribué selon les termes de la licence GPL

*/

#include <stdio.h>
#include <unistd.h>
#include <syslog.h>


main()
{
  int fd;
  char str[12];
  FILE *tbp;
  int childpid;

  /* openlog("admind_SE3", LOG_PERROR | LOG_PID, LOG_USER); */
  umask (022);
  chdir ("/tmp");

  /* Lock File du daemon
  fd = open ("/tmp/admind.lock", 0640);
  if (fd < 0) exit (0);
  if (lockf (fd, F_TLOCK, 0) < 0 ) {
    snprintf (str, 12, "%d\n", getpid ());
    write (fd, str, strlen (str));
  }
  */

  while(1) {
    tbp = fopen("/home/remote_adm/admin.sh","r");
    if (tbp != NULL) {
      fclose(tbp);
      if((childpid = fork()) < 0) system("/usr/bin/logger -t admind fork error");
      else if(childpid == 0) {
           system ("/home/remote_adm/admin.sh");
           system ("/bin/rm /home/remote_adm/admin.sh");
           /*syslog (LOG_INFO, "execution admin.sh");*/
           exit(0);
      }
      while (childpid !=wait(0));
    }
    sleep(1);
  }
}
