#include "stdafx.h"
#include "browse_for_directory.h"

int browse_for_directory(HWND hWnd, const string& title, string& directory)
{
	BROWSEINFO bi;
	ZeroMemory(&bi, sizeof(BROWSEINFO));
	bi.hwndOwner = hWnd;
	bi.lpszTitle = title.c_str();
	bi.ulFlags = BIF_NEWDIALOGSTYLE | BIF_RETURNONLYFSDIRS;
	ITEMIDLIST* idl = SHBrowseForFolder(&bi);
	if (!idl)
		return 1;
	char path[MAX_PATH];
	if (!SHGetPathFromIDList(idl, path))
		*path = 0;
	LPMALLOC lpm;
	if (SHGetMalloc(&lpm) == NOERROR)
		lpm->Free(idl);
	if (!*path)
		return 1;
	directory = path;
	return 0;
}