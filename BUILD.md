# Building the Unraid EasyTier Plugin

## Requirements

- Linux/macOS/WSL: `bash`, `tar`, `sha256sum`
- Windows: PowerShell 5 or later and the bundled `tar.exe`

## Build

The package version is read from the repository-level `VERSION` file.

```bash
bash build.sh
```

```powershell
powershell -ExecutionPolicy Bypass -File .\build.ps1
```

Both scripts create a versioned archive and a standard `sha256sum -c`
compatible sidecar. Linux release builds use xz compression. The Windows
script uses gzip because the Windows system `tar.exe` does not universally
support xz; Unraid's `tar -xf` auto-detects both formats.

## Package Layout

The archive contains:

- `install/doinst.sh`
- `usr/local/etc/rc.d/rc.easytier`
- `usr/local/emhttp/plugins/easytier/`
- `usr/local/php/easytier-utils/`
- `etc/logrotate.d/easytier`

`doinst.sh` creates the Unraid service, event, and daily-task symlinks and sets
the executable permissions required at runtime.

## Release Artifacts

Do not install `plugin/easytier.plg` directly from a source checkout because it
contains release tokens for the utils checksum and EasyTier binary asset. The
GitHub release workflow resolves those values through the GitHub Releases API
and publishes:

- `easytier.plg`
- `easytier-<version>.plg`
- `unraid-easytier-utils-<version>-noarch-1.txz`
- checksum files

Use the generated release PLG for installation testing.
