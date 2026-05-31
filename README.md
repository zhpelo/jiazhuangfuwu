# 联塑家装管

基于原生 PHP 8.4 + SQLite + 原生 JavaScript 的水电电子质保卡查询手机网页系统，适配微信内置浏览器使用场景。


默认用户名：admin
默认密码：admin123

## 功能概览

- `admin.php`
  - 管理员登录/退出
  - 施工员新增、编辑、删除
  - 客户筛选、查看图片、删除
  - 图片筛选、单张删除
  - 统计概览
- `worker.php`
  - 施工员手机号+密码登录
  - 新增客户并生成客户二维码
  - 上传/删除施工图片
  - 查看客户二维码与客户页面
  - 修改个人资料与密码
- `user.php?customer_id=xxx`
  - 客户信息展示
  - 施工员信息展示
  - 施工图片网格和原生模态放大
  - 联系施工员/客服电话

## 文件说明

- `index.php`：系统默认导航页
- `admin.php`：管理后台入口
- `worker.php`：施工员入口
- `user.php`：用户查看入口
- `api_admin.php`：后台 AJAX API
- `api_worker.php`：施工员 AJAX API
- `api_common.php`：公共函数、SQLite 建库、权限、安全、上传、二维码、删除逻辑
- `style.css`：全局样式
- `db.sqlite`：首次访问自动生成
- `uploads/`：运行时生成，包含二维码、原图、缩略图

## 默认账号

- 管理员
  - 用户名：`admin`
  - 密码：`admin123`
- 测试施工员
  - 手机号：`13800000000`
  - 密码：`123456`

## 部署步骤

1. 将整个项目放到支持 PHP 8.4 的 Web 目录，例如 `/www/wwwroot/liansu-progress/`。
2. 确保 PHP 已启用以下扩展：
   - `sqlite3`
   - `gd`
   - `fileinfo`
   - 建议启用 `curl`
3. 确保项目目录具备写权限，至少以下路径可写：
   - 项目根目录（用于生成 `db.sqlite`）
   - `uploads/`
4. 通过浏览器访问 `index.php`、`worker.php` 或 `admin.php`。
5. 首次访问任一页面时会自动：
   - 创建 `db.sqlite`
   - 创建数据库表
   - 创建默认管理员
   - 创建默认测试施工员

## 上传与环境建议

- 单张图片限制为 5MB。
- 如服务器默认上传限制较低，可调整 `php.ini`：

```ini
upload_max_filesize = 20M
post_max_size = 24M
max_file_uploads = 20
```

- 修改后重启 PHP-FPM 或 Web 服务。

## 二维码说明

- 客户二维码内容为：`user.php?customer_id=客户ID` 的完整访问地址。
- 系统优先使用 `quickchart.io` 生成二维码，失败时自动回退到 `api.qrserver.com`。
- 二维码会保存为本地图片文件，便于后续打印或转发。

## 安全与权限

- 管理员 Session：`admin_logged_in`
- 施工员 Session：`worker_logged_in`、`worker_id`
- 施工员 API 只能操作自己名下的客户与图片
- 删除客户/施工员时会同步删除二维码、原图、缩略图和数据库记录
- 密码统一使用 `password_hash()` / `password_verify()`

## 目录生成规则

- 客户原图：`uploads/customer_{customer_id}/`
- 客户缩略图：`uploads/thumbnails/customer_{customer_id}/`
- 客户二维码：`uploads/qrcodes/customer_{customer_id}.png`

## 本地检查命令

```bash
php -l api_common.php
php -l api_admin.php
php -l api_worker.php
php -l admin.php
php -l worker.php
php -l user.php
```

