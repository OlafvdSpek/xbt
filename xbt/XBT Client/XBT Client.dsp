# Microsoft Developer Studio Project File - Name="XBT Client" - Package Owner=<4>
# Microsoft Developer Studio Generated Build File, Format Version 6.00
# ** DO NOT EDIT **

# TARGTYPE "Win32 (x86) Application" 0x0101

CFG=XBT Client - Win32 Debug
!MESSAGE This is not a valid makefile. To build this project using NMAKE,
!MESSAGE use the Export Makefile command and run
!MESSAGE 
!MESSAGE NMAKE /f "XBT Client.mak".
!MESSAGE 
!MESSAGE You can specify a configuration when running NMAKE
!MESSAGE by defining the macro CFG on the command line. For example:
!MESSAGE 
!MESSAGE NMAKE /f "XBT Client.mak" CFG="XBT Client - Win32 Debug"
!MESSAGE 
!MESSAGE Possible choices for configuration are:
!MESSAGE 
!MESSAGE "XBT Client - Win32 Release" (based on "Win32 (x86) Application")
!MESSAGE "XBT Client - Win32 Debug" (based on "Win32 (x86) Application")
!MESSAGE 

# Begin Project
# PROP AllowPerConfigDependencies 0
# PROP Scc_ProjName ""
# PROP Scc_LocalPath ""
CPP=cl.exe
MTL=midl.exe
RSC=rc.exe

!IF  "$(CFG)" == "XBT Client - Win32 Release"

# PROP BASE Use_MFC 6
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "Release"
# PROP BASE Intermediate_Dir "Release"
# PROP BASE Target_Dir ""
# PROP Use_MFC 6
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "Release"
# PROP Intermediate_Dir "Release"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MD /W3 /GX /O2 /D "WIN32" /D "NDEBUG" /D "_WINDOWS" /D "_AFXDLL" /Yu"stdafx.h" /FD /c
# ADD CPP /nologo /MD /W3 /GX /O2 /I "..\misc" /D "WIN32" /D "NDEBUG" /D "_WINDOWS" /D "_AFXDLL" /D "_MBCS" /Yu"stdafx.h" /FD /c
# ADD BASE MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x413 /d "NDEBUG" /d "_AFXDLL"
# ADD RSC /l 0x413 /d "NDEBUG" /d "_AFXDLL"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 /nologo /subsystem:windows /machine:I386
# ADD LINK32 zdll.lib /nologo /subsystem:windows /machine:I386

!ELSEIF  "$(CFG)" == "XBT Client - Win32 Debug"

# PROP BASE Use_MFC 6
# PROP BASE Use_Debug_Libraries 1
# PROP BASE Output_Dir "Debug"
# PROP BASE Intermediate_Dir "Debug"
# PROP BASE Target_Dir ""
# PROP Use_MFC 6
# PROP Use_Debug_Libraries 1
# PROP Output_Dir "Debug"
# PROP Intermediate_Dir "Debug"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MDd /W3 /Gm /GX /ZI /Od /D "WIN32" /D "_DEBUG" /D "_WINDOWS" /D "_AFXDLL" /Yu"stdafx.h" /FD /GZ /c
# ADD CPP /nologo /MDd /W3 /Gm /GX /ZI /Od /I "..\misc" /D "WIN32" /D "_DEBUG" /D "_WINDOWS" /D "_AFXDLL" /D "_MBCS" /Yu"stdafx.h" /FD /GZ /c
# ADD BASE MTL /nologo /D "_DEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "_DEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x413 /d "_DEBUG" /d "_AFXDLL"
# ADD RSC /l 0x413 /d "_DEBUG" /d "_AFXDLL"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 /nologo /subsystem:windows /debug /machine:I386 /pdbtype:sept
# ADD LINK32 zdll.lib /nologo /subsystem:windows /debug /machine:I386 /pdbtype:sept

!ENDIF 

# Begin Target

# Name "XBT Client - Win32 Release"
# Name "XBT Client - Win32 Debug"
# Begin Group "Source Files"

# PROP Default_Filter "cpp;c;cxx;rc;def;r;odl;idl;hpj;bat"
# Begin Source File

SOURCE=..\misc\alerts.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\windows\browse_for_directory.cpp
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_admin_link.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_file.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_file_data.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_hasher.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_link.cpp"
# End Source File
# Begin Source File

SOURCE=..\misc\bt_logger.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\bt_misc.cpp
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_peer_data.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_peer_link.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_piece.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_piece_data.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_pl_write_data.cpp"
# End Source File
# Begin Source File

SOURCE=..\misc\bt_torrent.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\bt_tracker_account.cpp
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_tracker_link.cpp"
# End Source File
# Begin Source File

SOURCE=..\misc\bt_tracker_url.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\bvalue.cpp
# End Source File
# Begin Source File

SOURCE="..\BT Test\config.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\data_counter.cpp"
# End Source File
# Begin Source File

SOURCE=.\dlg_about.cpp
# End Source File
# Begin Source File

SOURCE=.\dlg_make_torrent.cpp
# End Source File
# Begin Source File

SOURCE=.\dlg_options.cpp
# End Source File
# Begin Source File

SOURCE=.\dlg_profile.cpp
# End Source File
# Begin Source File

