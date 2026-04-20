# Lancer ce script en PowerShell ADMINISTRATEUR
$certPath = "$PSScriptRoot\docker\nginx\certs\localhost.crt"
$cert = New-Object System.Security.Cryptography.X509Certificates.X509Certificate2($certPath)
$store = New-Object System.Security.Cryptography.X509Certificates.X509Store("Root", "LocalMachine")
$store.Open("ReadWrite")
$store.Add($cert)
$store.Close()
Write-Host "✓ Certificat Groupe Bama GED installe avec succes dans les autorites de confiance Windows." -ForegroundColor Green
Write-Host "  Redemarrez Chrome si il etait ouvert." -ForegroundColor Yellow
