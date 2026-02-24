# Makefile for Unraid EasyTier Plugin
# Build the utils package (txz)

VERSION := 2026.02.22.0001
PKG_NAME := unraid-easytier-utils
PKG_VERSION := $(PKG_NAME)-$(VERSION)-noarch-1
PKG_FILE := $(PKG_VERSION).txz
BUILD_DIR := build
PACKAGE_DIR := $(BUILD_DIR)/$(PKG_VERSION)

# Phony targets
.PHONY: all clean package utils

all: package

# Create the utils package
utils:
	@echo "Building $(PKG_FILE)..."
	@rm -rf $(BUILD_DIR)
	@mkdir -p $(PACKAGE_DIR)

	# Copy source files maintaining directory structure
	@cd src && find . -type f -not -path "*/\.*" | while read file; do \
		mkdir -p ../$(PACKAGE_DIR)/"$$(dirname $$file)"; \
		cp "$$file" ../$(PACKAGE_DIR)/"$$file"; \
	done

	# Create the package
	@cd $(BUILD_DIR) && tar --xz -cf ../$(PKG_FILE) $(PKG_VERSION)

	@echo "Package created: $(PKG_FILE)"
	@sha256sum $(PKG_FILE) > $(PKG_FILE).sha256
	@echo "SHA256:"
	@cat $(PKG_FILE).sha256 | awk '{print $$1}'

# Build the plugin package
package: utils
	@echo ""
	@echo "=== Plugin Package Build Summary ==="
	@echo "Utils Package: $(PKG_FILE)"
	@echo "SHA256: $$(cat $(PKG_FILE).sha256 | awk '{print $$1}')"
	@echo ""
	@echo "Next steps:"
	@echo "1. Update easytier.plg with the SHA256 above"
	@echo "2. Upload $(PKG_FILE) to GitHub releases"
	@echo "3. The plugin will download and install the utils package automatically"

# Clean build artifacts
clean:
	@echo "Cleaning build artifacts..."
	@rm -rf $(BUILD_DIR)
	@rm -f $(PKG_FILE) $(PKG_FILE).sha256
	@echo "Clean complete."

# Install for testing (requires Unraid system)
install: package
	@echo "Installing plugin for testing..."
	@if [ -z "$$UNRAID_IP" ]; then \
		echo "Error: UNRAID_IP environment variable not set"; \
		echo "Usage: make install UNRAID_IP=192.168.1.100"; \
		exit 1; \
	fi
	@scp $(PKG_FILE) root@$$UNRAID_IP:/boot/config/plugins/easytier/
	@echo "Package uploaded to Unraid server at $$UNRAID_IP"
