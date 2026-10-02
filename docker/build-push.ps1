# Build (linux/amd64) et publication des deux images sur Docker Hub — version Windows / PowerShell.
#   .\docker\build-push.ps1            → tag latest + tag daté (ex. 2026.10.02-1430)
#   .\docker\build-push.ps1 v1.4.0     → tag latest + v1.4.0
param([string]$Tag = (Get-Date -Format "yyyy.MM.dd-HHmm"))

$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..")

$AppImage   = "imperissoft/ged"
$NginxImage = "imperissoft/ged-nginx"

function Run([string[]]$cmd) {
    & $cmd[0] $cmd[1..($cmd.Length - 1)]
    if ($LASTEXITCODE -ne 0) { throw "Échec : $($cmd -join ' ')" }
}

Write-Host "→ Build ${AppImage}:${Tag}"
Run @("docker", "build", "--platform", "linux/amd64", "--target", "app", "-t", "${AppImage}:${Tag}", "-t", "${AppImage}:latest", ".")

Write-Host "→ Build ${NginxImage}:${Tag}"
Run @("docker", "build", "--platform", "linux/amd64", "--target", "nginx", "-t", "${NginxImage}:${Tag}", "-t", "${NginxImage}:latest", ".")

Write-Host "→ Push"
Run @("docker", "push", "${AppImage}:${Tag}")
Run @("docker", "push", "${AppImage}:latest")
Run @("docker", "push", "${NginxImage}:${Tag}")
Run @("docker", "push", "${NginxImage}:latest")

Write-Host "✓ Images publiées : $Tag et latest"
Write-Host "  Sur le VPS : Portainer → stack ged → Update the stack (Re-pull image and redeploy)"
