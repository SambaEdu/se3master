#!/usr/bin/perl -w

# parti.pl: automatic Partitionning tool 
# Version 1.1

#This script was developped by Pablo Cardoso

#    This program is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; either version 2 of the License, or
#    (at your option) any later version.

#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#    You should have received a copy of the GNU General Public License
#    along with this program (For example ./COPYING or ../COPYING);
#    if not, write to the Free Software Foundation, Inc., 675 Mass Ave,
#    Cambridge, MA 02139, USA.

# Please send all comments and bug reports by electronic mail to:
#   Pablo Cardoso <cardoso_pablo@hotmail.com>

#------------------------------------------------------------------------------------------------------------
 #                             SYNTAX OF THE CONFIGURATION FILE
 # parameters to be specified:
 #   - mountPoint </|/boot|/home|...>
 #   - fstype <swap|ext2|ext3|xfs|fat32|fat16>
 #   - size <taille>[K|M|G|%]		         # if % no growto allowed on the same device
 #
 # optionnal parameters:
 #   - growto <taille>[K|M|G]		         
 #   - deviceUsed <hda|hdb|sda...>      # if used, must be specified for all partitions
 #
 # For the swap, the mountPoint may be      : - none if one swap partition
 #                                            - none1, node2, ... if several
#------------------------------------------------------------------------------------------------------------
  


     
use strict;

#------------------------------------Modules-------------------------------------------
use Tie::IxHash;				#used to parse the hash table according to the order of insertion											
use Class::Struct;
use Math::BigInt;       #allow the use of big integers											
use Math::BigFloat;
use POSIX;
use Getopt::Std;

#*********************************Management of the options*********************************
my %opts=();
my $inputFile;
my $mountDeviceFile;
my $fstab;




getopts("i:f:t:h:", \%opts) or printusage();

$inputFile=$opts{"i"} if exists($opts{"i"});
$inputFile="part.data" if !exists($opts{"i"});
$mountDeviceFile=$opts{"f"} if exists($opts{"f"});
$fstab=$opts{"t"} if exists($opts{"t"});
$fstab="fstab" if !exists($opts{"t"});

#*********************************printing function*********************************
my $debug=1;
my $debugTest=1;
my $debugRes=1;
my $linux=1;
my $debugLevel=1;
our %part=();                           
tie %part, "Tie::IxHash";

require $inputFile  or die "\nfatal: cannot find the configuration file : part.data";

if ($debug){
  open DEBUG_FD, ">debug" or die "fatal: cannot create debug file";
}
if ($debugTest){
	open DEBUG_FD, ">debug" or die "fatal: cannot create debug file";
}
if ($debugRes){
	open DEBUG_FD, ">debug" or die "fatal: cannot create debug file";
}

$debug=0;
$debugTest=0;
$debugRes=0;

sub printusage  {
  print "-f <filename> with this option, the script generates a file containing the mountPoints associated to the devices\n";
  print "-i <filename> use this option to specify a configuration file other than the default one part.data\n";
  print "-h help\n";
  print "-t use this option to give a destination to the fstab generated\n";
}

sub fdebug
{
 
 my $output=shift;
 
 if ($debugLevel == 1)  {
 	if($debug){print DEBUG_FD "$output\n"};
 }
 if ($debugLevel == 2)  {
 	$debug=$debugTest;
 	if($debug){print DEBUG_FD "$output\n"};
 }
 if ($debugLevel == 3)  {
 	$debug=$debugRes;
 	if($debug){print DEBUG_FD "$output\n"}; 	
 }
 if ($debugLevel == 0)  {
 	$debug=0;
 	if($debug){print DEBUG_FD "$output\n"}; 	
 }
}
sub fatalExit
{
 my $message=shift;
 fdebug($message);
 print "$message\n";
 exit(EXIT_FAILURE);
}
sub YesNo
{
	my $messageBefore=shift;
	my $messageAfter=shift;
	my $yes_no;
	
	print $messageBefore;	
	
	$yes_no=<STDIN>;
	chomp $yes_no;
	
	if ($yes_no eq "no")  {
	    fatalExit("\no_____o\n");
  }
	if ($yes_no eq "yes")  {
	    print $messageAfter;
	    last;
  }
}


#---------------------------------------------------------------------------
#                     Declaration of the global variables                  #
#---------------------------------------------------------------------------

my %hashobj=();            #this table will allow to have access to all the structures which were created												  
my @listHd=();                #list of the keys corresponding to the structures of the devices(hda,hdb...)
my @listPart=();              #idem with the partitions, but the keys are the mount points


#---------------------------------------------------------------------------
#                                 STRUCTURES                               #
#---------------------------------------------------------------------------

#-----Declaration of the structures which will contain information of the disks and partitions
struct Disk =>
{
	 device => '$',          #hda, hdb ...
	 sizeDisk => '$',        #disk size	
	 freeSpace => '$',       #remaining size on this
	 usedSpace => '$',       #space used
	 growSpace => '$',       #space used by the "grow"
	 nbHD => '$',						 #number of disks	
	 sectors => '$',				 #number of sectors per track
	 heads => '$',					 #number of heads
	 cylinders => '$',			 #number of cylinders
};
struct Part =>
{
   mountPoint => '$',      		# /home, /var...
   fsType => '$',          		# fat32,swap...
   sizeMin => '$',         		# size minimum
   sizeGrow => '$',        		# size maximum
   coeff => '$',           		# grow coefficcient
   sizePart => '$',        		# partition size
   partNb => '$',          		# partition number
   deviceNb => '$',        		# device number
   partId => '$',          		# partition Id
   deviceUsed => '$',      		# device used by the partition
   partDone => '$',				 		# indicator of a valid partition
   partsDone => '$',			 		# indicator of a valid partitionning
   sumSizeMin => '$',					# sum of the sizes min
   sumSizeMinNotDone => '$',	# sum of the sizes min not done 
   method => '$',							# method: auto or manual
   start => '$',
};																									

#---------------------------------------------------------------------------
#                                 FUNCTIONS                                #
#---------------------------------------------------------------------------

#*********************************sumFullSize : calculation of the space usable*********************************
#input: none
#returns the sum of the disks sizes
#called by calcPartOnFullSize
sub sumFullSize                                                        
{                                                        
	my $eltList;                                                        
	my $fullSize;
                                                                          
	foreach $eltList (@listHd) {                       
  	$fullSize+=$hashobj{$eltList}->sizeDisk;         #sum of the disks sizes  
  }  																								 
  return $fullSize;
}
sub SizeCurrentDisk                                                        
{                 
        my $disk=shift;
	my $eltList;                                                        
	my $fullSize;
                                                                          
	                       
  	$fullSize=$hashobj{$disk}->sizeDisk;         #sum of the disks sizes  
  																							 
  return $fullSize;
}

#*********************************percentSize :  for the management of size in %*********************************
#input: size in percent of the partition
#returns the size in bytes of the partition
#called by convBytes

#CAUTION : use the size in percent if there is one disk avaible
sub percentSize
{
	my $bytes = shift;
	my $onlyPercent=shift;
        my $disk=shift;
	my $sizePart=0;
	my $fullSize;
        my $sumSizeMin=0;
	my $part;
        my $unit=0;

        
        
	if ($onlyPercent == 1)  {
		#$fullSize = sumFullSize();
	        #SizeCurrentDisk($listHd[0]);
	        if ($disk eq "none")  {
		    $fullSize = SizeCurrentDisk($listHd[0]);
		}
		else  {
		    $fullSize = SizeCurrentDisk($disk);
		}
	}
	else {
		foreach $part (keys %part)  {
			if ($part{$part}{'size'}=~m/^(\d+)(M|K|G|%)?/) {
			     if ($2) { 
				 $unit= ($2); 
			     }	
			       if ($unit ne '%')  {  
				  if ($disk ne "none") {
				    if ($part{$part}{'deviceUsed'} eq $disk)  {  
				      $sumSizeMin+=convBytes($part{$part}{'size'}, "none");				
			            }
				  }
				  else  {
				      $sumSizeMin+=convBytes($part{$part}{'size'}, "none");
				  }
				}
				    
			}
		}  
		#$fullSize = sumFullSize() - $sumSizeMin;
	        if ($disk eq "none")  {
		    $fullSize = SizeCurrentDisk($listHd[0]) - $sumSizeMin;
		}
		else  {
		    $fullSize = SizeCurrentDisk($disk) - $sumSizeMin;
		}
	        
	    }
        
	$bytes = ($fullSize*($bytes/100));
       
	return $bytes;    
}

#*********************************convBytes : conversion in octets*********************************
#input: size specified in the configuration file
#returns this size in bytes
#called by calcPartOnFullSize
sub convBytes
{
	$debugRes=0;
	$debugTest=0;
	$debug=0;
  fdebug("--------------------Conversion in octets------------------\n");
  my $size = shift;
  my $disk=shift;
  my $bytes=0;
  my $unit=0;
  my $mult=0;
  my $onlyPercent=1;
  my $existSizeMin=0;
  my $part;
  my $line;

  if ($size =~m/^(\d+)(M|K|G|%)?/) {
	  $bytes=$1;
	  $unit=($2) ? $2 : "";
	  fdebug("\nsize mini: $bytes\n");
	  fdebug("unit: $unit\n");
	  #Giga bytes
	  if ($unit eq 'G') {
	  	$mult=1000000000;
	  }
	  #Mega bytes
	  elsif ($unit eq 'M') {
	    $mult=1000000;
	  }
	  #Kilo bytes
	  elsif ($unit eq 'K') {
	    $mult=1000;
	  }
	  #size in percent of the disk
	  elsif ($unit eq '%') {
	  	$mult=1;
	  	    
	  	foreach $part (keys %part)  {
			  $line=$part{$part}{'size'};
			  
			  if ($line=~m/^(\d+)(M|K|G|%)?/) {
			    if ($2)  { 
				$unit=($2);
			    }
		      if ($unit ne '%')  {
		        $onlyPercent=0;
		      }
			  }	  
		  } 
	      	      	     
	    $bytes=percentSize($bytes, $onlyPercent, $disk);
	  }
	  #bytes
	  else {
	    $mult=1;
	  }
	  if ($bytes <= 0)  {
	  	fatalExit("Error while converting the sizes, size null or negative");
	  }
  }
  $debugRes=1;
	$debugTest=1;
	$debug=1;
#renvoyer -1
  $bytes = $bytes * $mult;
  return $bytes;
}

