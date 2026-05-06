# PowerShell build script for Windows
# Equivalent to build.sh for cross-platform builds

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir

$PluginDir = Join-Path $ProjectRoot "codemedic-speed-scanner-for-plugins"

$EnvFile = Join-Path $ProjectRoot "codemedic-speed-scanner-for-plugins\.env"
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
$OutputZip = Join-Path $OutputDir "codemedic-speed-scanner-for-plugins-${Mode}.zip"

$ExcludeDirs = @("tests", "vendor", ".git")
$ExcludeFiles = @(".gitignore", ".distignore", ".phpunit.result.cache", "composer-setup.php", ".phpunit.xml", "composer.json", "composer.lock", "README.md", ".env", ".env.example", "env-example")

Write-Host "Building WordPress plugin ZIP..."

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

$tempDir = Join-Path $env:TEMP "wp-plugin-build-$(Get-Random)"
New-Item -ItemType Directory -Path $tempDir | Out-Null

# Ensure destination directory exists
$destDir = Join-Path $tempDir "codemedic-speed-scanner-for-plugins"
New-Item -ItemType Directory -Path $destDir -ErrorAction SilentlyContinue | Out-Null

# Copy plugin files
Copy-Item -Path "$PluginDir\*" -Destination $destDir -Recurse -Force

# Remove excluded directories
foreach ($dir in $ExcludeDirs) {
    $target = Join-Path $tempDir "codemedic-speed-scanner-for-plugins\$dir"
    if (Test-Path $target) { Remove-Item -Path $target -Recurse -Force }
}

# Remove excluded files
foreach ($file in $ExcludeFiles) {
    $target = Join-Path $tempDir "codemedic-speed-scanner-for-plugins\$file"
    if (Test-Path $target) { Remove-Item -Path $target -Force }
}

if ($Mode -eq "premium") {
    $PluginName = "CodeMedic Speed Scanner for Plugins Premium"
    $PluginSlug = "codemedic-speed-scanner-for-plugins-premium"
    Rename-Item -Path "$tempDir\codemedic-speed-scanner-for-plugins" -NewName $PluginSlug
    $ConfigPath = Join-Path $tempDir "$PluginSlug\config.php"
} else {
    $PluginName = "CodeMedic Speed Scanner for Plugins"
    $PluginSlug = "codemedic-speed-scanner-for-plugins"
    $ConfigPath = Join-Path $tempDir "codemedic-speed-scanner-for-plugins\config.php"
}

# Update readme.txt
$readmePath = Join-Path $tempDir "$PluginSlug\readme.txt"
if (Test-Path $readmePath) {
    (Get-Content $readmePath) -replace "=== CodeMedic Speed Scanner for Plugins ===", "=== $PluginName ===" | Set-Content $readmePath
}

# Update main plugin file
$mainPluginPath = Join-Path $tempDir "$PluginSlug\codemedic-speed-scanner-for-plugins.php"
if (Test-Path $mainPluginPath) {
    (Get-Content $mainPluginPath) -replace "Plugin Name: CodeMedic Speed Scanner for Plugins", "Plugin Name: $PluginName" | Set-Content $mainPluginPath
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
