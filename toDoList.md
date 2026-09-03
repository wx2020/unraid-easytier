# Unraid EasyTier — 项目问题清单与待办

> 生成时间：2026-09-03　|　基于 `VERSION=2026.08.06.0002` 的全量代码走读（`src/` + `plugin/` + `.github/workflows/` + `tests/` + `translations/` + 构建脚本）  
> 约定：`file_path:line_number` 可直接跳转；优先级 `P0=阻断/安全` `P1=重要` `P2=一般` `P3=优化`

---

## 1. 安全（Security）— P0/P1

| # | 优先级 | 问题 | 位置 | 说明/风险 | 建议修复 |
|---|--------|------|------|-----------|----------|
| S-01 | **P0** | `rc.easytier:65` 使用 `eval "exec ... $EASYTIER_CUSTOM_PARAMS"` | `src/usr/local/etc/rc.d/rc.easytier:65` | `eval` 叠加 `custom-params.sh` 中的双层转义极易导致命令注入；若上游参数校验被绕过可执行任意命令 | 去掉 `eval`，改用 `set -- $EASYTIER_CUSTOM_PARAMS` + `exec` 数组或直接 `sh -c` 白名单参数；`System.php:259-264` 不应双重 `escapeshellarg`（先对每个 param 转义，又对整串再转义） |
| S-02 | **P0** | `System.php:259-260` 双重转义导致启动参数语义错误且掩盖注入 | `src/usr/local/php/easytier-utils/easytier-utils/System.php:259` | `$encoded = array_map('escapeshellarg', $params); file_put ... escapeshellarg(implode(' ', $encoded))` 会产生成 `''--network-name' 'foo''` 这类双引号嵌套，`eval` 解开后行为不可控 | 只做一层转义：`implode(' ', array_map('escapeshellarg', $params))`，`rc.easytier` 侧用 `eval`-free 启动 |
| S-03 | **P0** | `/boot/config/plugins/easytier/easytier.cfg`、`easytier.toml`、`custom-params.sh` 存明文 `NETWORK_SECRET` 且权限 0644 | `src/usr/local/php/easytier-utils/easytier-utils/Config.php:58`、`src/usr/local/emhttp/plugins/easytier/include/save_config_file.php:62`、`src/usr/local/php/easytier-utils/easytier-utils/System.php:260` | 任意本地用户可读密钥 | 写文件后 `chmod 0600`，`doinst.sh` 保证 `/boot/config/plugins/easytier` 目录 `0700`；日志中禁止打印 secret |
| S-04 | **P1** | `include/save_settings.php` 未校验 CSRF | `src/usr/local/emhttp/plugins/easytier/include/save_settings.php:1` | 同目录下 `save_config_file.php:28`、`clear_log.php:34`、`restart_service.php:32` 均校验 `csrf_token`，唯独此文件缺失；若被直接 POST 可绕过 | 引入 `requireCsrfToken($_POST['csrf_token'] ?? null)`，并在对应前端表单补 token |
| S-05 | **P1** | `Pages/Logs.php:32` `$_GET['lines']` 仅做 `intval` 范围裁剪，未做类型白名单；`clear_log.php:31` 信任客户端传入的 `log_file` 路径（虽有白名单但错误信息泄露） | `src/usr/local/emhttp/plugins/easytier/include/Pages/Logs.php:32`、`src/usr/local/emhttp/plugins/easytier/include/clear_log.php:44` | 风险低但属于不一致的输入校验 | 保持现有白名单，统一返回通用错误文案，避免路径枚举 |
| S-06 | **P1** | `Utils::runwrap()` / `System::getNodeOutput()` 直接拼接 `easytier-cli` 输出到 HTML（仅 `htmlspecialchars` 一层） | `src/usr/local/emhttp/plugins/easytier/include/Pages/Dashboard.php:32` | 若对端伪造 hostname 含 `<script>`，虽已转义但 `easytier-cli node` 输出未做长度/字符截断，可能导致超大 `<pre>` 撑爆 WebUI | 对 `getNodeOutput()` 结果做 `mb_strcut(..., 200KB)` 截断并保留转义 |
| S-07 | **P1** | `save_config_file.php:55` 写入任意 `config_content` 未做大小/类型限制 | `src/usr/local/emhttp/plugins/easytier/include/save_config_file.php:55` | 攻击者可写入数 MB/GB 导致 `/boot` 填满（Unraid 的 `/boot` 是 FAT U盘，空间小） | 增加 `strlen($config_content) > 256*1024` 拒绝，校验 TOML 基础语法（至少 `toml` 解析或 `easytier-core --help` 校验） |
| S-08 | **P2** | `easytier-watcher.php:43` `shell_exec('pgrep ...')` 依赖 `pgrep --ns $$` 在容器/非 Unraid 环境不可用 | `src/usr/local/emhttp/plugins/easytier/easytier-watcher.php:43` | 误判进程存活导致频繁重启 | 统一通过 `System::isRunning()` 复用逻辑，避免两处实现分叉 |

