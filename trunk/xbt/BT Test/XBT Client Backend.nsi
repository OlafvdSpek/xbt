!define UPGRADEDLL_NOREGISTER
!define VERSION "0.3.8"
!include "UpgradeDLL.nsh"

Name "XBT Client Backend ${VERSION}"
Outfile "XBT_Client_Backend-${VERSION}.exe"
InstallDir "$PROGRAMFILES\XBT\Client Backend"
Page directory
Page instfiles
UninstPage uninstConfirm
UninstPage instfiles
Section "Install"
	SetOutPath "$INSTDIR"
	!insertmacro UpgradeDLL "zlib1.dll" "$SYSDIR\zlib1.dll" "$SYSDIR"

	File release\*.exe
	SetOutPath "$INSTDIR\htdocs"
	File "..\XBT Client\htdocs\*"
	Exec "$INSTDIR\XBT Client Backend.exe --install"
	WriteUninstaller "$INSTDIR\Uninstall.exe"
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client Backend" "DisplayName" "XBT Client Backend ${VERSION}"
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client Backend" "UninstallString" '"$INSTDIR\Uninstall.exe"'
	WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client Backend" "NoModify" 1
SectionEnd

Section "Uninstall"
	Exec "$INSTDIR\XBT Client Backend.exe --uninstall"
	DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client Backend"
	RMDir /r "$INSTDIR"
	RMDir "$PROGRAMFILES\XBT"
SectionEnd
