!define VERSION "0.7.4"

Name "XBT Client ${VERSION}"
Outfile "XBT_Client-${VERSION}.exe"
InstallDir "$PROGRAMFILES\XBT\Client"
InstallDirRegKey HKLM "Software\XBT\Client" "InstallDir"
XPStyle on
Page directory
Page instfiles
UninstPage uninstConfirm
UninstPage instfiles

Section "Install"
	SetShellVarContext all
	SetOutPath "$INSTDIR"

	Delete "$INSTDIR\XBT Client.exe"
	Delete "$INSTDIR\XBT Client Old.exe"
	Rename "$INSTDIR\XBT Client.exe" "$INSTDIR\XBT Client Old.exe"
	File release\*.exe
	SetOutPath "$INSTDIR\htdocs"
	File htdocs\*
	WriteUninstaller "$INSTDIR\Uninstall.exe"
	CreateShortCut "$SMPROGRAMS\XBT Client.lnk" "$INSTDIR\XBT Client.exe"
	WriteRegStr HKCR ".torrent" "" "XBT Client"
	WriteRegStr HKCR ".torrent" "Content Type" "application/x-bittorrent"
	WriteRegStr HKCR "MIME\Database\Content Type\application/x-bittorrent" "Extension" ".torrent"
	WriteRegStr HKCR "bittorrent" "" "Torrent"
	WriteRegBin HKCR "bittorrent" "EditFlags" 00000100
	WriteRegStr HKCR "bittorrent\shell" "" "open"
	WriteRegStr HKCR "bittorrent\shell\open\command" "" `"$INSTDIR\XBT Client.exe" "%1"`
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client" "DisplayName" "XBT Client ${VERSION}"
	WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client" "UninstallString" '"$INSTDIR\Uninstall.exe"'
	WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client" "NoModify" 1
	WriteRegStr HKLM "Software\XBT\Client" "InstallDir" "$INSTDIR"
SectionEnd

Section "Uninstall"
	SetShellVarContext all
	Delete "$SMPROGRAMS\XBT Client.lnk"
	DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\XBT Client"
	DeleteRegKey HKLM "Software\XBT\Client"
	DeleteRegKey /ifempty HKLM "Software\XBT"
	RMDir /r "$PROGRAMFILES\XBT\Client"
	RMDir "$PROGRAMFILES\XBT"
SectionEnd