#*********************************detectDrives : disk detection*********************************
#input: none
#stores the result from the detection into the disk structure
#called in the main
sub detectDrives
{
  my $countDevice=1;              #comptage des disques  | disks counting
  my @listedevice=();                
  my $device;
  my $size;
  my $line;
	$debugRes=0;
	$debugTest=0;
	$debug=0;
	#recuperation of the list of drives and their geometry
	@listedevice=`sfdisk -g`;       
#
#ERROR  #1 : no drive detected
#    
	if (! @listedevice)  {
		fatalExit("\nFATAL ERROR : No hard drive detected\n");
	}	
	#here all information of detection are captured
	
	#Use of regular expressions extract useful information concerning the disks
	
	#then valorizations of the parameters from the structure Disk
	
	#for each device, sfdisk - S returns the size in blocks of 1024 bytes
	foreach $line (@listedevice) {
	  fdebug ("\n$line");
	  chomp($line);
	  if ($line=~m"^/dev/([a-z]+): (\d+) cylinders, (\d+) heads, (\d+) sectors/track") {
	    fdebug ("\ndevice trouve: $1\n");
	    fdebug ("heads: $3\n");
	    fdebug ("sectors: $4\n");
	    fdebug ("cylinders: $2\n");
	    $device=$1;
	    $size=`sfdisk /dev/$device -s`;
	    $size=$size*1024;
			
	    my $a = Disk->new();                   #creation of a structure disk
	    
	    #valorization of the fields of this struct
	    $a->device($1);                 #device
	    $a->sectors($4);								#sectors
	    $a->heads($3);									#heads
	    $a->usedSpace(0);								#space used initialized to 0	
	    $a->growSpace(0);								#grow space initialized to 0
	    $a->cylinders($2);							#cylinders
	    $a->sizeDisk($size);						#disk size
	    $a->freeSpace($size); 					#free space
	    $countDevice++;			            #incrementation of the disks counter
	    $hashobj{$1}=$a;
	    
	    #storage of a struct pointer in a hash table used for the parsing of the disks structures created        
	    push @listHd, $1;               #the key is the device
	  }
	  
	  if ($line=~m"^/dev/(cciss/c\dd\d): (\d+) cylinders, (\d+) heads, (\d+) sectors/track") {
	    fdebug ("\ndevice trouve: $1\n");
	    fdebug ("heads: $3\n");
	    fdebug ("sectors: $4\n");
	    fdebug ("cylinders: $2\n");
	    $device=$1;
	    $size=`sfdisk /dev/$device -s`;
	    $size=$size*1024;
			
	    my $a = Disk->new();                   #creation of a structure disk
	    
	    #valorization of the fields of this struct
	    $a->device($1);                 #device
	    $a->sectors($4);								#sectors
	    $a->heads($3);									#heads
	    $a->usedSpace(0);								#space used initialized to 0	
	    $a->growSpace(0);								#grow space initialized to 0
	    $a->cylinders($2);							#cylinders
	    $a->sizeDisk($size);						#disk size
	    $a->freeSpace($size); 					#free space
	    $countDevice++;			            #incrementation of the disks counter
	    $hashobj{$1}=$a;
	    
	    #storage of a struct pointer in a hash table used for the parsing of the disks structures created        
	    push @listHd, $1;               #the key is the device
	  }
	}
	$debugRes=1;
	$debugTest=1;
	$debug=1;
}


#*********************************pushParts : reading of the partition in part.data*********************************
#input: configuration file
#stores the partitions information into the partition structure
#called in the main
sub pushParts
{
	
  my $confFile=shift;
  
  #creation of a special hash table having several values by key
 
  #------------------------------------temporary variables------------------------------------------------------
  my $partType="none";                    
  my $deviceUsed="none";
  my $method="auto";
  my $sizeGrow=0;
  my $countPart=1;
  my $p;                                  #pt de montage | mount point
  $debugRes=0;
	$debugTest=0;
	$debug=0;

  #Parsing of the hash table of the configuration file and valorization of the datas of the partition structure p
  foreach $p (keys %part) {

		#obligatory parameters
	  fdebug ("mount point: $p\n");
	  fdebug ("  fstype: $part{$p}{'fstype'}\n");
	  fdebug ("  size: $part{$p}{'size'}\n");
	  
	  #optional parameters
	  fdebug ("  growto: $part{$p}{'growto'}\n") if exists($part{$p}{'growto'});
	  fdebug ("  parttype : $part{$p}{'parttype'}\n") if exists($part{$p}{'parttype'});
		
		
		#list of the keys for the access to the partitions structures
	  push @listPart, $p;                              
	  if (exists($part{$p}{'growto'})) {
	  	$sizeGrow=$part{$p}{'growto'};
	  	if ($sizeGrow eq 'all')  {
	  		$sizeGrow=1;
	  	}             
	  }
          
	  #if there is no grow, its value will be -1
	  else {
	  	$sizeGrow=-1;                              
	  }
	  $partType=$part{$p}{'parttype'} if exists($part{$p}{'parttype'});
	  $deviceUsed=$part{$p}{'deviceUsed'} if exists($part{$p}{'deviceUsed'});
	  if ($deviceUsed ne "none")  {
			$method = "manual";
	  }
	  
	
	  my $a = Part->new();                             #creation of the partition structure
	  $a->mountPoint($1);                              # | 
	  $a->fsType($part{$p}{'fstype'});                 # |
	  $a->sizeMin($part{$p}{'size'});                  # |
	  $a->sizeGrow($sizeGrow);                         # |
	  $a->mountPoint($p);                              # |
	  $a->deviceNb(0);                                 # |
	  $a->partDone(0);                                 # |
	  $a->partsDone(0);                                # |
	  $a->sumSizeMinNotDone(0);                        # |
	  $a->sumSizeMin(0);                               # |
	  $a->deviceUsed($deviceUsed);         						 # |
	  $a->method($method);								 						 # |
	  $countPart++;                                    #partition counter
	  
	  #storage of a struct pointer in a hash table intended to parse the partitions structures created
	  $hashobj{$p}=$a;
	}
	$debugRes=1;
	$debugTest=1;
	$debug=1;
}

#*********************************sumSizeMinNotDone*********************************
#RESERVED FOR FUTURE USE
sub sumSizeMinNotDone
{
	my $eltList;
	my $sumSizeMinNotDone=0;
  foreach $eltList (@listPart) {
	  if ($hashobj{$eltList}->partDone==0 && ($hashobj{$eltList}->sizeGrow == 0 | $hashobj{$eltList}->sizeGrow == 1)) {
	  	$sumSizeMinNotDone+=$hashobj{$eltList}->sizeMin;
	  }
  }
  foreach $eltList (@listPart) {
	  $hashobj{$eltList}->sumSizeMinNotDone($sumSizeMinNotDone);
  }
}

#*********************************sumSizeMin*********************************
#input: device used by these partitions
#returns the sum of the sizes mini on a specified device
#called by calcPartOnFullSize
sub sumSizeMin
{
	my $eltList;
	my $device=shift;
        
	my $sumSizeMin=0;
	my $method=$hashobj{$listPart[0]}->method;
	foreach $eltList (@listPart) {
	  
	  if ($method eq "auto")  {  	
	  	$sumSizeMin+=$hashobj{$eltList}->sizeMin;
	  }
          else  {
	
	  	if ($hashobj{$eltList}->deviceUsed eq $device)  {
		        
	  		$sumSizeMin+=$hashobj{$eltList}->sizeMin;
		      
	  	}
	  	else  {
	  		#$device=$hashobj{$eltList}->deviceUsed;
		        next;
	  	}
		}
	}	
       
    
    
	return $sumSizeMin;
}

