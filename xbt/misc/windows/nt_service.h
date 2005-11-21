#if !defined(AFX_NT_SERVICE_H__42D4E866_3774_4D76_B3A4_67E38CE5934E__INCLUDED_)
#define AFX_NT_SERVICE_H__42D4E866_3774_4D76_B3A4_67E38CE5934E__INCLUDED_

#if _MSC_VER > 1000
#pragma once
#endif // _MSC_VER > 1000

int nt_service_install(const char* name);
int nt_service_uninstall(const char* name);

#endif // !defined(AFX_NT_SERVICE_H__42D4E866_3774_4D76_B3A4_67E38CE5934E__INCLUDED_)
