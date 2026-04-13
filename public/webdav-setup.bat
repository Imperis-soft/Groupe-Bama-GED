@echo off
echo ============================================
echo  Configuration WebDAV - Groupe Bama GED
echo ============================================
echo.

REM Supprimer le lecteur existant si present
net use Z: /delete /y 2>nul

REM Mapper le lecteur WebDAV
net use Z: http://192.168.1.246/webdav /persistent:yes

if %errorlevel% == 0 (
    echo.
    echo [OK] Lecteur Z: mappe avec succes !
    echo Vous pouvez maintenant ouvrir les documents Word directement.
) else (
    echo.
    echo [ERREUR] Impossible de mapper le lecteur.
    echo Verifiez que le serveur est accessible.
)

pause
