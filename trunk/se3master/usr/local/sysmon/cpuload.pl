#!/usr/bin/perl
use POSIX;

#========================================================
# CPU Usage script for mrtg
#
#    File:      cpuload.pl
#    Author:    Chretien Jean-Luc | jeanluc.chretien@tice.ac-caen.fr
#    Version:   1.0
#
#    Date:      09/08/02
#    Purpose:   This script reports CPU usage for user
#               and system to mrtg, along with uptime
#               and the machine's hostname.
#
#    Usage:     ./cpuload.pl
#
#
#    Info:      Designed on Debian Woody
#
#
#    [Note to User]
#
#               If anyone has comments or suggestions, email me at
#               jean-luc.chretien@tice.ac-caen.fr and I'll try to
#               get back to you :)
#
#    Modified by Bruno Bzeznik 2002/12/18:
#    I prefer to use output from uptime, the second entry gives
#    the average load of last 5 minutes, and it's more representative
#    as we run mrtg every 5 minutes.
#   -------------------------------------------------------------
#
#    Sample cfg:
#
#  Target[machine]: `./cpuload.pl`
#  MaxBytes[machine]: 1000
#  YTicsFactor[agios]: 0.1
#  Options[machine]: gauge, nopercent
#  Unscaled[machine]: dwym
#  YLegend[machine]: % of CPU used
#  ShortLegend[machine]: %
#  LegendO[machine]: &nbsp;CPU System:
#  LegendI[machine]: &nbsp;CPU User:
#  Title[machine]: Machine name
#  PageTop[machine]: <H1>CPU usage for Machine name
#   </H1>
#   <TABLE>
#     <TR><TD>System:</TD><TD>Machine name</TD></TR>
#    </TABLE>
#

   # Run commands
   $getuptime = `/usr/bin/uptime`;

# Commented by Bruno Bzeznik 2002-12-18:
#   $getcpu = `/usr/bin/top -b -n 1 | grep "CPU"`;
#   @line = split /^/m,$getcpu;
#   @getload = split /\s+/,$line[0];
#   $getload[2]=~ s/\%//;
#   $getload[4]=~ s/\%//;
#   $cpuuser = floor($getload[2]+0.5);
#   $cpusys = floor($getload[4]+0.5);

# Edited by Bruno Bzeznik 2002-12-18:

   @line = split (/load average: /,$getuptime);
   @getload = split (/\s+/,$line[1]);
   $cpuuser = $getload[1]*100;
   $cpusys = $cpuuser;

   # Print cpu data for mrtg
   ### DEBUG ###
   #print "user:".$cpuuser."reel:".$getload[2]."\n";
   #print "system:".$cpusys."reel:".$getload[4]."\n";
   ### FIN DEBUG ###
   print $cpuuser."\n";
   print $cpusys."\n";

   # Parse though getuptime and get data
   $getuptime =~ /^\s+(\d{1,2}:\d{2}..)\s+up\s+(\d+)\s+(\w+),/;

   # Print getuptime data for mrtg
   print $2." ".$3."\n";

   # Print machine name for mrtg
   #print $machine."\n";
   exit (0);
