!define UPGRADEDLL_NOREGISTER
!define VERSION "0.0.7"
!include "UpgradeDLL.nsh"

Name "XBT Client ${VERSION}"
Outfile "XBT_Client-${VERSION}.exe"
InstallDir "$PROGRAMFILES\XBT"
Page directory
Page instfiles
Section "Install"
	SetShellVarContext all
	SetOutPath "$INSTDIR"
	!insertmacro UpgradeDLL "zlib1.dll" "$SYSDIR\zlib1.dll" "$SYSDIR"

	File release\*.exe
	WriteUninstaller "$INSTDIR\Uninstall.exe"
	CreateShortCut "$SMPROGRAMS\XBT Client.lnk" "$INSTDIR\XBT Client.exe"
	WriteRegStr HKCR ".torrent" "" "bittorrent"
	WriteRegStr HKCR ".torrent" "Content Type" "application/x-bittorrent"
	WriteRegStr HKCR "MIME\Database\Content Type\application/x-bittorrent" "Extension" ".torrent"
	WriteRegStr HKCR "bittorrent" "" "Torrent"
	WriteRegBin HKCR "bittorrent" "EditFlags" 00000100
	WriteRegStr HKCR "bittorrent\shell" "" "open"
	WriteRegStr HKCR "bittorrent\shell\open\command" "" `"$INSTDIR\XBT Client.exe" "%1"`
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client" "DisplayName" "XBT Client ${VERSION}"
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client" "UninstallString" '"$INSTDIR\Uninstall.exe"'
	WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client" "NoModify" 1
SectionEnd

Section "Uninstall"
	DeleteRegKey HKCR ".torrent"
	DeleteRegKey HKCR "MIME\Database\Content Type\application/x-bittorrent"
	DeleteRegKey HKCR "bittorrent"
	DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client"
	RMDir /r "$INSTDIR"
SectionEnd
