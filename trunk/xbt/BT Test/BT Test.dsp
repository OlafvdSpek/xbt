# Microsoft Developer Studio Project File - Name="BT Test" - Package Owner=<4>
# Microsoft Developer Studio Generated Build File, Format Version 6.00
# ** DO NOT EDIT **

# TARGTYPE "Win32 (x86) Console Application" 0x0103

CFG=BT Test - Win32 Debug
!MESSAGE This is not a valid makefile. To build this project using NMAKE,
!MESSAGE use the Export Makefile command and run
!MESSAGE 
!MESSAGE NMAKE /f "BT Test.mak".
!MESSAGE 
!MESSAGE You can specify a configuration when running NMAKE
!MESSAGE by defining the macro CFG on the command line. For example:
!MESSAGE 
!MESSAGE NMAKE /f "BT Test.mak" CFG="BT Test - Win32 Debug"
!MESSAGE 
!MESSAGE Possible choices for configuration are:
!MESSAGE 
!MESSAGE "BT Test - Win32 Release" (based on "Win32 (x86) Console Application")
!MESSAGE "BT Test - Win32 Debug" (based on "Win32 (x86) Console Application")
!MESSAGE 

# Begin Project
# PROP AllowPerConfigDependencies 0
# PROP Scc_ProjName ""
# PROP Scc_LocalPath ""
CPP=cl.exe
RSC=rc.exe

!IF  "$(CFG)" == "BT Test - Win32 Release"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "Release"
# PROP BASE Intermediate_Dir "Release"
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "Release"
# PROP Intermediate_Dir "Release"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /GX /O2 /D "WIN32" /D "NDEBUG" /D "_CONSOLE" /D "_MBCS" /Yu"stdafx.h" /FD /c
# ADD CPP /nologo /MD /W3 /GX /O2 /I "..\misc" /D "WIN32" /D "NDEBUG" /D "_CONSOLE" /D "_MBCS" /Yu"stdafx.h" /FD /c
# ADD BASE RSC /l 0x413 /d "NDEBUG"
# ADD RSC /l 0x413 /d "NDEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /subsystem:console /machine:I386
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib zlib.lib /nologo /subsystem:console /machine:I386 /out:"Release/XBT Client Backend.exe"

!ELSEIF  "$(CFG)" == "BT Test - Win32 Debug"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 1
# PROP BASE Output_Dir "Debug"
# PROP BASE Intermediate_Dir "Debug"
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 1
# PROP Output_Dir "Debug"
# PROP Intermediate_Dir "Debug"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /W3 /Gm /GX /ZI /Od /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /Yu"stdafx.h" /FD /GZ /c
# ADD CPP /nologo /MDd /W3 /Gm /GX /ZI /Od /I "..\misc" /D "WIN32" /D "_DEBUG" /D "_CONSOLE" /D "_MBCS" /Yu"stdafx.h" /FD /GZ /c
# ADD BASE RSC /l 0x413 /d "_DEBUG"
# ADD RSC /l 0x413 /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /subsystem:console /debug /machine:I386 /pdbtype:sept
# ADD LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib zlib.lib /nologo /subsystem:console /debug /machine:I386 /out:"Debug/XBT Client Backend.exe" /pdbtype:sept

!ENDIF 

# Begin Target

# Name "BT Test - Win32 Release"
# Name "BT Test - Win32 Debug"
# Begin Group "Source Files"

# PROP Default_Filter "cpp;c;cxx;rc;def;r;odl;idl;hpj;bat"
# Begin Source File

SOURCE=..\misc\alerts.cpp
# End Source File
# Begin Source File

SOURCE=".\BT Test.cpp"
# End Source File
# Begin Source File

SOURCE=.\bt_admin_link.cpp
# End Source File
# Begin Source File

SOURCE=.\bt_file.cpp
# End Source File
# Begin Source File

SOURCE=.\bt_file_data.cpp
# End Source File
# Begin Source File

