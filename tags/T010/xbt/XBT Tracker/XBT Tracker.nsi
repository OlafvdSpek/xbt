!define UPGRADEDLL_NOREGISTER
!define VERSION "0.1.0"
!include "UpgradeDLL.nsh"

Name "XBT Tracker ${VERSION}"
Outfile "XBT_Tracker-${VERSION}.exe"
InstallDir "$PROGRAMFILES\XBT\Tracker"
Page directory
Page instfiles
Section "Install"
	SetShellVarContext all
	SetOutPath "$INSTDIR"
	!insertmacro UpgradeDLL "zlib1.dll" "$SYSDIR\zlib1.dll" "$SYSDIR"

	File release\*.exe
	File xbt.css
	File xbt_files.php
	File xbt_tracker.conf.default
	File xbt_tracker.sql
	SetOverwrite off
	File /oname=xbt_tracker.conf xbt_tracker.conf.default
	WriteUninstaller "$INSTDIR\Uninstall.exe"
	CreateShortCut "$SMPROGRAMS\XBT Tracker.lnk" "$INSTDIR\XBT Tracker.exe"
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Tracker" "DisplayName" "XBT Tracker ${VERSION}"
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Tracker" "UninstallString" '"$INSTDIR\Uninstall.exe"'
	WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Tracker" "NoModify" 1
SectionEnd

Section "Uninstall"
	SetShellVarContext all
	Delete "$SMPROGRAMS\XBT Tracker.lnk"
	RMDir /r "$INSTDIR"
	RMDir "$PROGRAMFILES\XBT"
SectionEnd
