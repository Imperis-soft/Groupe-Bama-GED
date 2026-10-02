# Build (linux/amd64) et publication des deux images sur Docker Hub - version Windows / PowerShell.
# Fichier volontairement en ASCII pur : Windows PowerShell 5.1 lit mal l'UTF-8 sans BOM.
#   .\docker\build-push.ps1            -> tag latest + tag date (ex. 2026.10.02-1430)
#   .\docker\build-push.ps1 v1.4.0     -> tag latest + v1.4.0
param([string]$Tag = (Get-Date -Format "yyyy.MM.dd-HHmm"))

$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..")

$AppImage   = "imperissoft/ged"
$NginxImage = "imperissoft/ged-nginx"

function Invoke-Docker {
    docker @args
    if ($LASTEXITCODE -ne 0) { throw "Echec : docker $($args -join ' ')" }
}

Write-Host "== Build ${AppImage}:${Tag}"
Invoke-Docker build --platform linux/amd64 --target app -t "${AppImage}:${Tag}" -t "${AppImage}:latest" .

Write-Host "== Build ${NginxImage}:${Tag}"
Invoke-Docker build --platform linux/amd64 --target nginx -t "${NginxImage}:${Tag}" -t "${NginxImage}:latest" .

Write-Host "== Push"
Invoke-Docker push "${AppImage}:${Tag}"
Invoke-Docker push "${AppImage}:latest"
Invoke-Docker push "${NginxImage}:${Tag}"
Invoke-Docker push "${NginxImage}:latest"

Write-Host "OK - images publiees : $Tag et latest"
Write-Host "Sur le VPS : Portainer > stack ged > Update the stack (Re-pull image and redeploy)"
