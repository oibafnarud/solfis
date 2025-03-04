@echo off
setlocal enabledelayedexpansion

:: Configuración de codificación para caracteres especiales
chcp 65001 > nul

:: Nombre del archivo de salida
set "output_file=estructura_carpeta.txt"

:: Eliminar archivo de salida si existe
if exist "%output_file%" del "%output_file%"

:: Obtener la fecha y hora actual
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "fecha=%dt:~6,2%/%dt:~4,2%/%dt:~0,4%"
set "hora=%dt:~8,2%:%dt:~10,2%:%dt:~12,2%"

:: Escribir encabezado en el archivo
echo Estructura completa de carpetas y archivos > "%output_file%"
echo Generado el %fecha% a las %hora% >> "%output_file%"
echo Directorio base: %cd% >> "%output_file%"
echo. >> "%output_file%"

:: Usar el comando tree nativo con todas las opciones
tree /f /a > temp_tree.txt

:: Agregar el contenido del tree al archivo final
type temp_tree.txt >> "%output_file%"

:: Eliminar el archivo temporal
del temp_tree.txt

:: Agregar lista detallada de todos los archivos
echo. >> "%output_file%"
echo Lista detallada de archivos: >> "%output_file%"
echo. >> "%output_file%"
dir /s /b /a-d >> "%output_file%"

:: Agregar lista detallada de todas las carpetas
echo. >> "%output_file%"
echo Lista detallada de carpetas: >> "%output_file%"
echo. >> "%output_file%"
dir /s /b /ad >> "%output_file%"

echo Estructura guardada en %output_file%
echo.
echo Presione cualquier tecla para abrir el archivo...
pause > nul
start "" "%output_file%"