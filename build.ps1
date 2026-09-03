# Build script for Unraid EasyTier Plugin Utils Package
# Run this script on Windows with PowerShell

$ErrorActionPreference = "Stop"

$VERSION = (Get-Content (Join-Path $PSScriptRoot "VERSION") -Raw).Trim()
$PKG_NAME = "unraid-easytier-utils"
$PKG_VERSION = "$PKG_NAME-$VERSION-noarch-1"
$PKG_FILE = "$PKG_VERSION.txz"
$BUILD_DIR = "build"
$PACKAGE_DIR = "$BUILD_DIR\$PKG_VERSION"

Write-Host "Building $PKG_FILE..." -ForegroundColor Cyan

# Clean previous build
if (Test-Path $BUILD_DIR) {
    Remove-Item -Path $BUILD_DIR -Recurse -Force
}
Remove-Item -LiteralPath $PKG_FILE, "$PKG_FILE.sha256" -Force -ErrorAction SilentlyContinue

# Create package directory
New-Item -ItemType Directory -Path $PACKAGE_DIR -Force | Out-Null

# Copy source files maintaining directory structure
Write-Host "Copying source files..." -ForegroundColor Yellow

Get-ChildItem -Path "src" -Recurse -File | ForEach-Object {
    $relativePath = $_.FullName.Substring((Get-Item "src").FullName.Length + 1)
    $destPath = Join-Path $PACKAGE_DIR $relativePath
    $destDir = Split-Path $destPath -Parent

    if (-not (Test-Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }

    Copy-Item -Path $_.FullName -Destination $destPath -Force
}

# Install the plugin translation as part of the utils package so the plugin
# does not need a separate language-pack release asset.
Write-Host "Copying Chinese translation..." -ForegroundColor Yellow
$translationPath = Join-Path $PACKAGE_DIR "usr\local\emhttp\languages\zh_CN\easytier.txt"
New-Item -ItemType Directory -Path (Split-Path $translationPath -Parent) -Force | Out-Null
Copy-Item -Path "translations\zh_CN\EasyTier\easytier.txt" -Destination $translationPath -Force

# Create tar archive (requires 7zip or tar on Windows)
Write-Host "Creating package archive..." -ForegroundColor Yellow

Push-Location $BUILD_DIR

# P2 B-04: try xz for consistency with build.sh, fallback to gzip with warning
try {
    # Try bsdtar/xz first (matches build.sh --xz)
    tar --xz -cf "../$PKG_FILE" $PKG_VERSION 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Package created with tar --xz (xz, matches build.sh)" -ForegroundColor Green
    } else {
        throw "tar xz failed"
    }
} catch {
    try {
        tar -czf "../$PKG_FILE" $PKG_VERSION
        if ($LASTEXITCODE -eq 0) {
            Write-Host "WARNING: xz not available, created gzip .txz (Unraid tar auto-detects)" -ForegroundColor Yellow
            Write-Host "Package created with tar -czf (gzip)" -ForegroundColor Green
        } else {
            throw "tar gz failed"
        }
    } catch {
        # Fallback to 7zip if installed
        if (Get-Command "7z" -ErrorAction SilentlyContinue) {
            & 7z a -ttar -so archive.tar $PKG_VERSION | & 7z a -si -tgzip "../$PKG_FILE"
            Write-Host "Package created with 7zip (gzip)" -ForegroundColor Green
        } else {
            Write-Host "ERROR: Neither tar nor 7z found. Please install 7-Zip or use WSL/Linux to build." -ForegroundColor Red
            Write-Host "You can also copy the 'src' folder to a Linux system and run: make" -ForegroundColor Yellow
            Pop-Location
            exit 1
        }
    }
}

Pop-Location

# Calculate SHA256
Write-Host "Calculating SHA256..." -ForegroundColor Yellow

$sha256Hash = (Get-FileHash -LiteralPath "$PKG_FILE" -Algorithm SHA256).Hash.ToLowerInvariant()

# Save SHA256 to file
"$sha256Hash  $PKG_FILE" | Out-File -FilePath "$PKG_FILE.sha256" -Encoding ASCII

Write-Host ""
Write-Host "=== Build Complete ===" -ForegroundColor Green
Write-Host "Package: $PKG_FILE"
Write-Host "SHA256:  $sha256Hash"
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Update plugin/easytier.plg with this SHA256:"
Write-Host "   <SHA256>$sha256Hash</SHA256>"
Write-Host "2. Upload $PKG_FILE to GitHub releases"
Write-Host "3. The plugin will download and install the utils package automatically"
Write-Host ""
Write-Host "To clean build artifacts, run: Remove-Item -Recurse -Force build, *.txz, *.sha256"
