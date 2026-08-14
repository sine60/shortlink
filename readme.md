# 短链接系统（PHP 版）部署说明

一个基于 **PHP + SQLite** 的短链接服务，含登录鉴权、短链创建/编辑/删除、有效期、访问上限、失效等功能。

---

## 一、环境要求

- **PHP 7.4 及以上**
- 需启用 **`pdo_sqlite`** 扩展（宝塔面板默认已开启）
- 网站运行目录指向项目根目录，**默认文档设为 `index.php`**
- 数据库使用 SQLite，无需单独安装 MySQL

---

## 二、全新部署步骤

### 1. 上传并解压
将项目文件上传到网站根目录并解压，得到 `index.php`、`includes/`、`templates/`、`database/` 等。

### 2. 配置 Nginx 伪静态（关键）
如果不配这一步，登录、所有 `/api/*` 接口、短链跳转 `/xxxx` 全部会 404，前端会报“网络错误，请稍后重试”。

宝塔面板 → 网站 → 站点 → **伪静态**，粘贴：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ ^/(includes|templates|database)/ {
    deny all;
    return 403;
}
```

裸 Nginx：把上面两段放进站点的 `server { }` 块，然后 `nginx -s reload`。

### 3. 设置目录写权限（关键）
SQLite 首次访问需要在 `database/` 下生成数据库文件，目录必须可写：

```bash
chmod -R 755 database
# 宝塔可把 database 目录属主设为 www
```

### 4. 首次访问自动初始化
打开 `https://你的域名/`，PHP 会自动在 `database/` 下：
- 创建 `shortlink.db`
- 建表（含 `disabled` 字段；若为旧库会自动 `ALTER` 补列，不破坏已有数据）
- 种入默认管理员账号

### 5. 登录并改密（关键）
使用默认账号登录：

```
用户名：admin
密码：admin
```

**生产环境请第一时间在后台修改默认密码。**

---

## 三、验证部署是否成功

浏览器直接访问 `https://你的域名/api/login`：

- ✅ 看到 JSON（如 `{"error":"..."}`）→ 伪静态生效、PHP 正常
- ❌ 看到 nginx 的 404 页面 → 伪静态没配好，回到第二步

---

## 四、升级 / 覆盖更新（不丢数据）

本系统升级是**非破坏性**的：

- 代码用 `CREATE TABLE IF NOT EXISTS` 建表，绝不会删表；
- 首次访问时会 `ALTER TABLE links ADD COLUMN` 自动补齐缺失的 `disabled` / `remark` 列，**旧数据行全部保留**，新列取默认值（失效=0、备注=空）；
- 部署包**已排除 `shortlink.db`**（也不含 `-wal`/`-shm`），正常覆盖不会动到线上数据库；
- 无论线上是更早的哪个版本（有没有 `disabled`/`remark` 列），这套 `ALTER` 都是**幂等向前兼容**的，升级均不丢数据。

> ⚠️ **唯一会丢数据的情况**：部署时把服务器上的 `database/` 目录删掉、或「先清空目录再解压」——因为库文件不在包里，清空前目录后生产库就真没了。

**安全升级步骤：**

1. **部署前先备份整个 `database/` 目录**（连同 `shortlink.db`、`shortlink.db-wal`、`shortlink.db-shm` 三个文件一起拷走）。项目开了 **WAL 模式**，单独只拷 `.db` 可能缺最新写入，务必三个一起备份。
2. 用「覆盖 / 合并」方式上传解压——**不要勾选“清空目录再上传”**，**不要删除 `database/`**。
3. 只需覆盖部署包内的文件即可（关键是 `includes/db.php`、`index.php`、`templates/dashboard.php` 三个，缺一不可）。
4. 部署完成后浏览器**硬刷新**（Ctrl+Shift+R）避免旧 JS 缓存导致功能异常。
5. 首次访问会自动补齐字段，已有短链、账号全部照常工作。

---

## 五、关键注意事项（别漏）

