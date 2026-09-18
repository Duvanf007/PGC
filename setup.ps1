# ============================================================
# setup.ps1 — Script de Configuración y Despliegue
# Sistema de Gestión de Seguridad de Guachetá
# ============================================================
# INSTRUCCIONES:
# 1. Asegúrate de que XAMPP esté instalado en C:\xampp\
# 2. Haz clic derecho en este archivo → "Ejecutar con PowerShell"
# ============================================================

$source = Split-Path -Parent $MyInvocation.MyCommand.Path
$dest   = "C:\xampp\htdocs\seguridad"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Sistema de Seguridad Guacheta - Setup " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verificar que XAMPP existe
if (-not (Test-Path "C:\xampp\htdocs")) {
    Write-Host "[ERROR] No se encontro XAMPP en C:\xampp" -ForegroundColor Red
    Write-Host "Por favor instala XAMPP desde: https://www.apachefriends.org/" -ForegroundColor Yellow
    Read-Host "Presiona Enter para salir"
    exit 1
}

Write-Host "[1/4] Creando carpeta en htdocs: $dest" -ForegroundColor Green
New-Item -ItemType Directory -Force -Path "$dest\api"     | Out-Null
New-Item -ItemType Directory -Force -Path "$dest\css"     | Out-Null
New-Item -ItemType Directory -Force -Path "$dest\js"      | Out-Null
New-Item -ItemType Directory -Force -Path "$dest\img"     | Out-Null
New-Item -ItemType Directory -Force -Path "$dest\db"      | Out-Null
New-Item -ItemType Directory -Force -Path "$dest\uploads" | Out-Null
Write-Host "   -> Directorios creados correctamente" -ForegroundColor Gray

Write-Host "[2/4] Copiando archivos del proyecto..." -ForegroundColor Green

# HTML
Copy-Item "$source\*.html"       -Destination $dest -Force
# Imágenes
Copy-Item "$source\img\*"        -Destination "$dest\img\" -Recurse -Force -ErrorAction SilentlyContinue
# CSS
Copy-Item "$source\css\*" -Destination "$dest\css\" -Force
# JS
Copy-Item "$source\js\*"  -Destination "$dest\js\"  -Force
# API PHP
Copy-Item "$source\api\*" -Destination "$dest\api\" -Force
# DB Schema
Copy-Item "$source\db\*"  -Destination "$dest\db\"  -Force

Write-Host "   -> Archivos copiados correctamente" -ForegroundColor Gray

Write-Host "[3/4] Configurando permisos de uploads..." -ForegroundColor Green
$acl = Get-Acl "$dest\uploads"
$rule = New-Object System.Security.AccessControl.FileSystemAccessRule("Everyone","FullControl","ContainerInherit,ObjectInherit","None","Allow")
$acl.SetAccessRule($rule)
Set-Acl "$dest\uploads" $acl
Write-Host "   -> Permisos configurados" -ForegroundColor Gray

Write-Host "[4/4] Importando base de datos MySQL..." -ForegroundColor Green
Write-Host ""
Write-Host "ACCION REQUERIDA: Para crear la base de datos:" -ForegroundColor Yellow
Write-Host "  1. Abre XAMPP Control Panel y activa Apache + MySQL" -ForegroundColor White
Write-Host "  2. Ve a: http://localhost/phpmyadmin" -ForegroundColor White
Write-Host "  3. Crea una base de datos llamada: seguridad_guacheta" -ForegroundColor White
Write-Host "  4. Ve a la pestana SQL e importa el archivo:" -ForegroundColor White
Write-Host "     $dest\db\schema.sql" -ForegroundColor Cyan

# Intentar importar automaticamente (requiere MySQL en PATH)
$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"
if (Test-Path $mysqlPath) {
    $choice = Read-Host "`n¿Importar la base de datos automaticamente? (s/n)"
    if ($choice -eq 's' -or $choice -eq 'S') {
        Write-Host "Importando schema.sql automaticamente..." -ForegroundColor Green
        $sqlPath = "$dest\db\schema.sql" -replace '\\', '/'
        & $mysqlPath --default-character-set=utf8mb4 -u root -e "SOURCE $sqlPath;" 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "   -> Base de datos importada correctamente!" -ForegroundColor Green
        } else {
            Write-Host "   -> Error al importar. Por favor hazlo manualmente via phpMyAdmin." -ForegroundColor Red
        }
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  INSTALACION COMPLETADA" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Accede al sistema en:" -ForegroundColor White
Write-Host "  http://localhost/seguridad/" -ForegroundColor Cyan
Write-Host ""
Write-Host "Cuentas de acceso:" -ForegroundColor White
Write-Host "  Admin:    admin@guacheta.gov.co      / admin123" -ForegroundColor Gray
Write-Host "  Alcaldia: alcaldia@guacheta.gov.co   / alcaldia123" -ForegroundColor Gray
Write-Host "  Policia:  policia@guacheta.gov.co    / policia123" -ForegroundColor Gray
Write-Host "  Hospital: hospital@guacheta.gov.co   / hospital123" -ForegroundColor Gray
Write-Host ""

# Abrir el navegador
$openBrowser = Read-Host "¿Abrir http://localhost/seguridad/ en el navegador? (s/n)"
if ($openBrowser -eq 's' -or $openBrowser -eq 'S') {
    Start-Process "http://localhost/seguridad/"
}

Read-Host "Presiona Enter para salir"