---

## 2. 可靠性与健壮性（Reliability）

| # | 优先级 | 问题 | 位置 | 说明 | 建议修复 |
|---|--------|------|------|------|----------|
| R-01 | **P0** | `watcher` 在 `ENABLE=0` 时 `break` 退出，之后即使用户重新启用也不会再被拉起 | `src/usr/local/emhttp/plugins/easytier/easytier-watcher.php:105-108` | 必须重启系统或手动执行 `rc.easytier restart` 才能恢复监控 | 改为 `sleep` 轮询 `Config::Enable` 而不是 `break`，或由 `rc.easytier start` 负责单例拉起并在 `stop` 时优雅退出 |
| R-02 | **P0** | `/var/log/easytier-utils.log` 无轮转，`easytier.log` 已配置 `logrotate` 但 `postrotate` 用 `killall -HUP` 对 `easytier-core` 未必生效 | `src/usr/local/php/easytier-utils/log.sh:5`、`src/etc/logrotate.d/easytier:10` | `easytier-core` 持续追加日志可写满 `/var/log`（tmpfs） | 为 `easytier-utils.log` 增加独立 `logrotate` 段；`postrotate` 改为 `kill -HUP` 前先 `test -f /var/run/easytier.pid` 或直接 `copytruncate` |
| R-03 | **P1** | `rc.easytier:wait_for_network()` 超时 60s 后仍无条件 `return 0` 启动核心，若此时网络未就绪会导致 `easytier-core` 早期绑定失败且不再重试（依赖 watcher 10s 后重启） | `src/usr/local/etc/rc.d/rc.easytier:28` | 启动抖动、日志噪音 | 超时后记录 `WARNING` 并让 `watcher` 缩短首轮重试间隔，或在 `start_easytier` 中失败即返回非 0 触发外层重试 |
| R-04 | **P1** | `network_is_ready()` 第二分支 `ip -o addr show scope global \| awk` 对 `easytier0` 过滤不完整，`lo` 有 `127.0.0.1/8` 虽是 `scope host` 不会命中，但 `docker0` 等虚拟口会误判为“网络就绪” | `src/usr/local/etc/rc.d/rc.easytier:18` | 可能在物理网卡未 UP 时就启动 | 增加 `state UP` 过滤：`ip -o link show up` + `addr show ...` 联合判断，或直接检测 `ip route get 1.1.1.1` 可达性 |
| R-05 | **P1** | `System::getPeers()` 按 `\|` 分割 `easytier-cli peer` 输出，强依赖上游表格格式，版本升级即失效 | `src/usr/local/php/easytier-utils/easytier-utils/System.php:59` | 已有用户反馈格式变更导致 `AddPeersToHosts` 静默失效 | 优先尝试 `easytier-cli peer --json`（若上游支持）或增加表头行自动识别；失败时记录 `logwrap` |
| R-06 | **P1** | `Pages/Logs.php:42-52` 用 `SplFileObject::seek(PHP_INT_MAX)` + `key()` 取总行数，空文件或超大文件行为未定义；`array_slice(...,0,-1)` 在仅 1 行时会丢数据 | `src/usr/local/emhttp/plugins/easytier/include/Pages/Logs.php:42` | 大日志（>100MB）会导致 OOM | 改用 `tail -n $lines` 管道或 `SplFileObject::fseek` 逆向读取；限制 `filesize > 5MB` 时直接提示下载 |
| R-07 | **P1** | `BaseSystem::replaceHostsEntries()` 正则 `'/\R?' . HOSTS_START ... '\R?/s'` 会吃掉相邻空行，且并发写 `/etc/hosts` 无文件锁 | `src/usr/local/php/easytier-utils/easytier-utils/BaseSystem.php:31` | 与 Unraid 原生 `hosts` 管理或多插件并发冲突可丢条目 | `file_put_contents(LOCK_EX)` 已有但正则前应 `flock` 读-改-写全程；使用 `preg_replace` 后 `rtrim` 改为保留一空行 |
| R-08 | **P1** | `restart.sh` 固定 `sleep 5` 后 `exec rc.easytier restart`，连续点击“Apply/Restart”会堆积多个 `nohup` 实例 | `src/usr/local/emhttp/plugins/easytier/restart.sh:6` | 可能并发 `stop/start` 导致 pid 竞态 | 增加 `flock /var/lock/easytier-restart.lock -n` 或在 `rc.easytier` 入口加 `exec 9>/var/lock/...; flock -n 9` |
| R-09 | **P2** | `Config::__construct()` 每次 `parse_ini_file('/boot/config/plugins/easytier/easytier.cfg')` 无缓存，`watcher` 每 10s 构造一次 | `src/usr/local/php/easytier-utils/easytier-utils/Config.php:47` | 轻微 IO 放大 | watcher 循环中复用或加 1s 缓存 |
| R-10 | **P2** | `System::isRunning()` 注释 `@return array<string>` 与实际 `bool` 不符 | `src/usr/local/php/easytier-utils/easytier-utils/System.php:79` | 静态分析误报 | 修正 docblock |
| R-11 | **P2** | `daily.php:29` 当 `Enable=0` 直接 `exit(0)`，但 `pre-startup.php:39` 仍在 `Enable=0` 时清空 `hosts` 托管块，二者语义不一致 | `src/usr/local/php/easytier-utils/daily.php:29`、`src/usr/local/php/easytier-utils/pre-startup.php:39` | 禁用后 hosts 残留 | 统一：禁用时是否清理 hosts 应由 `AddPeersToHosts` 决定，并在 `stop_easytier` 中也清理 |

