#!/bin/bash
# Build script for Unraid EasyTier Plugin Utils Package
# Run this on Linux or WSL

set -e

VERSION="$(tr -d '\r\n' < VERSION)"
PKG_NAME="unraid-easytier-utils"
PKG_VERSION="${PKG_NAME}-${VERSION}-noarch-1"
PKG_FILE="${PKG_VERSION}.txz"
BUILD_DIR="build"
PACKAGE_DIR="${BUILD_DIR}/${PKG_VERSION}"

echo "Building ${PKG_FILE}..."

# Clean previous build
rm -rf "${BUILD_DIR}"

# Create package directory
mkdir -p "${PACKAGE_DIR}"

# Copy source files maintaining directory structure
echo "Copying source files..."
cd src
find . -type f -not -path "*/\.*" | while read -r file; do
    mkdir -p "../${PACKAGE_DIR}/$(dirname "$file")"
    cp "$file" "../${PACKAGE_DIR}/$file"
done
cd ..

# Install the plugin translation as part of the utils package so the plugin
# does not need a separate language-pack release asset.
echo "Copying Chinese translation..."
mkdir -p "${PACKAGE_DIR}/usr/local/emhttp/languages/zh_CN"
cp translations/zh_CN/EasyTier/easytier.txt \
    "${PACKAGE_DIR}/usr/local/emhttp/languages/zh_CN/easytier.txt"

# Create the package
echo "Creating package archive..."
cd "${BUILD_DIR}"
tar --xz -cf "../${PKG_FILE}" "${PKG_VERSION}"
cd ..

# Calculate SHA256
echo "Calculating SHA256..."
SHA256=$(sha256sum "${PKG_FILE}" | awk '{print $1}')
echo "${SHA256}  ${PKG_FILE}" > "${PKG_FILE}.sha256"

echo ""
echo "=== Build Complete ==="
echo "Package: ${PKG_FILE}"
echo "SHA256:  ${SHA256}"
echo ""
echo "Next steps:"
echo "1. Update plugin/easytier.plg with this SHA256:"
echo "   <SHA256>${SHA256}</SHA256>"
echo "2. Upload ${PKG_FILE} to GitHub releases"
echo "3. The plugin will download and install the utils package automatically"
echo ""
echo "To clean build artifacts, run: make clean"
