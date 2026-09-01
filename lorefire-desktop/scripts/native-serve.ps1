# native-serve.ps1 — Windows ARM64 helper around `php artisan native:serve`.
# Keeps Node + Electron + PHP on ARM. Does not switch the project to x64 Node.
#
# Usage (from lorefire-desktop):
#   powershell -ExecutionPolicy Bypass -File scripts\native-serve.ps1

$ErrorActionPreference = 'Stop'

function Get-NodeArch {
    $arch = & node -p "process.arch" 2>$null
    if (-not $arch) { throw "Node.js is not on PATH. Install ARM64 Node (not x64)." }
    return $arch.Trim()
}

function Get-PhpMachine {
    $machine = & php -r "echo php_uname('m');" 2>$null
    if (-not $machine) { throw "PHP is not on PATH. Install ARM64 PHP: winget install PHP.PHP.8.4" }
    return $machine.Trim()
}

$nodeArch = Get-NodeArch
if ($nodeArch -ne 'arm64') {
    throw "Node arch is '$nodeArch'. Keep ARM64 Node. Do not install x64 Node / Prism for Lorefire."
}

$phpMachine = Get-PhpMachine
if ($phpMachine -notmatch 'arm|aarch') {
    throw "PHP machine is '$phpMachine'. Use ARM64 PHP (winget install PHP.PHP.8.4), not x64 PHP under emulation."
}

$phpExe = & php -r "echo PHP_BINARY;"
$env:NATIVEPHP_PHP_EXECUTABLE = $phpExe
Write-Host "Windows ARM64 native:serve"
Write-Host "  Node : $nodeArch"
Write-Host "  PHP  : $phpMachine ($phpExe)"
Write-Host "  Electron stays ARM64; PHP is the system ARM binary (php-bin has no win/arm64)."
Write-Host ""

& php artisan native:serve @args
exit $LASTEXITCODE