---

## 3. 代码质量与可维护性（Code Quality）

| # | 优先级 | 问题 | 位置 | 说明 | 建议修复 |
|---|--------|------|------|------|----------|
| C-01 | **P1** | `Makefile` 与 `build.sh` 构建产物不一致：`Makefile` 未打包 `translations/zh_CN/...`，`build.sh` 已打包 | `Makefile:22` vs `build.sh:33` | `make` 产物缺少翻译，导致 CI `cmp` 失败但本地可复现不一致 | `Makefile` 补齐 `cp translations/...` 段，或统一由单一脚本生成 |
| C-02 | **P1** | `Config::$InstanceId` 定义但从未参与启动参数；`$Proxy` 语义是 SOCKS5 端口却命名为 `PROXY` | `src/usr/local/php/easytier-utils/easytier-utils/Config.php:38-40`、`src/usr/local/emhttp/plugins/easytier/include/Pages/Settings.php:175` | 暴露未完成功能，误导用户 | 要么实现 `--instance-id` 透传，要么移除字段；`PROXY` 重命名为 `SOCKS5_PORT` 并做兼容迁移 |
| C-03 | **P1** | `Utils::printRow/printDash` 死代码；`Utils::ip4_in_network` 无处调用 | `src/usr/local/php/easytier-utils/easytier-utils/Utils.php:34,44` | 增加维护负担 | 删除或补充单测后保留 |
| C-04 | **P2** | `Utils::logwrap()` 每次 `new Utils(PLUGIN_NAME)`，高频调用（watcher/daily）产生不必要对象 | `src/usr/local/php/easytier-utils/easytier-utils/Utils.php:71` | 性能微损耗 | 改为静态 `self::$instance` 单例或直接 `file_put_contents` |
| C-05 | **P2** | `Config::isValidServerAddress()` 对纯用户名分支 `^[A-Za-z0-9][A-Za-z0-9_.@-]{0,127}$` 过于宽松，`@` 在 URL 中有特殊含义 | `src/usr/local/php/easytier-utils/easytier-utils/Config.php:82` | 可能误判非法输入为合法 | 参照上游文档收紧为 `^[A-Za-z0-9][A-Za-z0-9_-]{1,64}$` 或明确支持 `user@domain` 格式 |
| C-06 | **P2** | `Config::hasValidServiceSettings()` 对 `Hostname` 仅校验 `preg_match('/\s/', ...)`，允许 `../`、`/` 等非法字符 | `src/usr/local/php/easytier-utils/easytier-utils/Config.php:120` | 可能导致 `hostname` 注入到 shell | 复用 `isValidServerAddress` 的 hostname 正则 |
| C-07 | **P2** | `BaseUtils::run_command()` 使用 `exec($command . " 2>&1")` 无超时、无输出长度限制 | `src/usr/local/php/easytier-utils/easytier-utils/BaseUtils.php:48` | `easytier-cli node` 卡死会阻塞 PHP-FPM | 增加 `timeout 5` 前缀或 `proc_open` 超时控制 |
| C-08 | **P2** | `EasyTier.page` / `EasyTier-1-Settings.page` / `EasyTier-2-Logs.page` 头部元数据重复，前后端未共享 | `src/usr/local/emhttp/plugins/easytier/EasyTier*.page:1` | 修改菜单需改 3 处 | 抽取公共 `Menu/Icon` 配置或脚本生成 |

