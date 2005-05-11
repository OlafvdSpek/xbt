!define UPGRADEDLL_NOREGISTER
!define VERSION "0.5.3"
!include "UpgradeDLL.nsh"

Name "XBT Client Backend ${VERSION}"
Outfile "XBT_Client_Backend-${VERSION}.exe"
InstallDir "$PROGRAMFILES\XBT\Client Backend"
InstallDirRegKey HKLM "Software\XBT\Client Backend" "InstallDir"
Page directory
Page instfiles
UninstPage uninstConfirm
UninstPage instfiles

Section "Install"
	SetOutPath "$INSTDIR"
	!insertmacro UpgradeDLL "zlib1.dll" "$SYSDIR\zlib1.dll" "$SYSDIR"

	Delete "$INSTDIR\XBT Client Backend.exe"
	Delete "$INSTDIR\XBT Client Backend Old.exe"
	Rename "$INSTDIR\XBT Client Backend.exe" "$INSTDIR\XBT Client Backend Old.exe"
	File release\*.exe
	SetOutPath "$INSTDIR\htdocs"
	File "..\XBT Client\htdocs\*"
	ExecWait "$INSTDIR\XBT Client Backend.exe --install"
	ExecWait 'net start "XBT Client"'
	WriteUninstaller "$INSTDIR\Uninstall.exe"
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client Backend" "DisplayName" "XBT Client Backend ${VERSION}"
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client Backend" "UninstallString" '"$INSTDIR\Uninstall.exe"'
	WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client Backend" "NoModify" 1
	WriteRegStr HKLM "Software\XBT\Client Backend" "InstallDir" "$INSTDIR"
SectionEnd

Section "Uninstall"
	ExecWait 'net stop "XBT Client"'
	ExecWait "$INSTDIR\XBT Client Backend.exe --uninstall"
	DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client Backend"
	DeleteRegKey HKLM "Software\XBT\Client Backend"
	DeleteRegKey /ifempty HKLM "Software\XBT"
	RMDir /r "$INSTDIR"
	RMDir "$PROGRAMFILES\XBT"
SectionEnd
