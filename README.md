# Text Protocol Server

> 基于 Hyperf 框架的 TCP 文本协议服务器，支持多种命令处理和编码自动转换

## 项目简介

Text Protocol Server 是一个运行在 Swoole 协程之上的 TCP 服务器，采用文本协议与客户端通信。它提供以下核心功能：

- **数学运算**: 乘法、除法、自增
- **树形结构转换**: 将扁平化的分类数据转换为层级树结构
- **编码自动转换**: 支持 GBK/GB2312/GB18030 等中文编码自动转换为 UTF-8

## 技术栈

| 技术 | 版本 | 说明 |
|------|------|------|
| PHP | >= 8.1 | 运行环境 |
| Swoole | >= 5.0 | 协程扩展 |
| Hyperf | ~3.1.0 | PHP 协程框架 |
| Docker | - | 容器化部署 |

## 快速开始

### 环境要求

- PHP >= 8.1
- Swoole PHP 扩展 >= 5.0（需在 php.ini 中设置 `swoole.use_short_name = Off`）
- Composer

### 本地开发

```bash
# 1. 安装依赖
composer install

# 2. 启动 HTTP 服务器（端口 8765）
php bin/hyperf.php start

# 或使用异步启动
php bin/hyperf.php start -d
```

### Docker 部署

```bash
# 构建并启动容器
docker-compose up -d

# 查看日志
docker-compose logs -f
```

## 架构设计

### 整体架构

```
┌─────────────────────────────────────────────────────────────┐
│                      Text Protocol Server                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐  │
│  │  TcpServer   │───▶│ Command      │───▶│   Commands   │  │
│  │  (TCP 端口)   │    │  Handler     │    │  (mul/div/   │  │
│  │              │    │              │    │   incr/      │  │
│  │              │    │              │    │  conv_tree)  │  │
│  └──────────────┘    └──────────────┘    └──────────────┘  │
│         │                   │                                  │
│         │            ┌──────┴──────┐                          │
│         │            │  Contract   │                          │
│         │            │ (Interface) │                          │
│         │            └─────────────┘                          │
│         │                                                     │
│  ┌──────┴──────┐                                              │
│  │   Swoole    │                                              │
│  │   Server    │                                              │
│  └─────────────┘                                              │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### 核心组件

#### 1. TcpServer (`app/Server/TcpServer.php`)

TCP 服务器核心类，负责：
- 客户端连接管理
- 数据接收与缓冲区处理
- 命令解析与分发
- 编码自动转换

```
客户端连接 → onConnect → 发送欢迎信息
     │
     ▼
接收数据 → onReceive → 缓冲区处理 → 按行分割 → processLine
     │
     ▼
命令解析 → CommandHandler.execute() → 返回结果 → 发送响应
     │
     ▼
客户端关闭 → onClose → 清理缓冲区
```

#### 2. CommandHandler (`app/Command/CommandHandler.php`)

命令处理器，采用**策略模式**处理各种命令：

| 命令 | 功能 | 示例 |
|------|------|------|
| `mul` | 乘法运算 | `mul 10 5` → `Result: 50.00` |
| `div` | 除法运算 | `div 100 4` → `Result: 25.00` |
| `incr` | 自增运算 | `incr 99` → `Result: 100` |
| `conv_tree` | 树形转换 | `conv_tree [{"namePath":"a,b,c","id":1}]` |

#### 3. ConvTreeCommand (`app/Command/ConvTreeCommand.php`)

树形结构转换器，将扁平化的分类数据转换为层级树：

**输入格式：**
```json
[
  {"namePath": "电子产品,手机,iPhone", "id": 1},
  {"namePath": "电子产品,电脑,MacBook", "id": 2}
]
```

**输出格式：**
```json
[
  {
    "name": "电子产品",
    "level": 1,
    "children": [
      {
        "name": "手机",
        "level": 2,
        "children": [
          {"name": "iPhone", "level": 3, "id": 1}
        ]
      }
    ]
  }
]
```

#### 4. 接口设计

项目采用**依赖倒置原则**，定义清晰的接口：

- `CommandHandlerInterface`: 命令处理器接口
- `JsonFormatterInterface`: JSON 格式化接口

### 设计模式应用

| 模式 | 应用位置 | 说明 |
|------|----------|------|
| 策略模式 | CommandHandler | 根据命令类型选择处理策略 |
| 依赖注入 | 构造函数 | 通过容器注入依赖 |
| 单一职责 | 各类 | 每个类只负责一项功能 |
| 接口隔离 | Contract 目录 | 定义最小化接口 |

## 配置说明

### 环境变量 (.env)

```env
APP_NAME=skeleton
APP_ENV=dev

# 数据库配置
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=hyperf
DB_USERNAME=root
DB_PASSWORD=

# Redis 配置
REDIS_HOST=localhost
REDIS_PORT=6379
```

### 服务器配置 (config/autoload/server.php)

- **HTTP 服务器**: 端口 8765
- **工作进程数**: CPU 核心数
- **最大协程数**: 100000
- **启用 HTTP/2**: 是

## 客户端连接示例

```bash
# 使用 telnet 连接
telnet localhost 8765

# 或使用 netcat
nc localhost 8765

# 发送命令
mul 10 20
div 100 4
incr 5
conv_tree [{"namePath":"a,b,c","id":1}]
```

## 项目结构

```
text-protocol-server/
├── app/
│   ├── Command/          # 命令处理
│   │   ├── CommandHandler.php
│   │   ├── ConvTreeCommand.php
│   │   ├── DefaultJsonFormatter.php
│   │   └── TreeNode.php
│   ├── Contract/         # 接口定义
│   │   ├── CommandHandlerInterface.php
│   │   └── JsonFormatterInterface.php
│   ├── Controller/       # HTTP 控制器
│   ├── Exception/        # 异常处理
│   ├── Listener/         # 事件监听
│   ├── Model/            # 数据模型
│   └── Server/           # TCP 服务器
│       └── TcpServer.php
├── config/               # 配置文件
├── bin/                  # 入口脚本
├── runtime/              # 运行时文件
├── Dockerfile            # 生产镜像
├── docker-compose.yml    # Docker 编排
└── composer.json         # 依赖管理
```

## 扩展开发

### 添加新命令

1. 在 `app/Command/` 目录创建新的命令类
2. 在 `CommandHandler.php` 的 `execute` 方法中添加匹配逻辑

```php
protected function handleNewCommand(array $params): string
{
    // 实现你的命令逻辑
    return "Result: ...";
}
```

### 修改 TCP 端口

编辑 `config/autoload/server.php` 中的端口配置：

```php
'servers' => [
    [
        'name' => 'http',
        'port' => 9502,  // 修改为其他端口
        // ...
    ],
],
```

## 性能优化

- 使用 Swoole 协程并发处理请求
- 启用 HTTP/2 协议支持
- 配置 `enable_request_lifecycle: false` 减少开销
- 调优 `worker_num` 匹配 CPU 核心数

## 许可证

[Apache-2.0](LICENSE)
