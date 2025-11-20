<#
    Helper script to bootstrap the Laravel workspace.
    USAGE: powershell.exe -ExecutionPolicy Bypass -File .\operations\init.ps1 [-SkipComposer] [-SkipNpm] [-SkipMigrate] [-SkipBuild] [-DevAssets]
#>

param(
    [switch]$SkipComposer,
    [switch]$SkipNpm,
    [switch]$SkipMigrate,
    [switch]$SkipBuild,
    [switch]$DevAssets
)

$ErrorActionPreference = 'Stop'

function Write-Step($Message) {
    Write-Host ''
    Write-Host "=== $Message ===" -ForegroundColor Cyan
}

function Exec($Command, $Arguments) {
    Write-Host ("> {0} {1}" -f $Command, ($Arguments -join ' '))
    & $Command @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Command '$Command' failed with code $LASTEXITCODE"
    }
}

function Ensure-Command($Command) {
    if (-not (Get-Command $Command -ErrorAction SilentlyContinue)) {
        throw "Required command '$Command' not found in PATH."
    }
}

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path $scriptDir

Push-Location $projectRoot
try {
    Write-Host "Project root: $projectRoot"

    if (-not (Test-Path '.env') -and (Test-Path '.env.example')) {
        Write-Step 'Copy .env.example to .env'
        Copy-Item '.env.example' '.env'
    }

    if (-not $SkipComposer) {
        Ensure-Command 'composer'
        if (-not (Test-Path 'vendor')) {
            Write-Step 'Running composer install'
            Exec 'composer' @('install')
        } else {
            Write-Host 'vendor/ already exists, skipping composer install (use -SkipComposer to bypass check).'
        }
    } else {
        Write-Host 'Composer install explicitly skipped.'
    }

    if (-not $SkipNpm) {
        Ensure-Command 'npm'
        if (-not (Test-Path 'node_modules')) {
            Write-Step 'Running npm install'
            Exec 'npm' @('install')
        } else {
            Write-Host 'node_modules/ already exists, skipping npm install (use -SkipNpm to bypass check).'
        }
    } else {
        Write-Host 'npm install explicitly skipped.'
    }

    Write-Step 'Laravel maintenance commands'
    Exec 'php' @('artisan', 'config:clear')
    Exec 'php' @('artisan', 'route:clear')
    Exec 'php' @('artisan', 'cache:clear')

    Write-Step 'Link storage and publish Telescope assets'
    Exec 'php' @('artisan', 'storage:link')
    Exec 'php' @('artisan', 'telescope:publish')

    if (-not $SkipMigrate) {
        Write-Step 'Running database migrations'
        Exec 'php' @('artisan', 'migrate', '--force')
    } else {
        Write-Host 'Migrations skipped.'
    }

    Write-Step 'Project specific init command'
    Exec 'php' @('artisan', 'init:app')

    if (-not $SkipBuild) {
        Ensure-Command 'npm'
        if ($DevAssets) {
            Write-Step 'Building assets (npm run dev)'
            Exec 'npm' @('run', 'dev')
        } else {
            Write-Step 'Building assets (npm run build)'
            Exec 'npm' @('run', 'build')
        }
    } else {
        Write-Host 'Asset build skipped.'
    }

    Write-Step 'Queue worker hint'
    Write-Host 'Start queue workers if needed: php artisan queue:work'
}
finally {
    Pop-Location
}
