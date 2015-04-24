VER | FINDSTR /L "5.1." > NUL
IF %ERRORLEVEL% EQU 0 goto xp

NET USE H: /DELETE /YES 
NET USE I: /DELETE /YES 
NET USE L: /DELETE /YES 
if exist X:\ NET USE X: /DELETE /YES 
if exist Y:\ NET USE Y: /DELETE /YES 

:xp 

if not exist I:\ net use I: \\###netbios_name###\Docs
if not exist H:\ net use H: \\###netbios_name###\Classes
if not exist L:\ net use L: \\###netbios_name###\Progs
echo %USERNAME
if %USERNAME% == admin goto admin
goto imp



:admin

if not exist X:\ net use X: \\###netbios_name###\admhomes
if not exist Y:\ net use Y: \\###netbios_name###\admse3

pause
:imp
start /B cscript //B %SYSTEMROOT%\Printers.vbs