#*********************************calcPartOnFullSize : calcul des partitions sur la taille totale*********************************
#*********************************calcPartOnFullSize : calculation of the partition on the whole available space*********************************
#input: none
#calculates the partitions sizes and store the sizes in the partitions structure
#called in the main
sub calcPartOnFullSize
{
	$debugRes=0;
	$debugTest=0;
	$debug=0;
	#Calculation of the partition size:
	#if the grow = 0
	#PartSize = SizeMin
	#if the grow in not = 0
	#PartSize = (GrowCoeff * RemainingSize) + SizeMin
	#if the grow is = all
	#PartSize = SizeMin + RemainingSize

  fdebug("\n-----------------------\n");
  fdebug("|CALCPARTONFULLSIZE|\n");
  fdebug("-----------------------\n");

  my $FullSize=0;           #whole size (sum of the disks sizes)
  my $HDSize=0;             #current disk size
  my $SizeGrow=-1;          #grow size
  my $SizeMin=0;            #size min
  my $SumSizeMin=0;         #sum of the sizes mini
  my $RemainingSize=0;      #remaining size
  my $PartSize=0;           #partition size
  my $nbHD=0;               #number of disks
  my $c=0;                  #coefficient resulting from the grow value
  my $SumPartSizeHD=0;      #sum of the partitions on the current device
  my $nbPart=0;             #partition number
  my $t=0;                  #
  my $temp=0;               #
  my $SumPartSize=0;        #sum of the partition sizes
  my $i=0;                  #
  my $eltList;              #						
  my $method='auto';				#method auto or manual
  my $deviceUsed='none';		#device used for the partition

 
  #------------------------------CALCULATION ON FULL SIZE----------------------------------------------------------
  
  fdebug("\n\nPRELIMINARY CALCULATIONS\n\n");
  fdebug("   1-calculation of the sizes min");
	
  foreach $eltList (@listPart) {
  	
		fdebug("\n\n  SIZE READ IN THE STRUCTURE: ");
		fdebug($hashobj{$eltList}->sizeMin);
		
		#conversion in octets	
		$SizeMin=convBytes($hashobj{$eltList}->sizeMin, $hashobj{$eltList}->deviceUsed); 
		$hashobj{$eltList}->sizeMin($SizeMin);
		
		#modification of the value in the structure             
		$hashobj{$eltList}->sizeMin($SizeMin);     
		$SizeGrow= convBytes($hashobj{$eltList}->sizeGrow, "none") ;          
		
		#calculation of the growth coefficient
		if ($SizeGrow>0) {
	    
	    #if the grow takes all the remaining size, coeff=1
	    if ($SizeGrow == 1) {
	    	$c=1;
	    }
	    
	    #case of a given grow not=0
	    else {
	    	$c=$SizeMin/$SizeGrow;
	    }
		}
		
		#if there is no grow, its coeff is =0
		else {
	    $SizeGrow=0;
	    $c=0;
		}
		
		$hashobj{$eltList}->sizeGrow($SizeGrow);  #modification of the value in the struct
		$hashobj{$eltList}->coeff($c);            #valorization of the coeff
		
		#incrementation of the size min to calculate the sum of the sizes mini
		$SumSizeMin+=$SizeMin;
		$t++;
		fdebug("\nSum of the sizes mini at the time $t : $SumSizeMin\n");
		
		$method=$hashobj{$eltList}->method;
  }
  
  #method automatic (default)
  if ($method eq 'auto')  {																		
	  $i=0;
	  foreach $eltList (@listHd) {
	    $i++;
	    $FullSize+=$hashobj{$eltList}->sizeDisk;         #full size
	    $nbHD=$i;                                        #nb disks
	    $hashobj{$eltList}->nbHD($nbHD);
	    fdebug("disk size $eltList : ");
	    fdebug($hashobj{$eltList}->sizeDisk);
	  }
#
#ERROR #2 
#
		if ($SumSizeMin > $FullSize)  {
			fatalExit("\nFATAL ERROR : Not enough space for the partitionning\nyou need at least $SumSizeMin bytes\n");
		}
	 
	  #calculation of the remaining size to be distributed
	  $RemainingSize= $FullSize-$SumSizeMin;                 
	  fdebug ("\n\nSum of the $nbHD disks : $FullSize\n");
	  fdebug ("Sum of the sizes mini : $SumSizeMin\n");
	  fdebug ("remaining size before the partitions calculation: $RemainingSize\n\n");
	  
    #----------------------preliminary calculation of the partitions sizes---------------------------------------			
	
	  foreach $eltList (@listPart) {
	    fdebug ("\nBeginning of the loop ------> sizegrow : ");
	    fdebug ($hashobj{$eltList}->sizeGrow);
	    fdebug ("\n                      ------> coeffgrow : ");
	    fdebug ($hashobj{$eltList}->coeff);
	    fdebug ("\n                      ------> taille min : ");
	        
	  #-------------------------calculation of the partition size in function of the grow-------------------------
	        
      #if the grow is 0 or 1, a special treatment is necessary
      if ($hashobj{$eltList}->sizeGrow == 0 | $hashobj{$eltList}->sizeGrow == 1) {
	      
	      #if grow = 0 then, the partition size is the size mini
	      if ($hashobj{$eltList}->sizeGrow == 0 ) {
	        $temp = 0;
	        $PartSize=$temp + $hashobj{$eltList}->sizeMin;
	      }
	      
	      #if the grow = 1, the grow takes all the remaining free space
	      else {
	        $temp = $RemainingSize*$hashobj{$eltList}->coeff;
	        $PartSize = $temp + $hashobj{$eltList}->sizeMin;
	      }
      }
      
      #for the other case we do that : 
      else {
	      $temp = $RemainingSize*$hashobj{$eltList}->coeff;
	      $PartSize=$temp + $hashobj{$eltList}->sizeMin;

      	if ($PartSize > $hashobj{$eltList}->sizeGrow) {
        	$PartSize=$hashobj{$eltList}->sizeGrow;
        }
      }
      
      #valorization in the structure
      $hashobj{$eltList}->sizePart($PartSize);                
      fdebug ("\n\nPARTITION SIZE : $PartSize \n");
      if ($hashobj{$eltList}->sizeGrow==0) {
      	fdebug ("\nsize mini, it is not necessary to decrement the remaining size\n\n");
      }
      else {
	      if ($RemainingSize<=0) {
	        fdebug ("\nall the space was taken by the grows\n");
	        $RemainingSize=0;
	      }
	      else {
	        $RemainingSize-=($PartSize-$hashobj{$eltList}->sizeMin);
	      }
      }
      fdebug ("REMAINING SIZE : $RemainingSize \n");
     
      #incrementation of the sum of partitions
      $SumPartSize+=$PartSize;                                 
      $temp=$PartSize-$hashobj{$eltList}->sizeMin;
      $temp+=$temp;
      fdebug ("\nvalue of the additional size allocated : $temp\n");
      fdebug ("\nsum of the partitions in the loop: $SumPartSize\n");	
	  }
  }
  
  #manual method
  else  {																															 
  $i=0;
  my $eltPart;
	  foreach $eltList (@listHd) {	
	    $i++;
	    $FullSize=$hashobj{$eltList}->sizeDisk;         #full size
	    $nbHD=$i;                                       #nb disks
	    $hashobj{$eltList}->nbHD($nbHD);
	    fdebug("size of the disk $eltList : ");
	    fdebug($hashobj{$eltList}->sizeDisk);
	    
	    $SumSizeMin=sumSizeMin($hashobj{$eltList}->device);
	      
			$RemainingSize= $FullSize-$SumSizeMin;
			
		  #----------------------preliminary calculation of the partitions sizes---------------------------------------			
		
			foreach $eltPart (@listPart) {
			    if ($hashobj{$eltPart}->deviceUsed ne $hashobj{$eltList}->device)  {
			      next;
			    }
	      fdebug ("\nbeginning of the loop ------> sizegrow : ");
	      fdebug ($hashobj{$eltPart}->sizeGrow);
	      fdebug ("\n                      ------> coeffgrow : ");
	      fdebug ($hashobj{$eltPart}->coeff);
	      fdebug ("\n                      ------> taille min : ");
			        
	      #-------------------------calculation of the partition size in function of the grow-------------------------
	    	      
	      #if the grow is 0 or 1, a special treatment is necessary
        if ($hashobj{$eltPart}->partDone==0)  {			  				
	        if ($hashobj{$eltPart}->sizeGrow == 0 | $hashobj{$eltPart}->sizeGrow == 1) {
	          
	          #if grow = 0 then, the partition size is the size mini
	          if ($hashobj{$eltPart}->sizeGrow == 0 ) {
	            $temp = 0;
	            $PartSize=$hashobj{$eltPart}->sizeMin;
	          }
	          
	          #if the grow = 1, the grow takes all the remaining free space
	          else {
		      
	      
              $temp = $RemainingSize*$hashobj{$eltPart}->coeff;
              $PartSize = $temp + $hashobj{$eltPart}->sizeMin;
		  
	          }
	        }
						        
	        #for the other case we do that : 
	        else {
	          $temp = $RemainingSize*$hashobj{$eltPart}->coeff;
	          $PartSize=$temp + $hashobj{$eltPart}->sizeMin;
	
          	if ($PartSize > $hashobj{$eltPart}->sizeGrow) {
            	$PartSize=$hashobj{$eltPart}->sizeGrow;
            }
	        }
	        $hashobj{$eltPart}->sizePart($PartSize);                 #valorisation dans la structure associée
					$hashobj{$eltPart}->partDone(1);							
	
	        fdebug ("\n\nPARTITION SIZE : $PartSize \n");
	        if ($hashobj{$eltPart}->sizeGrow==0) {
	          fdebug ("\nsize mini, it is not necessary to decrement the remaining size\n\n");
	        }
	        else {
	          if ($RemainingSize<=0) {
	            fdebug ("\nall the space was taken by the grows\n");
	            $RemainingSize=0;
	          }
	          else {
	          	$RemainingSize-=($PartSize-$hashobj{$eltPart}->sizeMin);
	          }
	        }
	        fdebug ("REMAINING SIZE : $RemainingSize \n");
	        $SumPartSize+=$PartSize;                                       #incrementation de la somme des partitions
	        $temp=$PartSize-$hashobj{$eltPart}->sizeMin;
	        $temp+=$temp;
	        fdebug ("\nvalue of the additional size allocated : $temp\n");
	        fdebug ("\nsum of the partitions in the loop: $SumPartSize\n");
				}				
			}
		}
			 	 			  
	}
	$debugRes=1;
	$debugTest=1;
	$debug=1;
   
}

#*********************************distributeSpace*********************************
#redistribute the space in order to make all the partition fit on the disks