---

## 4. 前端与交互（WebUI）

| # | 优先级 | 问题 | 位置 | 说明 | 建议修复 |
|---|--------|------|------|------|----------|
| W-01 | **P1** | `Settings.php:156-178` 高级区用 `<div class="advanced">` 但无 JS 折叠逻辑（依赖 Unraid 全局 `.advancedview` checkbox，未绑定 `settings.css` 显隐） | `src/usr/local/emhttp/plugins/easytier/include/Pages/Settings.php:132`、`src/usr/local/emhttp/plugins/easytier/styles/settings.css` | 用户找不到 Protocol/Listener 等字段 | 补 `settings.css` 中 `.advanced { display:none } .advancedview:checked ~ ...` 或改用 Unraid 标准 `toggle` |
| W-02 | **P1** | `Logs.php:166-179` 自动刷新用 `setInterval(refreshLogs,5000)` 且 `refreshLogs` 是整页跳转，导致定时器在 5s 内反复重载、丢失滚动位、产生历史记录污染 | `src/usr/local/emhttp/plugins/easytier/include/Pages/Logs.php:166` | 体验差 | 改为 `fetch('/plugins/easytier/include/log_tail.php?lines=...')` 局部更新 `<pre>` |
| W-03 | **P1** | `restart_service.php:33` 直接 `Utils::runwrap("/etc/rc.d/rc.easytier restart")` 同步执行，HTTP 请求会阻塞 5s+（含 `wait_for_network` 60s） | `src/usr/local/emhttp/plugins/easytier/include/restart_service.php:33` | 前端 `fetch` 超时、用户重复点击 | 改为 `restart.sh` 异步触发，立即返回 `202 Accepted` |
| W-04 | **P2** | `Dashboard.php` 仅原样输出 `easytier-cli node`，无结构化解析（IP、Peer 数、在线状态） | `src/usr/local/emhttp/plugins/easytier/include/Pages/Dashboard.php:31` | 可读性差 | 解析 JSON（若 CLI 支持 `--output json`）或表格化；增加“未运行/无数据”空状态引导 |
| W-05 | **P2** | `Settings.php:105` `SERVER_ADDRESS` 输入框无前端校验，非法输入静默回落到本地配置，用户无感知 | `src/usr/local/emhttp/plugins/easytier/include/Pages/Settings.php:105` | 误配置难排查 | 提交前用与 `Config::isValidServerAddress` 同等 JS 正则校验并提示 |
| W-06 | **P2** | `Logs.php:78-84` “50 lines” 选项实际不可达（`$lines <10 =>10` 但默认 100，50 可选却会被 `changeLineCount` 跳转）；`downloadLog()` 下载的是当前视图截断内容而非完整文件 | `src/usr/local/emhttp/plugins/easytier/include/Pages/Logs.php:78` | 功能与预期不符 | 补后端 `?lines=50` 支持；下载走 `/plugins/easytier/include/download_log.php` 流式下载 |

---

## 5. 构建、发布与 CI（Build/Release）