SOURCE=.\dlg_profiles.cpp
# End Source File
# Begin Source File

SOURCE=.\dlg_scheduler.cpp
# End Source File
# Begin Source File

SOURCE=.\dlg_scheduler_entry.cpp
# End Source File
# Begin Source File

SOURCE=.\dlg_torrent_options.cpp
# End Source File
# Begin Source File

SOURCE=.\dlg_tracker.cpp
# End Source File
# Begin Source File

SOURCE=.\dlg_trackers.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\windows\ETSLayout.cpp
# End Source File
# Begin Source File

SOURCE=.\ListCtrlEx.cpp
# End Source File
# Begin Source File

SOURCE="..\BT Test\merkle_tree.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\profiles.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\ring_buffer.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\scheduler.cpp"
# End Source File
# Begin Source File

SOURCE="..\BT Test\server.cpp"
# End Source File
# Begin Source File

SOURCE=..\misc\sha1.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\socket.cpp
# End Source File
# Begin Source File

SOURCE=.\StdAfx.cpp
# ADD CPP /Yc"stdafx.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\udp_tracker.cpp"
# End Source File
# Begin Source File

SOURCE=.\URLStatic.cpp
# End Source File
# Begin Source File

SOURCE=..\misc\virtual_binary.cpp
# End Source File
# Begin Source File

SOURCE=".\XBT Client.cpp"
# End Source File
# Begin Source File

SOURCE=".\XBT Client.rc"
# End Source File
# Begin Source File

SOURCE=".\XBT ClientDlg.cpp"
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

SOURCE=..\misc\alerts.h
# End Source File
# Begin Source File

SOURCE=..\misc\windows\browse_for_directory.h
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_admin_link.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_file.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_file_data.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_hasher.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_link.h"
# End Source File
# Begin Source File

SOURCE=..\misc\bt_logger.h
# End Source File
# Begin Source File

SOURCE=..\misc\bt_misc.h
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_peer_data.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_peer_link.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_piece.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_piece_data.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_pl_write_data.h"
# End Source File
# Begin Source File

SOURCE=..\misc\bt_strings.h
# End Source File
# Begin Source File

SOURCE=..\misc\bt_torrent.h
# End Source File
# Begin Source File

SOURCE=..\misc\bt_tracker_account.h
# End Source File
# Begin Source File

SOURCE="..\BT Test\bt_tracker_link.h"
# End Source File
# Begin Source File

SOURCE=..\misc\bt_tracker_url.h
# End Source File
# Begin Source File

SOURCE="..\BT Test\config.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\data_counter.h"
# End Source File
# Begin Source File

SOURCE=.\dlg_about.h
# End Source File
# Begin Source File

SOURCE=.\dlg_files.h
# End Source File
# Begin Source File

SOURCE=.\dlg_make_torrent.h
# End Source File
# Begin Source File

SOURCE=.\dlg_options.h
# End Source File
# Begin Source File

SOURCE=.\dlg_profile.h
# End Source File
# Begin Source File

SOURCE=.\dlg_profiles.h
# End Source File
# Begin Source File

SOURCE=.\dlg_scheduler.h
# End Source File
# Begin Source File

SOURCE=.\dlg_scheduler_entry.h
# End Source File
# Begin Source File

SOURCE=.\dlg_torrent.h
# End Source File
# Begin Source File

SOURCE=.\dlg_torrent_options.h
# End Source File
# Begin Source File

SOURCE=.\dlg_tracker.h
# End Source File
# Begin Source File

SOURCE=.\dlg_trackers.h
# End Source File
# Begin Source File

SOURCE=.\ListCtrlEx.h
# End Source File
# Begin Source File

SOURCE="..\BT Test\merkle_tree.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\profiles.h"
# End Source File
# Begin Source File

SOURCE=.\Resource.h
# End Source File
# Begin Source File

SOURCE="..\BT Test\ring_buffer.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\scheduler.h"
# End Source File
# Begin Source File

SOURCE="..\BT Test\server.h"
# End Source File
# Begin Source File

SOURCE=.\StdAfx.h
# End Source File
# Begin Source File

SOURCE=..\misc\stream_reader.h
# End Source File
# Begin Source File

SOURCE=..\misc\stream_writer.h
# End Source File
# Begin Source File

SOURCE="..\BT Test\udp_tracker.h"
# End Source File
# Begin Source File

SOURCE=.\URLStatic.h
# End Source File
# Begin Source File

SOURCE=".\XBT Client.h"
# End Source File
# Begin Source File

SOURCE=".\XBT ClientDlg.h"
# End Source File
# End Group
# Begin Group "Resource Files"

# PROP Default_Filter "ico;cur;bmp;dlg;rc2;rct;bin;rgs;gif;jpg;jpeg;jpe"
# Begin Source File

SOURCE=".\res\XBT Client.ico"
# End Source File
# Begin Source File

SOURCE=".\res\XBT Client.rc2"
# End Source File
# End Group
# End Target
# End Project
# Section XBT Client : {72ADFD6C-2C39-11D0-9903-00A0C91BC942}
# 	1:26:CG_IDR_POPUP_XBTCLIENT_DLG:104
# End Section