#input: none
#stores the value of the device used by the partition in the attribute deviceUsed of the partition structure
#called in the main
sub  distributeSpace
{
	$debugRes=0;
	$debugTest=0;
	$debug=0;
	fdebug("\n-----------------\n");
	fdebug("|DISTRIBUTESPACE|\n");
	fdebug("-----------------\n");
	my $eltList;                  #elements d'une liste
	my $temp=0;
	
	#--------------------------------REPARTITION OF THE PARTITIONS ON SEVERAL DISKS--------------------------------
	my $PartId;             #id of the partition, if it is valid, it determines also on which dd is the partition:hda1, hda2...  
																										
	my $sizeTemp=0;         
	my $eltListHD;          
	my $eltListPart;        
	my $partDone=0;         #variables that indicates that the partition is valid
													
													
	my $partNb=1;           
	my $device="nodevice";  
	my $incr=0;             
	my $deviceNb=0;         
	    
	foreach $eltListHD (@listHd) {
	  $device=$hashobj{$eltListHD}->device;
	  $sizeTemp=0;
	
	  foreach $eltListPart (@listPart) {
	  $incr++;
						
	    #if the partition has already been done, it is not necessary to redo it :)
	    
	    #if partDone=0, the partition is not valid or not treated
	   
	    if ($hashobj{$eltListPart}->partDone==0) {					
				if ($hashobj{$eltListPart}->method eq 'auto')  {
	       
	        #if there is the space and if the preceding partition is valid, one can carry out partitioning
	        if ($hashobj{$eltListHD}->usedSpace+$hashobj{$eltListPart}->sizePart <= $hashobj{$eltListHD}->sizeDisk && $incr == $temp+1) {
	          $hashobj{$eltListPart}->partNb($partNb);
	          $hashobj{$eltListPart}->deviceUsed($device);
	          $temp=$partNb;
	          $partNb++;
	          $partDone=1;
	          $hashobj{$eltListPart}->partDone($partDone);
	          $sizeTemp+=$hashobj{$eltListPart}->sizePart;
	          $hashobj{$eltListPart}->deviceNb($deviceNb);
	          $hashobj{$eltListHD}->usedSpace($sizeTemp);
	          $hashobj{$eltListHD}->freeSpace($hashobj{$eltListHD}->sizeDisk-$sizeTemp);	
	        }
	        
	        #in the contrary case, one does nothing but to label the partition as defective
	        else {
	          $partDone=0;
	          $device=0;
	          $hashobj{$eltListPart}->partNb($temp);
	          $hashobj{$eltListPart}->deviceUsed($device);
	          $hashobj{$eltListPart}->partDone($partDone);
	          $hashobj{$eltListPart}->deviceNb($deviceNb);
	
	          if ( $sizeTemp==0 ) {
	              $hashobj{$eltListPart}->deviceNb($deviceNb-1);
	
	          }	
	        }
				}
				#manual method
				else  {
					
					#if there is the space and if the preceding partition is valid, one can carry out partitioning
          if ($hashobj{$eltListHD}->usedSpace+$hashobj{$eltListPart}->sizePart < $hashobj{$eltListHD}->sizeDisk && $incr == $temp+1) {
	          $hashobj{$eltListPart}->partNb($partNb);
	          $temp=$partNb;
	          $partNb++;
	          $partDone=1;
	          $hashobj{$eltListPart}->partDone($partDone);
	          $sizeTemp+=$hashobj{$eltListPart}->sizePart;
	          $hashobj{$eltListPart}->deviceNb($deviceNb);
	          $hashobj{$eltListHD}->usedSpace($sizeTemp);
	          $hashobj{$eltListHD}->freeSpace($hashobj{$eltListHD}->sizeDisk-$sizeTemp);
          }
	            
          #in the contrary case, one does nothing but to label the partition as defective
          else {
				    $partDone=0;
				    $device=0;
	          $hashobj{$eltListPart}->partNb($temp);
	          $hashobj{$eltListPart}->partDone($partDone);
	          $hashobj{$eltListPart}->deviceNb($deviceNb);
	
	          if ( $sizeTemp==0 ) {
	              $hashobj{$eltListPart}->deviceNb($deviceNb-1);
	
	          }
          }																													
				}				
	    }
	    
	    #in this case, the partition was treated, in other terms, partDone=1
	    else {
	    	fdebug("\n-------partition done");
	    }
	
	    fdebug("\ncurrent Disk : ");
	    fdebug($hashobj{$eltListHD}->device);
	    fdebug("\n");
	    fdebug($hashobj{$eltListPart}->mountPoint);
	    fdebug("\n------- : ");
	    fdebug($hashobj{$eltListPart}->partNb);
	    fdebug("\npartition size : ");
	    fdebug($hashobj{$eltListPart}->sizePart);
	    fdebug("\ndevice used for this partition : ");
	    fdebug($hashobj{$eltListPart}->deviceUsed);
	
	    fdebug("\nsum of the partitions on this disk : ");
	    fdebug($hashobj{$eltListHD}->usedSpace);
	    fdebug("\n");
	    fdebug("disk size : ");
	    fdebug($hashobj{$eltListHD}->sizeDisk);
	    fdebug("\ncounter : $incr\n");	
	  }
	  $incr=0;
	  $deviceNb++;
	}
	$debugRes=1;
	$debugTest=1;
	$debug=1;
}


#*********************************partsOK********************************* 
#indicates that all the partitions were done

#input: none
#stores the value of the attribute partsDone that indicates that all the partitions are valid
#called by the main
sub partsOK
{
	my $eltList;                      #element d'une liste
	my $elt;
	foreach $eltList (@listPart) {
	  if ($hashobj{$eltList}->partDone == 0)  {
	    foreach $elt (@listPart) {
	    	$hashobj{$eltList}->partsDone(1);
	    }
	    fatalExit("Error: there are partitions not valid");
	  }
	  else    {
	  	$hashobj{$eltList}->partsDone(1);
	  }
	}
}


#*********************************partSize*********************************
# This subfunction calculates the sector counts according to the partition size and the disk geometry

#input: none
#stores the size of the partition in sectors according to the boundaries
#called by the main
sub partSize
{
		
	my $size;
	my ($sectors,$heads);
	my $nb;
	my $part;
	my $device="none";
	my $start=0;
	my $boundary;
	my $partCount=0;
	
	$debugRes=0;
	$debugTest=0;
	$debug=0;
	        
	fdebug ("conversion en secteurs de boundary :\n");
	foreach $part (@listPart)  {
	  $partCount++;
	  $device=$hashobj{$part}->deviceUsed;
	#erreur possible si mauvais device specifie
	  $sectors=$hashobj{$device}->sectors;
	  $heads=$hashobj{$device}->heads;
	  $boundary=$sectors * $heads;
	  $size=$hashobj{$part}->sizePart;
	  $nb = $size / 512 + $sectors;
	  $nb = int ($nb / $boundary);
	  if ($partCount == 1)  {
	  	$start=$hashobj{$device}->sectors;
	  }
	  else  {
	  	$start=0;
	  }
		$nb = $nb * $boundary -$start;
	  fdebug ("boundary=$boundary size=$size nb=$nb\n");
	  $hashobj{$part}->sizePart($nb);	   
	}
	return $nb;
	$debugRes=0;
$debugTest=0;
	$debug=0;
}
#*********************************diskSize*********************************
#calculate the disk sizes in term of sectors
#RESERVED FOR FUTURE USE
sub diskSize
{
	my $size;
	my ($sectors,$heads,$cylinders);
	my $disk;
	my $device;
	my $boundary;
	        	
	foreach $disk (@listHd)  {
	  $device=$hashobj{$disk}->device;
	  $sectors=$hashobj{$device}->sectors;
	  $heads=$hashobj{$device}->heads;
	  $cylinders=$hashobj{$device}->cylinders;
	  $size=$sectors * $heads * $cylinders;
	  $hashobj{$disk}->sizeDisk($size);
	}
	return $size;
}


#*********************************diskSize*********************************
#writing of the partitions in the input file, then sfdisk...(caution)

