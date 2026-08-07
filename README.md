# Unraid EasyTier Plugin

[中文文档](README_CN.md)

EasyTier networking plugin for Unraid OS 6.12.14 and later.

## About EasyTier

[EasyTier](https://github.com/EasyTier/EasyTier) is a simple, secure, and
decentralized mesh VPN powered by Rust and Tokio. It connects devices and
networks across NAT boundaries without requiring a traditional centralized VPN
gateway. EasyTier supports peer-to-peer NAT traversal, subnet proxying,
intelligent routing, encrypted tunnels, and multiple transport protocols.

This repository is an independent Unraid integration. It packages the upstream
EasyTier CLI binaries and provides Unraid-specific installation, WebUI
configuration, service management, logging, interface registration, and
maintenance.

### EasyTier Resources

- [Official website](https://easytier.cn/)
- [Upstream project](https://github.com/EasyTier/EasyTier)
- [English documentation](https://easytier.cn/en/guide/introduction.html)
- [Chinese documentation](https://easytier.cn/guide/introduction.html)
- [Web Console](https://easytier.cn/web/)
- [Upstream releases](https://github.com/EasyTier/EasyTier/releases)

## Features

- Start, stop, and automatically recover `easytier-core`
- Start EasyTier during system/plugin startup after network readiness; array auto-start is not required
- Configure network name, secret, peer, listener, RPC, and hostname in WebUI
- Register the `easytier0` interface with Unraid
- Configure IPv4 and IPv6 forwarding
- Show, clear, and download EasyTier logs
- Synchronize EasyTier peer hostnames into a managed `/etc/hosts` block
- Build and publish versioned Unraid packages with GitHub Actions

## Installation

### Method 1: Install from the plugin URL (recommended)

1. In Unraid, open **Settings > Plugins > Install Plugin**.
2. Paste the latest-release plugin URL:

`https://github.com/wx2020/unraid-easytier/releases/latest/download/easytier.plg`

3. Select **Install** and wait for the installation to complete.

### Method 2: Manual installation

1. Open the [latest release](https://github.com/wx2020/unraid-easytier/releases/latest).
2. Download the `easytier.plg` release asset.
3. In Unraid, open **Settings > Plugins > Install Plugin**.
4. Upload the downloaded `easytier.plg` file and select **Install**.

This URL always installs the most recently published plugin release. Changes
merged to `main` are not available through this URL until the **Build and
Release** workflow publishes a new release.

The `plugin/easytier.plg` file in the source tree is a release template. Its
utils-package checksum and EasyTier binary metadata are replaced by the release
workflow, so the template itself is not an installable release artifact.

## Configuration

After installation, open **Settings > Network Services > EasyTier**.

- **Enable EasyTier** controls whether the service starts.
- EasyTier starts during system/plugin startup after the host network is ready;
  it does not depend on the array starting.
- **Include Interface in Unraid** adds `easytier0` to Unraid network settings.
- **Enable IP Forwarding** installs the plugin sysctl configuration.
- **Add Peers to Hosts** updates a marked block in `/etc/hosts` each day.
- **Config Server Address** is passed to EasyTier as `-w` (`--config-server`),
  for example `udp://easytier.example.com:22020/username`. When it is valid,
  EasyTier receives its network configuration from the server and ignores the
  local network, listener, RPC, SOCKS5, and hostname parameters.
- **Listener Address** accepts `IP:PORT`; the selected protocol is prepended.
- **SOCKS5 Listen Port** enables EasyTier's SOCKS5 server when set.
- **EasyTier Config File** edits `/boot/config/plugins/easytier/easytier.toml`
  and passes it to EasyTier with `-c` when the service fields are incomplete.
  The precedence is valid `-w` address, valid service settings, then this
  config file.

## Development

The version is stored in [`VERSION`](VERSION).

```bash
# Linux, WSL, or macOS
bash build.sh

# Windows PowerShell
powershell -ExecutionPolicy Bypass -File .\build.ps1
```

The build creates:

- `unraid-easytier-utils-<version>-noarch-1.txz`
- `unraid-easytier-utils-<version>-noarch-1.txz.sha256`

Pull requests validate PHP and shell syntax, metadata consistency, package
contents, and the generated checksum.

## Release

Push a version tag matching the release version, or run the **Build and
Release** workflow with an explicit version:

```bash
git tag 2026.08.06.0002
git push origin 2026.08.06.0002
```

The workflow queries the EasyTier GitHub Releases API for the latest stable
Linux x86_64 archive, verifies the downloaded asset by calculating its SHA256,
builds the utils package, and generates the installable PLG files. The selected
EasyTier version is fixed in that plugin release; installed plugins do not query
the API or silently switch to a newer upstream binary.

## Community Applications

The repository includes `ca_profile.xml` and `templates/easytier.xml` for
submission to Unraid Community Applications. The CA entry installs the
generated `easytier.plg` asset from the latest GitHub release.

Plugin support and issue reports are handled through
[GitHub Issues](https://github.com/wx2020/unraid-easytier/issues).

## Troubleshooting

```bash
/etc/rc.d/rc.easytier restart
tail -f /var/log/easytier.log
tail -f /var/log/easytier-utils.log
easytier-cli peer
ip link show easytier0
```

## License

This project is licensed under the [GNU General Public License v3.0](LICENSE).
