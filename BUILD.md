# Building the Unraid EasyTier Plugin

This plugin consists of two packages:
1. **EasyTier binary** - Downloaded from EasyTier releases
2. **Utils package** - Contains PHP files, styles, and scripts (built from this repo)

## Building the Utils Package

### On Windows (PowerShell)

```powershell
.\build.ps1
```

Or if you have WSL installed:
```bash
wsl bash build.sh
```

### On Linux / WSL / macOS

```bash
# Using the build script
chmod +x build.sh
./build.sh

# Or using Make
make
```

## Build Output

The build process will create:
- `unraid-easytier-utils-2026.02.22.0001-noarch-1.txz` - The utils package
- `unraid-easytier-utils-2026.02.22.0001-noarch-1.txz.sha256` - SHA256 checksum

## Updating the Plugin

After building the utils package:

1. **Copy the SHA256 checksum** from the build output

2. **Update `plugin/easytier.plg`** with the SHA256:
   ```xml
   <FILE Name="/boot/config/plugins/easytier/unraid-easytier-utils-2026.02.22.0001-noarch-1.txz">
   <URL>https://github.com/YOUR_USERNAME/unraid-easytier/releases/download/2026.02.22.0001/unraid-easytier-utils-2026.02.22.0001-noarch-1.txz</URL>
   <SHA256>PASTE_SHA256_HERE</SHA256>
   </FILE>
   ```

3. **Update the repository URL** in `easytier.plg`:
   - Line 9: `pluginURL="https://raw.githubusercontent.com/YOUR_USERNAME/unraid-easytier/main/plugin/easytier.plg"`
   - Line 43: Update the download URL with your GitHub username

4. **Create a GitHub release**:
   - Tag: `2026.02.22.0001`
   - Upload: `unraid-easytier-utils-2026.02.22.0001-noarch-1.txz`

## Installing the Plugin

### Method 1: From Plugin URL (Recommended)

1. Upload `easytier.plg` to your GitHub repository
2. In Unraid, go to Settings → Plugins → Install Plugin
3. Paste the URL: `https://raw.githubusercontent.com/YOUR_USERNAME/unraid-easytier/main/plugin/easytier.plg`

### Method 2: Manual Install

1. Download `easytier.plg` and install it in Unraid
2. Manually upload the utils package to `/boot/config/plugins/easytier/`
3. Run the install script

## Package Structure

The utils package (`*.txz`) contains:

```
usr/local/
├── emhttp/plugins/easytier/
│   ├── include/
│   │   ├── Pages/
│   │   │   ├── Settings.php
│   │   │   └── Logs.php
│   │   ├── common.php
│   │   ├── page.php
│   │   ├── save_config_file.php
│   │   ├── clear_log.php
│   │   ├── save_settings.php
│   │   └── restart_service.php
│   ├── styles/
│   │   ├── settings.css
│   │   └── logs.css
│   ├── restart.sh
│   └── easytier-watcher.php
└── php/easytier-utils/
    ├── log.sh
    ├── pre-startup.php
    ├── daily.php
    ├── daily.sh
    └── easytier-utils/
        ├── Config.php
        ├── System.php
        └── Utils.php

usr/local/etc/
└── rc.d/
    └── rc.easytier

etc/
├── cron.daily/
│   └── easytier-daily
└── logrotate.d/
    └── easytier

install/
└── doinst.sh
```

## Cleaning Build Artifacts

```bash
# Linux/WSL
make clean

# PowerShell
Remove-Item -Recurse -Force build, *.txz, *.sha256
```

## Development Workflow

1. Make changes to source files in `src/`
2. Run build script to create new utils package
3. Update SHA256 in `plugin/easytier.plg`
4. Test the plugin on Unraid
5. Commit changes and create GitHub release