SOURCE=.\bt_hasher.cpp
# End Source File
# Begin Source File

SOURCE=.\bt_link.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\bt_logger.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\bt_misc.cpp
# End Source File
# Begin Source File

SOURCE=.\bt_peer_data.cpp
# End Source File
# Begin Source File

SOURCE=.\bt_peer_link.cpp
# End Source File
# Begin Source File

SOURCE=.\bt_piece.cpp
# End Source File
# Begin Source File

SOURCE=.\bt_pl_write_data.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\bt_tracker_account.cpp
# End Source File
# Begin Source File

SOURCE=.\bt_tracker_link.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\bt_tracker_url.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\bvalue.cpp
# End Source File
# Begin Source File

SOURCE=.\config.cpp
# End Source File
# Begin Source File

SOURCE=.\data_counter.cpp
# End Source File
# Begin Source File

SOURCE=.\merkle_tree.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\windows\nt_service.cpp
# End Source File
# Begin Source File

SOURCE=.\profiles.cpp
# End Source File
# Begin Source File

SOURCE=.\ring_buffer.cpp
# End Source File
# Begin Source File

SOURCE=.\scheduler.cpp
# End Source File
# Begin Source File

SOURCE=.\server.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\sha1.cpp
# End Source File
# Begin Source File

SOURCE=..\..\misc\socket.cpp
# End Source File
# Begin Source File

SOURCE=.\StdAfx.cpp
# ADD CPP /Yc"stdafx.h"
# End Source File
# Begin Source File

SOURCE=.\udp_tracker.cpp
# End Source File
# Begin Source File

SOURCE=..\..\xf2\misc\virtual_binary.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\xcc_z.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\xif_key.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\xif_key_r.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\xif_value.cpp
# End Source File
# End Group
# Begin Group "Header Files"

# PROP Default_Filter "h;hpp;hxx;hm;inl"
# Begin Source File

SOURCE=.\bt_admin_link.h
# End Source File
# Begin Source File

SOURCE=.\bt_file.h
# End Source File
# Begin Source File

SOURCE=.\bt_file_data.h
# End Source File
# Begin Source File

SOURCE=.\bt_hasher.h
# End Source File
# Begin Source File

SOURCE=.\bt_link.h
# End Source File
# Begin Source File

SOURCE=..\misc\bt_misc.h
# End Source File
# Begin Source File

SOURCE=.\bt_peer_data.h
# End Source File
# Begin Source File

SOURCE=.\bt_peer_link.h
# End Source File
# Begin Source File

SOURCE=.\bt_piece.h
# End Source File
# Begin Source File

SOURCE=.\bt_pl_write_data.h
# End Source File
# Begin Source File

SOURCE=..\misc\bt_tracker_account.h
# End Source File
# Begin Source File

SOURCE=.\bt_tracker_link.h
# End Source File
# Begin Source File

SOURCE=..\misc\bvalue.h
# End Source File
# Begin Source File

SOURCE=.\config.h
# End Source File
# Begin Source File

SOURCE=.\data_counter.h
# End Source File
# Begin Source File

SOURCE=.\merkle_tree.h
# End Source File
# Begin Source File

SOURCE=.\profiles.h
# End Source File
# Begin Source File

SOURCE=.\ring_buffer.h
# End Source File
# Begin Source File

SOURCE=.\scheduler.h
# End Source File
# Begin Source File

SOURCE=.\server.h
# End Source File
# Begin Source File

SOURCE=.\StdAfx.h
# End Source File
# Begin Source File

SOURCE=..\misc\stream_writer.h
# End Source File
# Begin Source File

SOURCE=.\udp_tracker.h
# End Source File
# End Group
# Begin Group "Resource Files"

# PROP Default_Filter "ico;cur;bmp;dlg;rc2;rct;bin;rgs;gif;jpg;jpeg;jpe"
# End Group
# End Target
# End Project