| 项目 | 说明 |
|---|---|
| **伪静态必须配** | 否则登录和所有接口、短链跳转都废 |
| **`database/` 写权限** | SQLite 建库需要，否则首页报数据库错误 |
| **改默认密码** | `admin/admin` 仅用于首次登录，生产务必改 |
| **时区** | 代码已按 `Asia/Shanghai`（北京时间）显示；`created_at` 库内存 UTC，前端按 UTC 解析后显示本地时间，**无需手动处理时区** |
| **覆盖更新不会丢数据** | 部署包已排除本地 `shortlink.db`，按「四、升级」步骤覆盖上传不会覆盖线上数据库 |

---

## 六、功能说明

- **创建短链**：填原网址、可选短码、可选有效期（精确到秒）、可选访问上限。
- **编辑**：带有效期的短链也能正常编辑；编辑保存后会自动将 `disabled` 复位为 0（重新生效）。
- **失效**：操作列点「失效」→ 短链立即失效，访问返回 410 已失效页；状态显示“失效”。**只有点「编辑」并保存后，才会再次生效**。
- **删除**：手动删除才从数据库移除；过期/达上限的短链不会被自动删除，只是拒绝跳转（返回 410），记录保留。
- **有效期选择器**：自定义的日期时间弹窗，含日历 + 时分秒 + 底部按钮 `清除 | 00:00:00 | 23:59:59 | 今天`，以及年/月翻页（`<<` `‹` … `›` `>>`）。

---

## 七、数据库说明

### 存储方式
**账号信息和短链信息都保存在同一个 SQLite 数据库文件中**：`database/shortlink.db`。无需单独安装数据库服务，也无需分开管理。

库内含两张表，通过 `links.user_id → users.id` 外键关联（每条短链都记录了创建它的账号）：

**`users` 表（账号信息）**

| 字段 | 说明 |
|---|---|
| `id` | 主键 |
| `username` | 用户名 |
| `password_hash` | 密码的 bcrypt 哈希（**不存明文**） |
| `created_at` | 账号创建时间 |

> 默认管理员 `admin/admin` 也是种在这张表里，不是写在配置文件里。

**`links` 表（短链信息）**

| 字段 | 说明 |
|---|---|
| `id` | 主键 |
| `user_id` | 所属账号 id（外键关联 users.id） |
| `short_code` | 短码 |
| `original_url` | 原网址 |
| `expires_at` | 有效期，UTC 时间；为空表示永久 |
| `max_visits` | 访问上限；为空表示不限 |
| `visit_count` | 已访问次数 |
| `disabled` | 是否失效，`0`=有效，`1`=失效 |
| `remark` | 备注（列表展示，可编辑） |
| `created_at` | 创建时间，UTC（由 SQLite `CURRENT_TIMESTAMP` 生成） |

### 备份与迁移注意事项
- **备份账号和短链数据，只需备份 `database/` 目录即可**——两者都在 `shortlink.db` 一个文件里。
- 项目开启了 **WAL 模式**，运行时目录里除 `shortlink.db` 外，还会有 `shortlink.db-wal`、`shortlink.db-shm` 两个文件。完整备份时**建议三个文件一起拷**；或先停站/确保数据落盘，再单独拷 `shortlink.db`，否则可能缺最新写入。
- 部署包已**排除 `shortlink.db`**：全新部署会自动建库；覆盖更新不会覆盖线上已有的账号和短链数据。

---

## 八、常见问题排查

- **登录报“网络错误，请稍后重试”** → 伪静态没配，浏览器拿到 404 HTML 而非 JSON。
- **点「失效」提示“操作失败”** → 需同时部署 `includes/db.php` + `index.php` + `templates/dashboard.php` 三个文件，`db.php`/`index.php` 缺一不可。
- **创建时间显示差 8 小时** → 确保部署了含时区修复的版本（`includes/db.php` 顶部有 `date_default_timezone_set('Asia/Shanghai')`，`dashboard.php` 的 `parseDate` 会把无时区时间按 UTC 解析）。
- **有效期弹窗没有 00:00:00 / 23:59:59 按钮** → 用的是自写弹窗，需确认 `templates/dashboard.php` 为最新版。
- **升级后功能异常 / 看着像没更新** → 多半是浏览器缓存，按 Ctrl+Shift+R 硬刷新；并确认 `includes/db.php`、`index.php`、`templates/dashboard.php` 三个文件都已覆盖为最新版。
