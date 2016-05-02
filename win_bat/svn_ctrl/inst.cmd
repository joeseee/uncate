@echo off
sc create svnserve binpath= "\"C:\Program Files\Subversion\bin\svnserve.exe\" --service -r D:\svn_repo" displayname= "Subversion Server" depend= Tcpip start= auto

pause