| # | 优先级 | 问题 | 位置 | 说明 | 建议修复 |
|---|--------|------|------|------|----------|
| B-01 | **P0** | `plugin/easytier.plg:93` 仍是 `PLACEHOLDER_SHA256`，若误直接安装源码 PLG 会校验失败；`release.yml:generate plugin manifests` 用 `sed "s/${SOURCE_VERSION}/${VERSION}/g"` 全局替换，可能误改 `CHANGES` 中的历史版本号 | `plugin/easytier.plg:93`、`.github/workflows/release.yml:63` | 发布产物不可溯源 | `sed` 改为仅替换 `<PLUGIN version="...">` 与 `unraid-easytier-utils-...txz` 两处的锚定替换 |
| B-02 | **P1** | `release.yml: Resolve latest EasyTier release` 仅取 `releases/latest`，若上游发布 `prerelease`/`draft` 或改名 `easytier-linux-x86_64-v...zip` 即失败；未校验 `asset.size` 与 `content-length` | `.github/workflows/release.yml:36` | 供应链风险 | 增加 `jq 'select(.prerelease==false and .draft==false)'` 过滤；失败时回退到 `releases/tags/vX.Y.Z` 列表；校验下载后 `unzip -t` |
| B-03 | **P1** | `plugin/easytier.plg:114` `unzip -o "$EASYTIER_ARCHIVE"` 未校验 zip 完整性、未限制解压路径（ZipSlip） | `plugin/easytier.plg:114` | 恶意镜像可写任意路径 | `unzip -o` 前 `unzip -t`；解压到临时目录并白名单 `easytier-core`/`easytier-cli` |
| B-04 | **P2** | `build.ps1:53` Windows 用 `tar -czf` 生成 gzip 而 `build.sh:41` 用 `tar --xz` 生成 xz，Unraid 虽兼容但校验和与文件名语义不一致（`.txz` 实际为 gzip） | `build.ps1:53`、`build.sh:41` | 用户验证困惑 | Windows 改用 `bsdtar --xz` 或统一改为 `.tgz` 并在文档说明 |
| B-05 | **P2** | `pr-check.yml:validate metadata` 中 `grep -q '<SHA256>PLACEHOLDER_SHA256</SHA256>'` 与发布后 `! grep -q PLACEHOLDER_SHA256` 互为相反断言，但 `plugin/easytier.plg` 源码始终含占位符，PR 永远通过而发布前需人工确认 | `.github/workflows/pr-check.yml:15` | 易遗漏 | 增加 `release.yml` 中 `grep -q PLACEHOLDER` 则 `exit 1` 的负向测试 |
| B-06 | **P3** | `ca_profile.xml:8` Icon 指向 `raw.githubusercontent.com/.../main/.../easytier.png` 非常驻 CDN，无版本 pin，CA 审核可能要求固定 commit | `ca_profile.xml:8` |  | 改为 `raw.githubusercontent.com/wx2020/unraid-easytier/<tag>/...` 或 jsDelivr |

---

## 6. 国际化与文档（i18n & Docs）

| # | 优先级 | 问题 | 位置 | 说明 | 建议修复 |
|---|--------|------|------|------|----------|
| I-01 | **P2** | `translations/en_US` 与 `zh_CN` 各 84 key 已对齐，但 `README.md:66` 说明“模板不可直接安装”未在 `README_CN.md` 同步加粗提示 | `README_CN.md:56` | 中文用户易误装源码 PLG | 同步加粗与警告框 |
| I-02 | **P3** | `validate-translations.py:11` `LOOKUP_PUNCTUATION` 正则含 `<.+?/?>` 可能误删 `A valid Config ...` 中的正常字符 | `tests/validate-translations.py:11` | 极小概率漏检 | 单测覆盖该分支 |

---

## 7. 测试覆盖（Tests）

| # | 优先级 | 问题 | 位置 | 说明 | 建议修复 |
|---|--------|------|------|------|----------|
| T-01 | **P1** | 无 `Config::isValidServerAddress` / `isValidListener` / `isValidPort` 单测；`render-pages.php` 虽覆盖 3 个页面但未覆盖 `Error` 分支的 `niceError=false` 抛异常路径 | `tests/render-pages.php:39` | 回归风险 | 新增 `tests/ConfigTest.php`（PHPUnit 或纯 PHP assert）覆盖边界：`udp://[::1]:22020/u`、`tcp://1.1.1.1:99999/u`、`""`、纯用户名等 |
| T-02 | **P2** | 未对 `System::createEasytierParamsFile` 的 3 优先级（`ServerAddress` > 本地有效配置 > `easytier.toml`）做穷举 | `tests/render-pages.php:40` | 已有 2 例，缺 `easytier.toml` 分支与空配置分支 | 补 `touch(Config::CORE_CONFIG_FILE)` 场景单测 |
| T-03 | **P2** | `validate-ca.py` 未校验 `templates/easytier.xml` 的 `Overview` 长度与 CA 要求的非空描述 | `tests/validate-ca.py:32` |  | 增加 `len(Overview.strip()) > 20` 断言 |

