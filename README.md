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

1. Download the latest `easytier.plg` file from the [Releases](https://github.com/yourusername/unraid-easytier/releases) page
2. In Unraid, go to **Settings** → **Plugins** → **Install Plugin**
3. Paste the URL to the `easytier.plg` file or upload it directly
4. Click **Install**

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

### Building the Plugin

1. Update version numbers in `plugin/easytier.plg`
2. Update download URLs and SHA256 checksums
3. Build the utils package:
   ```bash
   cd src
   makepkg -l y -c y ../unraid-easytier-utils-<version>-noarch-1.txz
   ```

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
