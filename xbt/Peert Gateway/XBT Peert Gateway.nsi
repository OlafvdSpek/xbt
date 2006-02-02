!define UPGRADEDLL_NOREGISTER
!define VERSION "0.6.7"
!include "UpgradeDLL.nsh"

Name "XBT Peert Gateway ${VERSION}"
Outfile "XBT_Peert_Gateway-${VERSION}.exe"
InstallDir "$PROGRAMFILES\XBT\Peert Gateway"
InstallDirRegKey HKLM "Software\XBT\Peert Gateway" "InstallDir"
Page directory
Page instfiles
UninstPage uninstConfirm
UninstPage instfiles

Section "Install"
	SetShellVarContext all
	SetOutPath "$INSTDIR"
	!insertmacro UpgradeDLL "zlib1.dll" "$SYSDIR\zlib1.dll" "$SYSDIR"

	Delete "$INSTDIR\XBT Client Backend.exe"
	Delete "$INSTDIR\XBT Client Backend Old.exe"
	Rename "$INSTDIR\XBT Client Backend.exe" "$INSTDIR\XBT Client Backend Old.exe"
	ExecWait 'net stop "XBT Peert Gateway"'
	Delete "$INSTDIR\XBT Peert Gateway.exe"
	Delete "$INSTDIR\XBT Peert Gateway Old.exe"
	Rename "$INSTDIR\XBT Peert Gateway.exe" "$INSTDIR\XBT Peert Gateway Old.exe"
	File "..\BT Test\release\XBT Client Backend.exe"
	File "release\XBT Peert Gateway.exe"
	File "VLC\activex\axpeertvlc.dll"
	File "VLC\plugins\"
	File "libcurl.dll"
	Exec "$INSTDIR\XBT Client Backend.exe --install"
	Exec "$INSTDIR\XBT Peert Gateway.exe --install"
	Exec 'net start "XBT Client"'
	Exec 'net start "XBT Peert Gateway"'
	Exec 'regsvr32 "$INSTDIR\axpeertvlc.dll"'
	WriteUninstaller "$INSTDIR\Uninstall.exe"
	CreateShortCut "$SMPROGRAMS\Peert.lnk" "http://peert.com/"
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Peert Gateway" "DisplayName" "XBT Peert Gateway ${VERSION}"
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Peert Gateway" "UninstallString" '"$INSTDIR\Uninstall.exe"'
	WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Peert Gateway" "NoModify" 1
	WriteRegStr HKLM "Software\Peert" "VLC" "$INSTDIR"
	WriteRegStr HKLM "Software\XBT\Peert Gateway" "InstallDir" "$INSTDIR"
SectionEnd

Section "Uninstall"
	SetShellVarContext all
	ExecWait 'net stop "XBT Client"'
	ExecWait 'net stop "XBT Peert Gateway"'
	ExecWait "$INSTDIR\XBT Client Backend.exe --uninstall"
	ExecWait "$INSTDIR\XBT Peert Gateway.exe --uninstall"
	Delete "$SMPROGRAMS\Peert.lnk"
	DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Peert Gateway"
	DeleteRegKey HKLM "Software\Peert"
	DeleteRegKey HKLM "Software\XBT\Peert Gateway"
	DeleteRegKey /ifempty HKLM "Software\XBT"
	RMDir /r "$INSTDIR"
	RMDir "$PROGRAMFILES\XBT"
SectionEnd
