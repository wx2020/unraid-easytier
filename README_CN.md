# Unraid EasyTier 插件

[English](README.md)

适用于 Unraid OS 6.12.14 及更高版本的 EasyTier 组网插件。

## 关于 EasyTier

[EasyTier](https://github.com/EasyTier/EasyTier) 是一款基于 Rust 和 Tokio
构建的简单、安全、去中心化的异地组网工具。它无需传统的中心化 VPN
网关，即可连接位于不同 NAT 网络中的设备和网络。EasyTier 支持点对点
NAT 穿透、子网代理、智能路由、加密隧道和多种传输协议。

本仓库是一个独立的 Unraid 集成项目。它打包上游 EasyTier CLI 二进制文件，
并提供适用于 Unraid 的安装、WebUI 配置、服务管理、日志、网络接口注册和维护功能。

### EasyTier 相关资源

- [官方网站](https://easytier.cn/)
- [上游项目](https://github.com/EasyTier/EasyTier)
- [中文文档](https://easytier.cn/guide/introduction.html)
- [英文文档](https://easytier.cn/en/guide/introduction.html)
- [Web Console](https://easytier.cn/web/)
- [上游版本发布](https://github.com/EasyTier/EasyTier/releases)

## 功能

- 启动、停止并自动恢复 `easytier-core`
- 在系统/插件启动且网络就绪后启动 EasyTier，不依赖阵列自动启动
- 在 WebUI 中配置网络名称、密码、对等节点、监听地址、RPC 和主机名
- 将 `easytier0` 网络接口注册到 Unraid
- 配置 IPv4 和 IPv6 转发
- 查看、清空和下载 EasyTier 日志
- 将 EasyTier 对等节点主机名同步到 `/etc/hosts` 中由插件管理的区块
- 使用 GitHub Actions 构建和发布带版本号的 Unraid 软件包

## 安装

### 方法一：通过插件 URL 安装（推荐）

1. 在 Unraid 中打开 **Settings > Plugins > Install Plugin**。
2. 粘贴最新版本的插件 URL：

`https://github.com/wx2020/unraid-easytier/releases/latest/download/easytier.plg`

3. 选择 **Install**，等待安装完成。

### 方法二：手动安装

1. 打开[最新版本](https://github.com/wx2020/unraid-easytier/releases/latest)。
2. 下载 Release 附件中的 `easytier.plg`。
3. 在 Unraid 中打开 **Settings > Plugins > Install Plugin**。
4. 上传下载的 `easytier.plg` 文件，然后选择 **Install**。

上述 URL 始终安装最近发布的插件版本。合并到 `main` 的更改只有在
**Build and Release** 工作流发布新版本后，才会通过此 URL 提供。

源码中的 `plugin/easytier.plg` 是发布模板。工具包校验和及 EasyTier
二进制文件元数据会由发布工作流替换，因此该模板本身不能作为插件安装。

## 配置

安装完成后，打开 **Settings > Network Services > EasyTier**。

- **Enable EasyTier**：控制是否启动服务。
- EasyTier 会在系统/插件启动且主机网络就绪后启动，不依赖阵列启动。
- **Include Interface in Unraid**：将 `easytier0` 添加到 Unraid 网络设置。
- **Enable IP Forwarding**：安装插件的 sysctl 配置。
- **Add Peers to Hosts**：每天更新 `/etc/hosts` 中由插件标记的区块。
- **Config Server Address**：作为 `-w`（`--config-server`）传给 EasyTier，
  例如 `udp://easytier.example.com:22020/username`。配置有效时，EasyTier
  会从服务器获取网络配置，并忽略本地网络、监听、RPC、SOCKS5 和主机名参数。
- **Listener Address**：填写 `IP:PORT`，插件会在前面添加所选协议。
- **SOCKS5 Listen Port**：设置后启用 EasyTier SOCKS5 服务器。
- **EasyTier Config File**：编辑 `/boot/config/plugins/easytier/easytier.toml`，
  当服务配置不完整时通过 `-c` 传给 EasyTier。优先级为有效的 `-w` 地址、
  有效的服务配置，最后才是该配置文件。

## 开发

版本号保存在 [`VERSION`](VERSION) 中。

```bash
# Linux、WSL 或 macOS
bash build.sh

# Windows PowerShell
powershell -ExecutionPolicy Bypass -File .\build.ps1
```

构建会生成：

- `unraid-easytier-utils-<version>-noarch-1.txz`
- `unraid-easytier-utils-<version>-noarch-1.txz.sha256`

Pull Request 会检查 PHP 和 Shell 语法、元数据一致性、软件包内容及生成的校验和。

## 发布

推送与发布版本一致的版本标签，或在 **Build and Release** 工作流中指定版本：

```bash
git tag 2026.08.06.0002
git push origin 2026.08.06.0002
```

工作流会通过 EasyTier GitHub Releases API 查询最新的稳定版 Linux x86_64
压缩包，计算 SHA256 并验证下载的文件，然后构建工具包和生成可安装的 PLG
文件。所选 EasyTier 版本会固定在对应插件版本中；已安装的插件不会查询 API，
也不会在未通知用户的情况下切换到更新的上游二进制版本。

## Community Applications

仓库包含用于提交到 Unraid Community Applications 的 `ca_profile.xml` 和
`templates/easytier.xml`。CA 条目会安装 GitHub 最新 Release 中生成的
`easytier.plg` 附件。

插件支持和问题反馈请通过
[GitHub Issues](https://github.com/wx2020/unraid-easytier/issues) 提交。

## 故障排查

```bash
/etc/rc.d/rc.easytier restart
tail -f /var/log/easytier.log
tail -f /var/log/easytier-utils.log
easytier-cli peer
ip link show easytier0
```

## 许可证

本项目采用 [GNU General Public License v3.0](LICENSE) 许可证。