#input: none
#write in the sfdisk input file
#called by the main
sub writePart
{
	my $size;
	my $part;
	my $hd;
	my $device;
	my $partCount=0;
	my $logicalSize=0;
	my $sumPrimarySize=0;
	my $start;
	my $hdCount=0;
	my $fileSystem;
	my $line;
	my $label;
	my @result;
	my $Id;
        my $counter=0;
	my %labelStart=();
	my %labelDevice=();
	tie %labelStart, "Tie::IxHash";
	tie %labelDevice, "Tie::IxHash";
        my $tooSmall;
  my $formatOk = 0;
  
  $debugRes=0;
	$debugTest=0;
	$debug=0;
	
	#for each device, a input file is created for sfdisk, "parted.data"
	foreach $hd (@listHd)   {			 
		open (PART,">parted.data") || die ("error in the opening of the file");
		print PART "unit: sectors\n\n";
		
		#This is a counter to localize the logical partition  
		foreach $part (@listPart)   {
		    if ($hashobj{$part}->deviceUsed eq $hashobj{$hd}->device)  {
			$partCount++;
		    }
			if ($partCount <= 3)  {
		  	$sumPrimarySize+=$hashobj{$part}->sizePart;
		  }
		}
		$logicalSize=$hashobj{$hd}->sizeDisk - $sumPrimarySize;
		$logicalSize-=$hashobj{$hd}->sectors;
		$counter=$partCount;
	        $partCount=0;
		$start=$hashobj{$hd}->sectors;
		$hdCount++;
		foreach $part (@listPart)  {
			#one do the partitionning if the device used is the same as the current device
			if ($hashobj{$part}->deviceUsed eq $hashobj{$hd}->device)  {
				$partCount++;
				
				$Id=$hashobj{$part}->fsType;
				
				#valorization of the Id of the Filesystem for sfdisk
				#swap  => 82
				#ext3  => 83
				#ext2  => 83
				#ntfs  => 86
				#fat16 => ?
				#fat32 => ?
				#xfs   => ?
				if ($Id eq "swap")  {
					$Id='82';
				        $formatOk=1;
				}
				if ($Id eq "ext2" | $Id eq "ext3")  {
					$Id='83';
				        $formatOk=1;
				}
				if ($Id eq "ntfs")  {
					$Id='86';
				        $formatOk=1; 
				}
				if ($Id eq "xfs")  {
					$Id='83';
				        $formatOk=1;
				}		
			        if ($Id eq "fat32")  {
				       $Id='0b';
				       $formatOk=1;
				}
			        if ($Id eq "fat16")  {
				       $Id='06';
				       $formatOk=1;
				}
			        
			        if ($formatOk == 0)  {
				    fatalExit("ERROR the format $Id is not supported\n");
				}
			        
			        if ($hashobj{$part}->sizePart <= 0)  {
				    $tooSmall=$hashobj{$part}->mountPoint;
               			    fatalExit("the size of the partition $tooSmall is too small (none is the swap partition)");
				}
				#when partCounter >=5, the partitions are logical      		               
				if ($partCount >=5)  {
					$start+=$hashobj{$hd}->sectors;
					$size=$hashobj{$part}->sizePart;
					$size-=$hashobj{$hd}->sectors;
					$hashobj{$part}->sizePart($size);
				}
				#in the case below, the partition is the first of a new device
				if ($partCount == 1 && $hdCount >= 2)  {
					$size=$hashobj{$part}->sizePart;
					$size-=$hashobj{$hd}->sectors;
					$hashobj{$part}->sizePart($size);
				}
			        
				print PART $start;
				$hashobj{$part}->start($start);
				print PART ",";				
				#when partCount =4 it is an extended partition		   
				if ($partCount == 4 && $counter>=5)  {
					print PART $logicalSize;
					print PART ",5\n";
					$start+=$hashobj{$hd}->sectors;
					print PART $start;
					$hashobj{$part}->start($start);
					print PART ",";
					$size=$hashobj{$part}->sizePart-$hashobj{$hd}->sectors;
					print PART $size;
					print PART ",";
					print PART $Id;
					print PART "\n";
					$start+=$size;
				}				
			        if ($partCount == 4 && $counter<5)  {
				        print PART $hashobj{$part}->sizePart;
					print PART ",";
					print PART $Id;
					print PART "\n";
				}
				#all the other cases	
				if ($partCount!=4)  {
					print PART $hashobj{$part}->sizePart;
					print PART ",";
					print PART $Id;
					print PART "\n";
				}				
				#first partition
				if ($partCount == 1 )  {
					$start=$hashobj{$part}->sizePart+$hashobj{$hd}->sectors;
				}			
				#all the partitions that are neither logical neither the first of a device
				if($partCount!=4 && $partCount!=1) {
					$start+=$hashobj{$part}->sizePart;								
					#Nota Bene : the value of start is loaded in the structure Part in order to 
					#recognize the partition with its label and start for the formatting				
				}
			}
		}		
		close (PART);
		$device=$hashobj{$hd}->device;
		#PARTITIONNING
		
#
#ERROR #3
#
	       
	        my @ligfic;
	        if (open(FILE,"parted.data")) {@ligfic=<FILE>};
	      
	        close(FILE);
	        
	        if ($ligfic[2]) {
		   system("sfdisk /dev/$device < parted.data") && die ("\nERROR : partitionning could not be done!"); 
		}
		
				   
		
		#the table below contains the values of the partition table
		#@result = system("sfdisk -d /dev/$device");
	  @result = `sfdisk -d /dev/$device`;
	  #`sfdisk -d /dev/$device > tablePartitions`; 
		fdebug(@result);
	        
	  foreach $line (@result)  {
		        
			if ($line=~m"^/dev/([a-z]+)(\d) : start=\s*(\d+), size=\s*(\d+), Id=\s*(\d+)") {
				$labelStart{($1).($2)}=($3);
				$labelDevice{($1).($2)}=($1);
			}
		  if ($line=~m"^/dev/([a-z]+)(\d) : start=\s*(\d+), size=\s*(\d+), Id=\s*(\d[a-z])") {
				$labelStart{($1).($2)}=($3);
				$labelDevice{($1).($2)}=($1);
			}
		  if ($line=~m"^/dev/([a-z]+)(\d) : start=\s*(\d+), size=\s*(\d+), Id=\s*([a-z])") {
				$labelStart{($1).($2)}=($3);
				$labelDevice{($1).($2)}=($1);
			}
		
	        if ($line=~m"^/dev/(cciss/c\dd\d)(p\d) : start=\s*(\d+), size=\s*(\d+), Id=\s*(\d+)") {
				$labelStart{($1).($2)}=($3);
				$labelDevice{($1).($2)}=($1);
		    
			}
		  if ($line=~m"^/dev/(cciss/c\dd\d)(p\d) : start=\s*(\d+), size=\s*(\d+), Id=\s*(\d[a-z])") {
				$labelStart{($1).($2)}=($3);
				$labelDevice{($1).($2)}=($1);
			}
		  if ($line=~m"^/dev/(cciss/c\dd\d)(p\d) : start=\s*(\d+), size=\s*(\d+), Id=\s*([a-z])") {
				$labelStart{($1).($2)}=($3);
				$labelDevice{($1).($2)}=($1);
			}
		}
		my $cle;
		my $val;  
		fdebug("\n Hash tables needed for the formatting : \n"); 
		while (($cle, $val) = each (%labelDevice))  {
		fdebug (" $cle => $val\n");
		}
		while (($cle, $val) = each (%labelStart))  {
		fdebug (" $cle => $val\n");
		}    
				
		#formatting of the partitions
		foreach $part (@listPart)  {
		  if ($hashobj{$part}->deviceUsed eq $device)  {
#
#ERROR #4						      
#
				foreach $label (keys %labelStart)  {			      
	 		    if ($hashobj{$part}->start == $labelStart{$label} && $hashobj{$part}->deviceUsed eq $labelDevice{$label})  {
 		     		$hashobj{$part}->deviceUsed($label);
		        $fileSystem=$hashobj{$part}->fsType;
				
						print "formating /dev/$label\n";
						
						if ($fileSystem eq "swap")  {
							system("mkswap /dev/$label") && die("ERROR while formatting the swap partition");
							system("swapon -a") && die("ERROR while activating the swap partition") ;
							fdebug ("$label => $fileSystem\n");
						}
				                if ($fileSystem eq "xfs")  {
							system("mkfs.xfs -f /dev/$label") && die("ERROR while formatting the swap partition");
							
							fdebug ("$label => $fileSystem\n");
						}
						if (($fileSystem eq "fat32") or ($fileSystem eq "fat16"))  {
						  if ($fileSystem eq "fat32")  {
						  	system("mkfs.vfat -F 32 /dev/$label") && die ("ERROR while formatting a fat32 partition");
						  }
						  else  {
							  system("mkfs.vfat -F 16 /dev/$label") && die ("ERROR while formatting a fat32 partition");
							}
						}
						#In the case the file system type is xfs, the kernel must be patched, orelse it won't work
						if (($fileSystem ne "swap") && ($fileSystem ne "fat32") && ($fileSystem ne "fat16") && ($fileSystem ne "xfs"))  {
 							system("mkfs -t $fileSystem /dev/$label") && die("ERROR while formatting the partition $label in $fileSystem");
			 				fdebug ("$label => $fileSystem\n");
				 		}		
			    }	  			
				}
	 		}
		}
		
		#print "\npartition created and formatted with succes\n"; 
		`cp parted.data testParted`;
		`rm parted.data`;
	}
	$debugRes=1;
	$debugTest=1;
	$debug=1;
}


#*********************************writeFstab*********************************
#writing in fstab

#input: none
#write the fstab file
#called by the main
sub writeFstab
{
	my $part;
	my $hd;
	my $device;      
	open (FSTAB,">$fstab") || die ("error in the opening of the file");
	foreach $part (@listPart)  {						
		if ($hashobj{$part}->deviceUsed=~m"^(cciss/c\dd\dp\d)") {
			$device=($1);
			
			$hashobj{$part}->deviceUsed($device);	
		    
		    
		}
	        
		print FSTAB "/dev/";
		print FSTAB $hashobj{$part}->deviceUsed;
		print FSTAB "     ";
		print FSTAB $hashobj{$part}->mountPoint;
		print FSTAB "     ";
		print FSTAB $hashobj{$part}->fsType;
		print FSTAB "     ";
	        
	        
	        if ($hashobj{$part}->mountPoint eq 'none' | $hashobj{$part}->mountPoint eq '/')  {
	 	    
		    if ($hashobj{$part}->mountPoint eq '/')  {
		      print FSTAB "errors=remount-ro";
		      print FSTAB "     "; 
		      print FSTAB "0  1\n";
		    }
	            if ($hashobj{$part}->mountPoint eq 'none')  {
		      print FSTAB "sw";
	 	      print FSTAB "     "; 
		      print FSTAB "0  0\n";
		    }  
		}
	        else {
		      print FSTAB "defaults";
	 	      print FSTAB "     "; 
		      print FSTAB "0  0\n";
	        }
	}
        print FSTAB "proc";
		print FSTAB "     ";
		print FSTAB "/proc";
		print FSTAB "     ";
		print FSTAB "proc";
		print FSTAB "     ";
		print FSTAB "defaults";
		print FSTAB "     ";
		print FSTAB "0  0\n";
                print FSTAB "/dev/fd0";
		print FSTAB "     ";
		print FSTAB "/floppy";
		print FSTAB "     ";
		print FSTAB "auto";
		print FSTAB "     ";
		print FSTAB "user,noauto";
		print FSTAB "     ";
		print FSTAB "0  0\n";
                print FSTAB "/dev/cdrom";
		print FSTAB "     ";
		print FSTAB "/cdrom";
		print FSTAB "     ";
		print FSTAB "iso9660";
		print FSTAB "     ";
		print FSTAB "ro,user,noauto";
		print FSTAB "     ";
		print FSTAB "0  0\n";
	close (FSTAB);
	
}
#*********************************diskSize*********************************
#recalculate the numbers of device in function of the disks used
#NOT USED
sub setNbPart
{
	my $part;
	my $previousDevice=$hashobj{$listPart[0]}->deviceUsed;
	my $newNbPart=1;
	my $diskJumped=0;
	
	foreach $part (@listPart)  {
	  if ($hashobj{$part}->deviceUsed eq $previousDevice)    {
	    $previousDevice=$hashobj{$part}->deviceUsed;
	    if ($diskJumped==1)  {
				$hashobj{$part}->partNb($newNbPart);
				$newNbPart++;
	    }
	  }
	  else   {
	    $hashobj{$part}->partNb($newNbPart);
	    $newNbPart++;
	    $previousDevice=$hashobj{$part}->deviceUsed;
	    $diskJumped=1;
	  }
	}
}

#*********************************setGrowSpace*********************************
#calculate the space to distribute for the grow of the big partitions

#input: none
#stores the value of growSpace which is usefull for the space redistribution
#called by adjustSizePart 
sub setGrowSpace
{
	my $previousDevice=$hashobj{$listPart[0]}->deviceUsed;
	my $part;
	my $hd;
	my $growSpace=0;
	my $sumSizeMin=0;
	my $lastDeviceUsed=0;
	my $stop=0;
	
	#for each disk, the remaining size is calculated
	foreach $hd(@listHd)    {
	  $previousDevice=$hashobj{$hd}->device;
	  
	  #precalculation of the sum of the size mini already done
	  foreach $part(@listPart)    {
	    if ($hashobj{$part}->deviceUsed ne 0 && $hashobj{$part}->deviceUsed eq $previousDevice)    {
	      $previousDevice=$hashobj{$part}->deviceUsed;
	      $sumSizeMin+=$hashobj{$part}->sizeMin;
	      $lastDeviceUsed=$hashobj{$part}->deviceUsed;
	    }
	  }
	
	  #if there is space for the partition, the device is valorized
	  foreach $part(@listPart)    {
	    if ($hashobj{$part}->deviceUsed eq 0 && $sumSizeMin+$hashobj{$part}->sizeMin < $hashobj{$lastDeviceUsed}->sizeDisk && $stop == 0)    {
	      $hashobj{$part}->deviceUsed($lastDeviceUsed);
	      $growSpace=$hashobj{$lastDeviceUsed}->sizeDisk-$sumSizeMin;
	      $hashobj{$lastDeviceUsed}->growSpace($growSpace);
	      $stop=1;
	    }
	  }
	}
}

#*********************************adjustSizePart*********************************
#adjustSizePart : corrects the lack of space