---

## 8. 待办清单（按优先级排序）

### P0 — 必须在下个发布前修复
- [ ] **S-01 + S-02** 重构 `rc.easytier` 启动：移除 `eval`，`System.php` 只做单层 `escapeshellarg`，`custom-params.sh` 改为 `EASYTIER_ARGS=( ... )` 数组或直接写 `/boot/config/plugins/easytier/args.txt` 供 `rc.easytier` `xargs -0` 读取
- [ ] **S-03** 敏感文件 `chmod 0600`，目录 `0700`，日志脱敏
- [ ] **R-01** 修复 `watcher` 禁用后永久退出问题
- [ ] **R-02** 为 `easytier-utils.log` 增加 `logrotate`，`easytier.log` 改 `copytruncate`
- [ ] **B-01** 修复 `release.yml` 全局 `sed` 误替换，锁定替换锚点

### P1 — 强烈建议
- [x] **S-04** 补 `save_settings.php` CSRF
- [x] **S-07** 限制 `save_config_file.php` 写入大小与 TOML 校验
- [x] **R-03/R-04** 优化 `wait_for_network` 判定与超时重试
- [x] **R-05** 兼容 `easytier-cli peer` 新格式 / JSON
- [x] **R-06** 重写 `Logs.php` 大文件读取为 `tail` 方案
- [x] **R-08** `restart.sh` / `rc.easytier` 增加 `flock` 互斥
- [x] **C-01** 统一 `Makefile` 与 `build.sh` 构建逻辑
- [x] **C-02** 清理 `InstanceId` / 重命名 `PROXY`
- [x] **W-01/W-03** 修复高级配置折叠与异步重启
- [x] **B-02/B-03** 加固发布流程与解压校验
- [x] **T-01/T-02** 补 `Config` 单测

### P2 — 一般
- [ ] **S-05/S-06/S-08** 输入输出加固与 `isRunning` 统一
- [ ] **R-07/R-09/R-10/R-11** hosts 并发、缓存、文档与禁用语义统一
- [ ] **C-03..C-08** 死代码、正则、超时、页面复用清理
- [ ] **W-02/W-04/W-05/W-06** 日志自动刷新、Dashboard 结构化、前端校验、下载完整性
- [ ] **B-04/B-05** 构建压缩格式统一与 PR 负向断言
- [ ] **I-01** 中英文档同步
- [ ] **T-03** CA 校验增强

### P3 — 优化/长期
- [ ] **B-06** Icon CDN 固定版本
- [ ] **I-02** 翻译校验正则单测
- [ ] 引入 `phpstan` / `psalm` 静态分析与 `shellcheck` 到 CI
- [ ] 将 `easytier-watcher.php` 改为 `systemd`/`supervisord` 风格或 Unraid `event` 驱动，避免常驻 PHP 进程
- [ ] 评估 `easytier-core` 以非 `root` 用户运行的可行性

---

## 9. 复现与验证建议

```bash
# 1. 语法与静态检查（本地无需 Unraid）
find src -name '*.php' -exec php -l {} \;
shellcheck src/usr/local/etc/rc.d/rc.easytier src/install/doinst.sh src/usr/local/emhttp/plugins/easytier/restart.sh

# 2. WebUI 渲染（与 CI 一致）
sudo mkdir -p /usr/local/php /usr/local/emhttp/plugins
sudo cp -a src/usr/local/php/easytier-utils /usr/local/php/
sudo cp -a src/usr/local/emhttp/plugins/easytier /usr/local/emhttp/plugins/
php tests/render-pages.php

# 3. 构建一致性
bash build.sh && make clean && make && diff -r build/unraid-* package/unraid-* || echo "mismatch"

# 4. 翻译与 CA
python tests/validate-translations.py
python tests/validate-ca.py

# 5. 安全手工验证
grep -n "eval" src/usr/local/etc/rc.d/rc.easytier
ls -l /boot/config/plugins/easytier/easytier.cfg  # 应为 0600
cat /usr/local/emhttp/plugins/easytier/custom-params.sh  # 检查双重转义
```

---

*本清单基于静态走读生成，未在真实 Unraid 6.12.14+ 环境中动态运行 `easytier-core`；`easytier-cli peer/node` 的实际输出格式建议在真机上抓包确认后再固化解析逻辑。*
