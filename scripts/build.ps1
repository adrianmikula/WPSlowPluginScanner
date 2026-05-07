# PowerShell build script for Windows
# Equivalent to build.sh for cross-platform builds

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir

$PluginDir = Join-Path $ProjectRoot "code-medic-slow-site-scanner"

$EnvFile = Join-Path $ProjectRoot "code-medic-slow-site-scanner\.env"
$Mode = "free"
if (Test-Path $EnvFile) {
    $envContent = Get-Content $EnvFile
    foreach ($line in $envContent) {
        if ($line -match '^PIA_MODE=(.*)$') {
            $Mode = $Matches[1].Trim()
            break
        }
    }
    if (-not $Mode) { $Mode = "free" }
}

$OutputDir = Join-Path $ProjectRoot "build"
$OutputZip = Join-Path $OutputDir "code-medic-slow-site-scanner-${Mode}.zip"

$ExcludeDirs = @("tests", "vendor", ".git")
$ExcludeFiles = @(".gitignore", ".distignore", ".phpunit.result.cache", "composer-setup.php", ".phpunit.xml", "composer.json", "composer.lock", "README.md", ".env", ".env.example", "env-example")

Write-Host "Building WordPress plugin ZIP..."

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

$tempDir = Join-Path $env:TEMP "wp-plugin-build-$(Get-Random)"
New-Item -ItemType Directory -Path $tempDir | Out-Null

# Ensure destination directory exists
$destDir = Join-Path $tempDir "code-medic-slow-site-scanner"
New-Item -ItemType Directory -Path $destDir -ErrorAction SilentlyContinue | Out-Null

# Copy plugin files
Copy-Item -Path "$PluginDir\*" -Destination $destDir -Recurse -Force

# Remove excluded directories
foreach ($dir in $ExcludeDirs) {
    $target = Join-Path $tempDir "code-medic-slow-site-scanner\$dir"
    if (Test-Path $target) { Remove-Item -Path $target -Recurse -Force }
}

# Remove excluded files
foreach ($file in $ExcludeFiles) {
    $target = Join-Path $tempDir "code-medic-slow-site-scanner\$file"
    if (Test-Path $target) { Remove-Item -Path $target -Force }
}

if ($Mode -eq "premium") {
    $PluginName = "CodeMedic Slow Site Scanner Premium"
    $PluginSlug = "code-medic-slow-site-scanner-premium"
    Rename-Item -Path "$tempDir\code-medic-slow-site-scanner" -NewName $PluginSlug
    $ConfigPath = Join-Path $tempDir "$PluginSlug\config.php"
} else {
    $PluginName = "CodeMedic Slow Site Scanner (Free)"
    $PluginSlug = "code-medic-slow-site-scanner-free"
    Rename-Item -Path "$tempDir\code-medic-slow-site-scanner" -NewName $PluginSlug
    $ConfigPath = Join-Path $tempDir "$PluginSlug\config.php"
}

# Update readme.txt
$readmePath = Join-Path $tempDir "$PluginSlug\readme.txt"
if (Test-Path $readmePath) {
    (Get-Content $readmePath) -replace "=== CodeMedic Slow Site Scanner ===", "=== $PluginName ===" | Set-Content $readmePath
}

# Update main plugin file
$mainPluginPath = Join-Path $tempDir "$PluginSlug\code-medic-slow-site-scanner.php"
if (Test-Path $mainPluginPath) {
    (Get-Content $mainPluginPath) -replace "Plugin Name: CodeMedic Slow Site Scanner", "Plugin Name: $PluginName" | Set-Content $mainPluginPath
}

# Generate config.php from .env
if (Test-Path $EnvFile) {
    $configLines = @()
    $configLines += "<?php"
    $configLines += "if ( ! defined( 'ABSPATH' ) ) { exit; }"
    $configLines += "// Auto-generated config - do not commit to version control"
    foreach ($line in $envContent) {
        if ($line -match '^(PIA_\w+)\s*=\s*(.*)$') {
            $key = $Matches[1]
            $value = $Matches[2].Trim()
            if ($value) {
                $configLines += "define('$key', '$value');"
            }
        }
    }
    $configLines | Set-Content $ConfigPath
}

# Create ZIP archive
# Use Compress-Archive (PowerShell built-in)
$tempZip = Join-Path $tempDir "plugin.zip"
Compress-Archive -Path "$tempDir\$PluginSlug\*" -DestinationPath $tempZip -Force

# Move to output
Move-Item -Path $tempZip -Destination $OutputZip -Force

# Cleanup
Remove-Item -Path $tempDir -Recurse -Force

Write-Host "Built: $OutputZip"
Get-Item $OutputZip | Format-List Name, Length, FullName