#input: none
#stores the corrected partitions size in the structure
#called by the main
sub adjustSizePart
{
	$debugRes=0;
	$debugTest=0;
	$debug=0;
	fdebug("\n----------------\n");
	fdebug("|ADJUSTSIZEPART|\n");
	fdebug("----------------\n");
	my $eltList;                     
	my $parts;
	my $device;                       #name of the device
	my $deviceNb;                     #number of the device
	my $newPartSize=0;                #new partition size
	my $oldSizePart=0;
	my $partDone;                     #partition done (partDone=1) or not
	my $sumSizeMinNotDone=0;          #sum of the sizes mini not done
	my $i=0;                          #counter
	
	#calculation of the sum of the sizes mini not done
	foreach $eltList (@listPart) {	              
		if ($hashobj{$eltList}->partDone==0 && $hashobj{$eltList}->sizeGrow == 0) {
			$sumSizeMinNotDone+=$hashobj{$eltList}->sizeMin;
		}
	}	
	foreach $eltList (@listPart) {	
	  #if partDone=0, the partition isn't done, space is missing
	  
	  #if partsDone=1, all the partitions have been done, there is still space to distribute 
	
	  #test that filters the partitions already done. since this function corrects, it is useful only if the partition isn't done
	  if ($hashobj{$eltList}->partDone==0) {
	    $i++;
	    #Case of the partition which takes all the remaining size (if there are several disks, it can be very large)	    
	    if ($hashobj{$eltList}->sizeGrow == 1) {
	      $deviceNb=$hashobj{$eltList}->deviceNb;
	      $hashobj{$eltList}->deviceNb($deviceNb+1);
	      $device=$listHd[$deviceNb+1];
	      $hashobj{$eltList}->deviceUsed($device);
	      $newPartSize=$hashobj{$device}->freeSpace - $sumSizeMinNotDone;
	      $partDone=1;
	      $hashobj{$eltList}->sizePart($newPartSize);
	      $hashobj{$eltList}->partDone($partDone);
	      $hashobj{$device}->freeSpace($hashobj{$device}->freeSpace-$newPartSize);
	      $hashobj{$device}->usedSpace($hashobj{$device}->usedSpace+$newPartSize);
	      $hashobj{$eltList}->partNb($hashobj{$eltList}->partNb+$i);	
	    }
	    #if there was a problem with a partition with a grow=1, maybe there are partitions not done
			if ($hashobj{$eltList}->sizeMin <= $hashobj{$device}->freeSpace && $hashobj{$eltList}->sizeGrow ==0)  {
			$deviceNb=$hashobj{$eltList}->deviceNb;
			$newPartSize=$hashobj{$eltList}->sizeMin;
			$hashobj{$eltList}->sizePart($newPartSize);
			$partDone=1;
			$hashobj{$eltList}->partDone($partDone);
			$hashobj{$eltList}->deviceUsed($device);
			$hashobj{$device}->usedSpace($hashobj{$device}->usedSpace+$newPartSize);
			$hashobj{$device}->freeSpace($hashobj{$device}->freeSpace-$newPartSize);
			$hashobj{$eltList}->partNb($hashobj{$eltList}->partNb+$i);
	    }
	    
	    #case of a partition with a grow!=1
	    
	    #before the grow repartition, we recalculate the free space on the current device
	    if ($hashobj{$eltList}->sizeGrow != 1 && $hashobj{$eltList}->sizeGrow != 0 && $hashobj{$hashobj{$eltList}->deviceUsed}->growSpace!=0) {
	    	setGrowSpace();
	    }
	    #if the device!=0, that means there is space on it for the partition
	    #cf setGrowSpace 
	    if ($hashobj{$eltList}->deviceUsed ne 0)    {	
	      #if growspace!=0
	      #if grow != (0 | 1) ,partition size = coefgrow * remaining size
	      #if grow=0, partition size= size min
	
	      fdebug ("grow space : \n");
	      fdebug ($hashobj{$hashobj{$eltList}->deviceUsed}->growSpace);
	      fdebug ("\n");
	
	      #if grow!=0, we recalculate the size on the new partition
	      if ($hashobj{$eltList}->sizeGrow != 1 && $hashobj{$eltList}->sizeGrow != 0 && $hashobj{$hashobj{$eltList}->deviceUsed}->growSpace != 0) {
	   
	        #with this method, all the grow partitions are recalculated
	        foreach $parts(@listPart)    {
	          if ($hashobj{$parts}->sizeGrow != 1 && $hashobj{$parts}->sizeGrow != 0 && $hashobj{$parts}->deviceUsed eq $hashobj{$eltList}->deviceUsed)    {
	            $newPartSize=$hashobj{$parts}->coeff * $hashobj{$hashobj{$parts}->deviceUsed}->growSpace + $hashobj{$eltList}->sizeMin;
	            $hashobj{$parts}->sizePart($newPartSize);
	            $hashobj{$device}->usedSpace($hashobj{$device}->usedSpace+$newPartSize);
	            $hashobj{$device}->freeSpace($hashobj{$device}->freeSpace-$newPartSize);
	          }
	          $partDone=1;
	          $hashobj{$eltList}->partDone($partDone);
	          $hashobj{$eltList}->partNb($hashobj{$eltList}->partNb+$i);
	        }	
	      }
	      
	      #if there is no grow size
	      if ($hashobj{$eltList}->sizeGrow == 0 && $hashobj{$hashobj{$eltList}->deviceUsed}->growSpace != 0)    {
	        $newPartSize=$hashobj{$eltList}->sizeMin;
	        $hashobj{$eltList}->sizePart($newPartSize);
	        $hashobj{$device}->usedSpace($hashobj{$device}->usedSpace+$newPartSize);
	        $hashobj{$device}->freeSpace($hashobj{$device}->freeSpace-$newPartSize);
	        $partDone=1;
	        $hashobj{$eltList}->partDone($partDone);
	        $hashobj{$eltList}->partNb($hashobj{$eltList}->partNb+$i);
	      }
	    }	
	  }
	  	
	  #if the partitions were done and there is a grow!=0 $$!=1, there are still free space to distribute
	  if ($hashobj{$eltList}->partsDone==1 && $hashobj{$eltList}->sizeGrow != 0 && $hashobj{$eltList}->sizeGrow != 1) {	
	    
	    #if the remaining size >0
	    if ($hashobj{$hashobj{$eltList}->deviceUsed}->freeSpace >0)  {
	      $newPartSize=$hashobj{$eltList}->sizePart+$hashobj{$hashobj{$eltList}->deviceUsed}->freeSpace*$hashobj{$eltList}->coeff;
	      $oldSizePart=$hashobj{$eltList}->sizePart;
	      
	      #if the sizes is over the max size, the max size is chosen
	      if ($newPartSize > $hashobj{$eltList}->sizeGrow)  {
	        fdebug ("\nla taille max a ete depassee, la taille de la partition prendra l'espace maxi\n");
	        $newPartSize=$hashobj{$eltList}->sizeGrow;
	      }
	      
	      #orelse, one redistribute the space with the grow coeff on the new remaining size
	      else {
	      	fdebug ("\ntaille du grow optimisée avec succes\n");
	      }
	      
	      #update of the fields concerned by the modification
	      $hashobj{$eltList}->sizePart($newPartSize);
	      $hashobj{$hashobj{$eltList}->deviceUsed}->freeSpace( $hashobj{$hashobj{$eltList}->deviceUsed}->freeSpace-($newPartSize-$oldSizePart));
	      $hashobj{$hashobj{$eltList}->deviceUsed}->usedSpace( $hashobj{$hashobj{$eltList}->deviceUsed}->usedSpace+($newPartSize-$oldSizePart));	
	    }
	  }
	}
	$debugRes=1;
	$debugTest=1;
	$debug=1;
}
#*********************************testConfSyntax*********************************
#function that detects the errors

