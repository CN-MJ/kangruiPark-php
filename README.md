# 赣南健康城 PHP API

赣南健康城微信小程序后端 API，基于 Laravel 11.x 构建。

## 环境要求

- PHP >= 8.2
- Composer
- MySQL >= 5.7 / 8.0
- 扩展：pdo_mysql、mbstring、openssl、tokenizer、xml、ctype、json、bcmath

## 安装步骤

### 1. 克隆/进入项目目录

```bash
cd kangruiPark-php
```

### 2. 安装依赖

```bash
composer install
```

### 3. 配置环境变量

```bash
cp .env.example .env
```

编辑 `.env` 文件，配置数据库连接：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kangruidb
DB_USERNAME=root
DB_PASSWORD=your_password

# 园区停车位总数（可选，默认 100）
TOTAL_PARKING_SPOTS=100
```

### 4. 生成应用密钥

```bash
php artisan key:generate
```

### 5. 创建数据库

在 MySQL 中创建空数据库（若尚未创建）：

```sql
CREATE DATABASE kangruidb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. 执行数据库迁移

```bash
php artisan migrate
```

### 7. 启动开发服务

```bash
php artisan serve --port=3922
```

服务默认运行在 `http://localhost:3922`，API 前缀为 `/api`。

## API 统一响应格式

```json
{
  "code": 200,
  "msg": "success",
  "data": {}
}
```

- `code` 为 200 表示成功，非 200 表示错误
- `msg` 为提示信息
- `data` 为业务数据

## API 接口列表

### 预约模块 `/api/appointments`

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/appointments` | 获取预约列表，支持 `?user_phone=手机号` 筛选 |
| POST | `/api/appointments` | 提交预约 |
| PATCH | `/api/appointments/{id}/cancel` | 取消预约 |

**提交预约请求体：**

```json
{
  "user_name": "张三",
  "user_phone": "13800138000",
  "appointment_time": "2026-06-20 14:00:00",
  "resource_name": "会议室A"
}
```

### 停车模块 `/api/parking`

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/parking/remaining` | 查询剩余车位 |
| POST | `/api/parking/entry` | 提交停车预约（入场） |
| PATCH | `/api/parking/{id}/exit` | 出场 |

**入场请求体：**

```json
{
  "plate_number": "粤B12345",
  "entry_time": "2026-06-15 10:00:00"
}
```

`entry_time` 可选，不传则使用当前时间。

**出场请求体（可选）：**

```json
{
  "exit_time": "2026-06-15 18:00:00"
}
```

### 新闻模块 `/api/news`

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/news` | 新闻列表（每页 10 条，置顶优先，按发布时间倒序） |
| GET | `/api/news/{id}` | 新闻详情 |

### 管理后台 API `/api/admin`（需 Bearer Token）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/admin/login` | 管理员登录 |
| POST | `/api/admin/logout` | 退出登录 |
| GET | `/api/admin/dashboard` | 数据概览 |
| GET | `/api/admin/appointments` | 预约列表 |
| PATCH | `/api/admin/appointments/{id}/cancel` | 取消预约 |
| GET | `/api/admin/parking/records` | 停车记录 |
| PATCH | `/api/admin/parking/{id}/exit` | 确认出场 |
| GET/POST/PUT/DELETE | `/api/admin/news` | 新闻管理 |
| PATCH | `/api/admin/news/{id}/top` | 置顶/取消置顶 |

**默认管理员账号**（执行 `php artisan db:seed --class=AdminSeeder` 后）：

- 账号：`admin`
- 密码：`admin123`

## 数据表说明

| 表名 | 说明 |
|------|------|
| `appointments` | 预约记录 |
| `parking_records` | 停车记录 |
| `news` | 新闻资讯 |
| `admins` | 管理员账号 |
| `admin_tokens` | 管理员登录 Token |

## 配置说明

停车位总数可在 `.env` 中通过 `TOTAL_PARKING_SPOTS` 配置，或在 `config/app.php` 的 `total_parking_spots` 项中修改。

剩余车位计算公式：`总车位数 - 未离场记录数（status=0）`

## 开发说明

- 控制器位于 `app/Http/Controllers/Api/`
- 路由定义在 `routes/api.php`
- 暂无需用户认证，通过手机号或车牌号识别用户
- 代码规范遵循 PSR-12

## 测试示例

```bash
# 查询剩余车位
curl http://localhost:3922/api/parking/remaining

# 提交预约
curl -X POST http://localhost:3922/api/appointments \
  -H "Content-Type: application/json" \
  -d "{\"user_name\":\"张三\",\"user_phone\":\"13800138000\",\"appointment_time\":\"2026-06-20 14:00:00\",\"resource_name\":\"会议室A\"}"

# 新闻列表
curl http://localhost:3922/api/news
```

## License

MIT
"# kangruiPark-php" 
