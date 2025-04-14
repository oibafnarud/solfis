@echo off
setlocal enabledelayedexpansion

:: Solicitar ruta al usuario
set /p "source_path=Ingrese la ruta a analizar (ejemplo C:\proyecto): "

:: Verificar si la ruta existe
if not exist "%source_path%" (
    echo La ruta no existe.
    goto :eof
)

:: Crear archivo de salida
set "output_file=%~dp0estructura_directorios.txt"
echo Estructura de directorios y archivos (excluyendo node_modules y .next) > "%output_file%"
echo Ruta analizada: %source_path% >> "%output_file%"
echo Fecha: %date% %time% >> "%output_file%"
echo. >> "%output_file%"

:: Contadores
set "total_folders=0"
set "total_files=0"
set "total_size=0"
set "excluded_folders=0"
set "excluded_files=0"

echo Analizando estructura, por favor espere...
echo.

:: Listar todos los directorios y archivos
echo Directorios y Archivos: >> "%output_file%"
echo ==================== >> "%output_file%"

for /f "tokens=*" %%G in ('dir /s /b /a "%source_path%"') do (
    set "item=%%G"
    set "relative_path=!item:%source_path%=!"
    
    :: Verificar si el path contiene node_modules o .next
    echo !relative_path! | findstr /i "\node_modules\ \.next\" > nul
    if errorlevel 1 (
        if exist "%%G\*" (
            echo [DIR] !relative_path! >> "%output_file%"
            set /a "total_folders+=1"
        ) else (
            :: Obtener tamaño del archivo
            for %%F in ("%%G") do set "file_size=%%~zF"
            set /a "total_files+=1"
            set /a "total_size+=file_size"
            echo [FILE] !relative_path! ^(%%~zF bytes^) >> "%output_file%"
        )
    ) else (
        if exist "%%G\*" (
            set /a "excluded_folders+=1"
        ) else (
            set /a "excluded_files+=1"
        )
    )
)

echo. >> "%output_file%"
echo Resumen: >> "%output_file%"
echo ======== >> "%output_file%"
echo Total de carpetas (excluyendo node_modules y .next): %total_folders% >> "%output_file%"
echo Total de archivos (excluyendo node_modules y .next): %total_files% >> "%output_file%"
echo Tamaño total (excluyendo node_modules y .next): %total_size% bytes >> "%output_file%"
echo Carpetas excluidas (node_modules y .next): %excluded_folders% >> "%output_file%"
echo Archivos excluidos (node_modules y .next): %excluded_files% >> "%output_file%"

echo Análisis completado.
echo Se ha generado el archivo: %output_file%
echo.
echo Resumen:
echo - Total de carpetas: %total_folders%
echo - Total de archivos: %total_files%
echo - Tamaño total: %total_size% bytes
echo - Elementos excluidos: %excluded_folders% carpetas y %excluded_files% archivos
echo.

pause