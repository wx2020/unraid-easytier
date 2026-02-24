# Unraid EasyTier Plugin

EasyTier networking plugin for Unraid OS.

## Features

- EasyTier network integration
- Web UI for configuration
- Automatic interface registration with Unraid network settings
- IP forwarding configuration
- Peer management dashboard
- Automatic peer hostname resolution

## Installation

### Method 1: Plugin URL (Recommended)

1. In Unraid, go to **Settings** → **Plugins** → **Install Plugin**
2. Paste one of the following URLs:
   - **Latest Release**: `https://github.com/wx2020/unraid-easytier/releases/latest/download/easytier.plg`
   - **Specific Version**: `https://github.com/wx2020/unraid-easytier/releases/download/VERSION/easytier-VERSION.plg`
3. Click **Install**

### Method 2: Manual Download

1. Download the latest `easytier.plg` from the [Releases](https://github.com/wx2020/unraid-easytier/releases) page
2. Upload it to Unraid using **Plugins** → **Install Plugin**
3. Click **Install**

## Configuration

After installation, navigate to **Settings** → **EasyTier** to configure:

### System Settings

- **Enable EasyTier**: Enable or disable the EasyTier service
- **Include Interface in Unraid**: Add EasyTier interface to Unraid's network settings
- **Enable IP Forwarding**: Enable IP forwarding for routing traffic
- **Add Peers to Hosts**: Automatically add peers to `/etc/hosts`

### Network Configuration

- **Network Name**: The name of the EasyTier network to join
- **Network Secret**: Secret key for private networks
- **Protocol**: Connection protocol (UDP, TCP, WebSocket, etc.)
- **Listener Address**: Address and port for EasyTier to listen on
- **Proxy Address**: Optional SOCKS5 proxy
- **Instance ID**: Unique instance ID (0 for auto-assignment)
- **RPC Port**: Management RPC port
- **Hostname**: Hostname for this instance

## Project Structure

```
unraid-easytier/
├── plugin/
│   ├── easytier.plg          # Main plugin file
│   └── plugin.json           # Plugin metadata
├── src/
│   ├── install/
│   │   └── doinst.sh         # Installation script
│   ├── usr/local/etc/rc.d/
│   │   └── rc.easytier       # Service control script
│   ├── usr/local/emhttp/plugins/easytier/
│   │   ├── include/          # Web UI components
│   │   │   ├── Pages/        # UI pages
│   │   │   ├── common.php    # Common functions
│   │   │   ├── page.php      # Page loader
│   │   │   └── easytier-utils/ # Utility classes
│   │   ├── restart.sh        # Restart script
│   │   └── event/            # Event handlers
│   ├── usr/local/php/easytier-utils/
│   │   ├── log.sh            # Logging utility
│   │   ├── pre-startup.php   # Pre-startup tasks
│   │   ├── daily.php         # Daily maintenance
│   │   └── daily.sh          # Daily wrapper
│   └── etc/
│       ├── cron.daily/       # Cron jobs
│       └── logrotate.d/      # Log rotation
└── README.md
```

## Development

### Building for Testing

See [BUILD.md](BUILD.md) for detailed build instructions.

Quick start:
```bash
# Linux/WSL
make

# Or using the build script
chmod +x build.sh
./build.sh

# Windows PowerShell
.\build.ps1
```

### Creating a Release

Releases are automatically created via GitHub Actions when you push a tag:

```bash
# Tag the release
git tag 2026.02.22.0001
git push origin 2026.02.22.0001
```

Or manually trigger the workflow from GitHub Actions page.

The workflow will:
1. Build the utils package
2. Calculate SHA256
3. Update the plg file with the correct SHA256
4. Create a GitHub release with all assets
5. Provide installation URL for easy testing

### Testing Changes

1. Make your changes to files in `src/`
2. Create a PR to test the build
3. The PR check workflow will validate:
   - Package structure
   - PHP syntax
   - Required files presence
4. After merge, create a tag to trigger release build

### Testing

To test the plugin during development:

1. Place the plugin files in `/usr/local/emhttp/plugins/easytier/`
2. Make scripts executable: `chmod +x /usr/local/emhttp/plugins/easytier/*.sh`
3. Restart the service: `/etc/rc.d/rc.easytier restart`
4. Check logs: `tail -f /var/log/easytier.log` and `/var/log/easytier-utils.log`

## Troubleshooting

### Service not starting

Check the service logs:
```bash
tail -f /var/log/easytier.log
```

### Interface not appearing in Unraid

1. Ensure "Include Interface in Unraid" is enabled in settings
2. Check that IP forwarding is enabled: `sysctl net.ipv4.ip_forward`
3. Verify the interface exists: `ip link show easytier0`

### Peers not connecting

1. Verify network name and secret match across all peers
2. Check firewall settings
3. Review EasyTier logs for connection errors

## Credits

Based on the [unraid-tailscale](https://github.com/unraid/unraid-tailscale) plugin by Derek Kaser.

## License

GPLv3 - See [LICENSE](LICENSE) file for details
