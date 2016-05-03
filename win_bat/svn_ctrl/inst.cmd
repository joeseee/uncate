@echo off

set SVNSERVE=\"C:\Program Files\TortoiseSVN\bin\svnserve.exe\"
set ROOTPATH=C:\Users\pdczxj\svn_repo

sc create svnserve binpath= "%SVNSERVE% --service --root %ROOTPATH%" displayname= "Subversion Server" depend= Tcpip start= auto

pause