#input: configuration file
#do the detection and check the configuration file syntax
#returns nothing but if an error occurs, the program is stopped
sub testConfSyntax
{
  my $part;
  my $hd;
  my $confSyntax=0;
  my $confFile=shift;
  my @listedevice=();                
  my $device;
  my $size;
  my $line;
  my $partType="none";                    
  my $deviceUsed="none";
  my $method="auto";
  my $sizeGrow=0;
  my $countPart=1;
  my $p;            #pt de montage | mount point
  my $q;
  my $varTemp;
  
  #We have to do a predetection to compare the information in the configuration file with the devices data
	
  @listedevice=`sfdisk -g`;       
#
#ERROR  #1
#    
  
  if (! @listedevice)  {
    fatalExit("\nFATAL ERROR : No hard drive detected\n");
  }	
    
  foreach $line (@listedevice) {
    chomp($line);
    if ($line=~m"^/dev/([a-z]+): (\d+) cylinders, (\d+) heads, (\d+) sectors/track") {	    
      $device=$1;
      $size=`sfdisk /dev/$device -s`;
      $size=$size*1024;
	  
      my $a = Disk->new();                   #creation of a structure disk
	    
      #valorization of the fields of this struct
      $a->device($1);                 #device
      $a->sizeDisk($size);						#sizeDisk : disk size
      $hashobj{$1}=$a;
   
      #storage of a struct pointer in a hash table used for the parsing of the disks structures created        
      push @listHd, $1;               #la cle est le device  														 | the key is the device
    }
    if ($line=~m"^/dev/(cciss/c\dd\d): (\d+) cylinders, (\d+) heads, (\d+) sectors/track") {	    
      $device=$1;
      $size=`sfdisk /dev/$device -s`;
      $size=$size*1024;
	
      my $a = Disk->new();                   #creation of a structure disk
    
      #valorization of the fields of this struct
      $a->device($1);                 #device
      $a->sizeDisk($size);						#sizeDisk : disk size
      $hashobj{$1}=$a;
  
      #storage of a struct pointer in a hash table used for the parsing of the disks structures created        
      push @listHd, $1;               #la cle est le device  														 | the key is the device
    }
  }
  
  #------------------------------------------------------------------------------------------------------------
  #                                         SYNTAX
  # parameters to be specified:
  #   - mountPoint </|/boot|/home|...>
  #   - fstype <swap|ext2|ext3|xfs|fat32|fat16>
  #   - size <taille>[K|M|G|%]		         # if % no growto allowed on the same device
  #
  # optionnal parameters:
  #   - growto <taille>[K|M|G]		         
  #   - deviceUsed <hda|hdb|sda...>      # if used, must be specified for all partitions
  #
  # For the swap, the mountPoint may be      : - none if one swap partition
  #                                            - none1, node2, ... if several
  #------------------------------------------------------------------------------------------------------------
  
  foreach $p (keys %part) {              		
    #list of the keys for the access to the partitions structures
    push @listPart, $p;                              
    if (exists($part{$p}{'growto'})) {
      $sizeGrow=$part{$p}{'growto'};             
    }
    
    #if there is no grow, its value will be -1
    else {
      $sizeGrow=-1;                              
    }
    $partType=$part{$p}{'parttype'} if exists($part{$p}{'parttype'});
    $deviceUsed=$part{$p}{'deviceUsed'} if exists($part{$p}{'deviceUsed'});
    if ($deviceUsed ne "none")  {
      $method = "manual";
    }
    
    
    #TESTS

    #test of the mount point
    ##first, are there two times the same mount point?
    ##for this test, it is necessary to read the configuration file
    ##because the tie hash will not contain two times the same key
    my $line;
    my $dupe;
    my @splitTab;
    my @filePart;
    my @tab;
    my @tab_bis;
    my $el;
    my $el2;
    my $parttested;
    my $inc=0;
    my $inc2=0;
    my $slash=0;
    my $fstypeOK=0;
    
    if (open(PARTDATA,"part.data")) {
      @filePart=<PARTDATA>;
    }
    else {
      fatalExit("erreur lecture de part.data");
    }
    close(PARTDATA);
    
    foreach $line (@filePart)  {
      @splitTab=split (/=/,$line);
      $dupe=$splitTab[0];
      chop($dupe);
      push (@tab,$dupe);
      push (@tab_bis,$dupe);
    }
    
    close (PARTDATA);
       
    foreach $el2 (@tab)  {
      $parttested=$el2;
      $inc2=0;
      foreach $el (@tab_bis)  {
	if ($inc != $inc2)  {
          if ($el eq $parttested) {
	    fatalExit("there cannot be two times the same mountPoint : $parttested");
          }    
        }
	$inc2++;
      }
      $inc++;
    }
    
    #verification que la partition  / est presente
    foreach $p (keys %part) {
      if ($p eq "/")  {
        $slash=1;
	last;
      }
    }
    if ($slash==0)  {
      fatalExit("there is no / partition, the partitionning cannot be done");
    }
      
      
    #verification qu'il n'y a pas de point de montage /etc
    if ($p eq "/etc")  {
       fatalExit("/etc must be linked to /, so it is not possible to create this mountPoint");
    }  
      
    my $none="nothing";
    if ($p=~m"^(none\d)")  {
	$none=$1;	
    }   
    
    if ($p eq ("none" or "/[a-z]+") or $none)  {  
      fdebug("mountPoint ");
      fdebug($p);
      fdebug(" ===> [OK]\n");
    }
    else  {
      fatalExit("The mount point $p is not valid");
    }
    #test of the fstype
    if ($part{$p}{'fstype'} eq "swap") {
      fdebug("fsType ");
      fdebug($part{$p}{'fstype'});
      fdebug(" ===> [OK]\n");
      $fstypeOK=1;
    }
    if ($part{$p}{'fstype'} eq "ext2") {
      fdebug("fsType ");
      fdebug($part{$p}{'fstype'});
      fdebug(" ===> [OK]\n");
      $fstypeOK=1;
    }
    if ($part{$p}{'fstype'} eq "ext3") {
      fdebug("fsType ");
      fdebug($part{$p}{'fstype'});
      fdebug(" ===> [OK]\n");
      $fstypeOK=1;
    }
    if ($part{$p}{'fstype'} eq "fat32") {
      fdebug("fsType ");
      fdebug($part{$p}{'fstype'});
      fdebug(" ===> [OK]\n");
      $fstypeOK=1;
    }
    if ($part{$p}{'fstype'} eq "fat16") {
      fdebug("fsType ");
      fdebug($part{$p}{'fstype'});
      fdebug(" ===> [OK]\n");
      $fstypeOK=1;
    }
    if ($part{$p}{'fstype'} eq "xfs") {
      fdebug("fsType ");
      fdebug($part{$p}{'fstype'});
      fdebug(" ===> [OK]\n");
      $fstypeOK=1;
    }
    if ($part{$p}{'fstype'} eq "ntfs") {
      fdebug("fsType ");
      fdebug($part{$p}{'fstype'});
      fdebug(" ===> [OK]\n");
      $fstypeOK=1;
    }
    if ($fstypeOK==0)  {
      my $falseFstype=$part{$p}{'fstype'};
      fdebug("error with the fstype : ");
      fdebug($part{$p}{'fstype'});
      fatalExit("\nthe filesystem type $falseFstype is not supported");
    }
    
    #test of the device used in case of a manual partitionning
    if ($method eq "manual")  {
      foreach $hd (@listHd)  {
   	if ($hd ne $deviceUsed)  {
          $varTemp=$deviceUsed;
	}
	else {
	  $varTemp=1;
	  fdebug("device ");
	  fdebug($deviceUsed);
	  fdebug(" => [OK]\n");
	  last;
	}
      }
      if ($varTemp ne "1")  {
   	fatalExit("syntax error : the device $varTemp specified in the configuration file doesnt't exist");
      }
    } 
  	
    #Test of the partition size
    if ($method eq "auto")  {
      foreach $hd (@listHd)  {
    	if (convBytes($part{$p}{'size'}, "none") > $hashobj{$hd}->sizeDisk)  {
          fdebug("WARNING : the partition size specified in the configuration file exceeds the size of a disk");  
        }
      }
    } 
    else {
      if (convBytes($part{$p}{'size'}, $part{$p}{'deviceUsed'}) > $hashobj{$deviceUsed}->sizeDisk)  {
    	$varTemp=$p;
      	fatalExit("the hard drive space is not sufficient for the partition $varTemp");
      }
      else {
        fdebug("manual partitionning, size "); 
        fdebug($part{$p}{'size'});
        fdebug(" ===> OK\n");
      }
    }
    
    #test: s'il y a deux grow sur le meme device ou en mode auto
    my $growId=0; 
    my $devId=0;
    my $oldDeviceUsed;
    my $oldGrowId=0;
    if ($method eq "auto")  {
      foreach $p (keys %part) {
	if ($part{$p}{'growto'})  {
          if ($part{$p}{'growto'} eq 'all')  {
	    $growId=1;
	  }
	  if ($part{$p}{'growto'} eq 'all' && $growId==1)  {
	    fatalExit("there cannot be two growto 'all' in the configuration file when the partitionning method is automatic");
	  }
	}
      }
    } 
    else {
      $inc=0;
      $inc2=0;
      $growId=0;
      $devId=0;
      $oldGrowId=0;
      foreach $p (keys %part) { 
	$inc2=0;
	foreach $q (keys %part) {
	  if ($inc2 != $inc)  {
	    if ($part{$p}{'deviceUsed'} eq $part{$q}{'deviceUsed'})  {
	      $devId=1;
	    }
	    else  {
	      $devId=0;
	    }
	    if (($part{$q}{'growto'}) && ($part{$p}{'growto'}))  {
	      if ($part{$p}{'growto'} eq $part{$q}{'growto'})  {
	        if ($part{$p}{'growto'} eq 'all') {
	          $growId=1;
	        }
	        else  {
	          $growId=0;
	        }
		if (($growId==1) && ($devId==1))  {
		  fatalExit("there cannot be two growto 'all' in the configuration file on the same device");
	        }
	      }
	    }
	  }
	  $inc2++
	}
        $inc++;
      }	
    }
  }

  
    
    
  #End of the tests, we have to delete the hash tables
  fdebug("there are no syntax errors detected\n");
  foreach $hd (@listHd)  {
    delete ($hashobj{$hd});
  }
  foreach $part (@listPart)  {
    delete ($hashobj{$part});
  }
  @listHd=();
  @listPart=();
}

#another testing function

#no input
#check if the partitions can fit on the disks
#return nothing but if an error occurs, the program is stopped
sub testValidConfiguration  { 
  my $part;
  my $hd;
  my $method;
  my %deviceHash=();
  my $deviceNb=0; 
  my $sizeUsedOnCurrentDisk=0;
  my $nbDisks=0;
  my $sizeMin;
  my $device;
  my $oldDevice=$hashobj{$listPart[0]}->deviceUsed;
	
  foreach $hd (@listHd)  {
    $deviceHash{$deviceNb}=$hd;
    $deviceNb++;
    $nbDisks++;
  }
  $deviceNb=0;
  if (convBytes($hashobj{$listPart[0]}->sizeMin, $hashobj{$listPart[0]}->deviceUsed) > $hashobj{$listHd[0]}->sizeDisk)  {
    fatalExit("ERROR : the first partition doesn't fit on the first disk\n");
  }
  #Test on the sizes mini, we want to know if all the partitions fit on the disks
  foreach $part (@listPart)  {
  	
    #if the sizes are in percent, this test is not necessary
    if ($hashobj{$part}->sizeMin=~m"^(\d+)%")  {
      if ($nbDisks > 1 && $hashobj{$part}->deviceUsed eq 'none')  {
        fatalExit("ERROR : the partitionning for more than one disk is not avaible if the sizes are specified in % in the configuration file");
      }
      print "WARNING : you are using size in percent, if your disks are too small, your partitions will be too small too!!\n";
      last;
    }
    $sizeMin=convBytes($hashobj{$part}->sizeMin, $hashobj{$part}->deviceUsed);
	
    #in the case of an automatic partitionning, a pre partitionning is necessary with the sizes mini
		
    #if the variable deviceNb is equal to the number of devices, that mean that there is not enough space for all the partitions
    if ($hashobj{$part}->method eq "auto")  {	
		
      if ($deviceNb == $nbDisks)  {
   	fatalExit("ERROR not enough space on the disks for partitionning, check the configuration file for more information"); 
      }       
      if (($sizeMin + $sizeUsedOnCurrentDisk) < $hashobj{$deviceHash{$deviceNb}}->sizeDisk)  {   
        $sizeUsedOnCurrentDisk+=$sizeMin;
      }
      else  {
 	$sizeUsedOnCurrentDisk=0;
  	$deviceNb++;
        redo;
      }
    }
    #manual
		
    #if the devices are specified in the configuration file, we already know on which device the tests must be done
    else  {
      $device=$hashobj{$part}->deviceUsed;
      if ($device eq $oldDevice)  {
        $sizeUsedOnCurrentDisk+=$sizeMin;
        if ($sizeUsedOnCurrentDisk > $hashobj{$device}->sizeDisk)  {
	  fatalExit("ERROR not enough space on the disk $device for partitionning, check the configuration file for more information"); 
        }
      }       
      else  {
        $sizeUsedOnCurrentDisk=0;
        $oldDevice=$device;
        redo;			  
      }
      $oldDevice=$device;
    } 
  }	
}	

sub deleteAll
{
    my $hd;
    my  $part;
	foreach $hd (@listHd)  {
		delete ($hashobj{$hd});
	}
  foreach $part (@listPart)  {
		delete ($hashobj{$part});
	}
  @listHd=();
  @listPart=();
}	

sub writeMountDeviceFile
{
	my $part;
	my @unsorted=();
	my $mountPoint;
  my $elt;
  my $nb=0;
  my $i=0;
  my $none='none';
  my $first;
  my $second;
  my $j=0;
  my $file = $opts{"f"};
	open (MOUNT,">$file") || die ("error in the opening of the file");
	
	foreach $part (@listPart)  {
	    $nb++;
	}         
  foreach $part (@listPart)  {
      if ($part=~m"^(none\d)")  {
	    	$none=($1);
	    }
			if ($part eq 'none' | $part eq $none)  {
			  next;
			}
			else  {
			  push @unsorted,$part;
		  }
  }	  
  while ($j <= $#unsorted)  { 
  	foreach $elt (@unsorted)  {         
		  if ($i != 0) { 
	              
		      if ( length($unsorted[$i]) <= length($unsorted[$i-1]) )  {
		         $first=$unsorted[$i];
		         $second=$unsorted[$i-1];
		         $unsorted[$i-1]=$first;
		         $unsorted[$i]=$second;
		      }   
		  }
      $i++;
    }
    $j++;
    $i=0;
  }
    
  #print @unsorted;
  foreach $elt (@unsorted)  {
  	print MOUNT $hashobj{$elt}->mountPoint;
		print MOUNT "     ";
		print MOUNT "/dev/";
      
	  print MOUNT $hashobj{$elt}->deviceUsed;
    print MOUNT "\n";    
  }
  close (MOUNT); 
 
 
}
#===============================================MAIN==============================================================
#There are three debug level corresponding to the three variables below
#if you want : a verbose one,  write : $debug=1
#                                      $debugLevel=1
#						   a quiet one, write      $debugLevel=0;
#the debug levels 2 and three correspond with $debugTest and $debugRes, 
#                                            if $debugTest=1 and $debugLevel=2 fdebug will print
#                                            if $debugRes=1 and $debugLevel=3 fdebug will print									
$debugTest=0;
$debug=0;
$debugRes=0;


#-----------------------------------------------------------------------------------------------------------------
#                      EXTRACTION OF THE INFORMATION FROM THE DISKS DETECTION
#-----------------------------------------------------------------------------------------------------------------
fdebug ("\n------------------------------------\n");
fdebug ("|HASH TABLE OF THE DISKS INFORMATION|\n");
fdebug ("------------------------------------\n");



fdebug ("list of devices : @listHd\n");

#-----------------------------------------------------------------------------------------------------------------
#                      EXTRACTION OF INFORMLATION FROM THE CONFIGURATION FILE DU FICHIER DE CONF
#-----------------------------------------------------------------------------------------------------------------

fdebug ("\n---------------------------------------\n");
fdebug ("|HASH TABLE OF THE PARTITION INFORMATION|\n");
fdebug ("---------------------------------------\n\n");

$debugLevel=1; 
$debugRes=1;
$debugTest=1;
$debug=1;

printusage() if exists ($opts{"h"});
exit if exists ($opts{"h"});

my $confFile='part.data';
testConfSyntax($confFile);

detectDrives();
$debugTest=0;
pushParts($confFile);



testValidConfiguration();


fdebug("\nliste des partitions : @listPart\n");


#-----------------------------------------------------------------------------------------------------------------
#                                CALCULATION OF THE PARTITIONS SIZES
#-----------------------------------------------------------------------------------------------------------------

my $eltList;
my $device;
my $deviceNb;
my $newPartSize;
my $partDone;
my $sumSizeMinNotDone=0;
my $method;
my $i=0;

foreach $eltList (@listPart) {
	$hashobj{$eltList}->partDone(0);
}
foreach $eltList (@listPart) {
  $method=$hashobj{$eltList}->method;
}



#If the disks are not specified in the configuration file, the automatic method is used
#method auto
if ($method eq 'auto') {						

	#CALCULATION ON FULL SIZE
	calcPartOnFullSize();
	
	fdebug("\ndistribution of the partitions on the full size\n");	
	foreach $eltList (@listHd) {	
	  fdebug("\nHARD DRIVES : ");
	  fdebug($hashobj{$eltList}->device);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizeDisk);
	  fdebug("\nfree space : ");
	  fdebug($hashobj{$eltList}->freeSpace);
	  fdebug("\n");
	}
  fdebug("\nPARTITIONS : \n");
  foreach $eltList (@listPart) {
	  fdebug($hashobj{$eltList}->mountPoint);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("\n");
	}
	
	#distribution of the partitions on the disks
	
	distributeSpace();
	
	fdebug("\ndistribution of the partitions on the disks ");
	fdebug("\nPARTITIONS : \n");
	foreach $eltList (@listPart) {	
	  fdebug($hashobj{$eltList}->mountPoint);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("\n");
	}
	
	#correction of the lack of size
	
	adjustSizePart();
	
	fdebug("\ncorrection of the lack of size\n");
	foreach $eltList (@listHd) {	
	  fdebug("\nHD : ");
	  fdebug($hashobj{$eltList}->device);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizeDisk);
	  fdebug("\nFree space : ");
	  fdebug($hashobj{$eltList}->freeSpace);
	  fdebug("\n");
	}
	fdebug("\nPARTITIONS : \n");
	foreach $eltList (@listPart) {	
	  fdebug($hashobj{$eltList}->mountPoint);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("  [");
	  fdebug($hashobj{$eltList}->partDone);
	  fdebug("]\n");
	}
	
	#function which indicates that all the partitions have been done
	partsOK();
	
	#optimization of the sizes and redistribution of free space available
	
	adjustSizePart();
	
	fdebug("\n\ndistribution of the space available\n");
	foreach $eltList (@listHd) {
	  fdebug("\nHD : ");
	  fdebug($hashobj{$eltList}->device);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizeDisk);
	  fdebug("\nFree space : ");
	  fdebug($hashobj{$eltList}->freeSpace);
	  fdebug("\n");
	  }
	fdebug("\nPARTITIONS : \n");
	foreach $eltList (@listPart) {	
	  fdebug($hashobj{$eltList}->mountPoint);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("\n");
	} 
		
	#conversion in sectors
	fdebug("\nConversion in secteurs : \n");
	
	partSize();
	
	#calculation of the disk size in sectors
	diskSize();
	
  foreach $eltList (@listHd)  {
	  fdebug("\nHD : ");
	  fdebug($hashobj{$eltList}->device);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizeDisk);
	  fdebug("\n");
	}
	foreach $eltList (@listPart) {
	  fdebug("Part no ");
	  fdebug($hashobj{$eltList}->partNb);
	  fdebug(" : ");
	  fdebug($hashobj{$eltList}->mountPoint);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("\n");
	}
	
	#numerotation of the partitions
	fdebug("\nmodification of the partition numbers : \n");
	setNbPart();
	
	foreach $eltList (@listPart) {
	  fdebug("Part no ");
	  fdebug($hashobj{$eltList}->partNb);
	  fdebug(" : ");
	  fdebug($hashobj{$eltList}->mountPoint);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("\n");
	}

	fdebug("\nwriting the input file of sfdisk : ");
	writePart();
	fdebug("\nwriting fstab : ");
	writeFstab();
			
}

#if the disks are specified in the configuration file the method is manual
#method manual
else {				
	
	#CALCULATION ON FULL SIZE
	calcPartOnFullSize();
	
	fdebug("\ndistribution of the partitions on full space\n");
	foreach $eltList (@listHd) {	
	  fdebug("\nHARD DRIVES : ");
	  fdebug($hashobj{$eltList}->device);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizeDisk);
	  fdebug("\nFree space : ");
	  fdebug($hashobj{$eltList}->freeSpace);
	  fdebug("\n");
	}
  fdebug("\nPARTITIONS : \n");
  foreach $eltList (@listPart) {
	  fdebug($hashobj{$eltList}->mountPoint);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("\n");
	}
			
	#correction of the lack of size
	adjustSizePart();
	
	fdebug("\ncorrection of the lack of size\n");
	foreach $eltList (@listHd) {
	  fdebug("\nHD : ");
	  fdebug($hashobj{$eltList}->device);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizeDisk);
	  fdebug("\nFree space : ");
	  fdebug($hashobj{$eltList}->freeSpace);
	  fdebug("\n");
	}
	fdebug("\nPARTITIONS : \n");
	foreach $eltList (@listPart) {	
	  fdebug( $hashobj{$eltList}->mountPoint);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("  [");
	  fdebug($hashobj{$eltList}->partDone);
	  fdebug("]\n");
	}
	#function that indicates that all the partitions have been done
	partsOK();
	
	#optimization of the sizes, distribution of the space available to the partitions
	adjustSizePart();
	
	fdebug("\n\ndistribution of the free space available to the partitions \n");
	foreach $eltList (@listHd) {
	  fdebug( "\nHD : ");
	  fdebug($hashobj{$eltList}->device);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizeDisk);
	  fdebug("\nFree space : ");
	  fdebug($hashobj{$eltList}->freeSpace);
	  fdebug("\n");
  }
	fdebug("\nPARTITIONS : \n");
	foreach $eltList (@listPart) {
	  fdebug( $hashobj{$eltList}->mountPoint);
	  fdebug( " => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("\n");
	} 
		
	fdebug("\nConversion in sectors : \n");
	partSize();
	
	diskSize();
	
	foreach $eltList (@listPart) {              
	  fdebug($hashobj{$eltList}->mountPoint);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("\n");
	}
	
	fdebug("\nnumerotation of the partitions : \n");
	setNbPart();
	foreach $eltList (@listPart) {	              
	  fdebug($hashobj{$eltList}->mountPoint);
	  fdebug(" => ");
	  fdebug($hashobj{$eltList}->sizePart);
	  fdebug(" on the device ");
	  fdebug($hashobj{$eltList}->deviceUsed);
	  fdebug("\n");
	}
	        
	fdebug("\nwriting the input file of sfdisk : ");
	writePart();
	
	fdebug("\nwriting fstab : ");
	writeFstab();
					
}

writeMountDeviceFile() if exists ($opts{"f"});
deleteAll();

#A FAIRE
#checker les fonctions inutiles
1;
#================================================================================================================
