#!/usr/bin/env php
<?php
/**
 * Text Protocol Server - 入口文件
 *
 * 基于 Swoole 的 TCP 文本协议服务器
 * 使用简化的依赖注入容器
 */

declare(strict_types=1);

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
ini_set('memory_limit', '1G');
error_reporting(E_ALL);
date_default_timezone_set('UTC');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

require BASE_PATH . '/vendor/autoload.php';

/**
 * 简单的依赖注入容器
 *
 * 用于将接口绑定到实现类
 */
class SimpleContainer
{
    private static array $bindings = [];

    public static function bind(string $abstract, $concrete): void
    {
        self::$bindings[$abstract] = $concrete;
    }

    public static function make(string $abstract)
    {
        if (isset(self::$bindings[$abstract])) {
            $concrete = self::$bindings[$abstract];
            if (is_callable($concrete)) {
                return $concrete();
            }
            return new $concrete();
        }
        throw new \Exception("No binding found for: {$abstract}");
    }
}

// 注册服务绑定
SimpleContainer::bind(App\Contract\CommandHandlerInterface::class, App\Command\CommandHandler::class);

// 输出启动信息
echo "Starting Text Protocol Server on 0.0.0.0:8765\n";

// 获取命令处理器
$commandHandler = SimpleContainer::make(App\Contract\CommandHandlerInterface::class);

// 创建 TCP 服务器
$tcpServer = new App\Server\TcpServer($commandHandler);

$server = new \Swoole\Server('0.0.0.0', 8765, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

$server->set([
    'worker_num' => 1,
    'daemonize' => false,
    'enable_coroutine' => true,
]);

$server->on('connect', function (\Swoole\Server $server, int $fd) use ($tcpServer) {
    $tcpServer->onConnect($server, $fd);
});

$server->on('receive', function (\Swoole\Server $server, int $fd, int $reactorId, string $data) use ($tcpServer) {
    $tcpServer->onReceive($server, $fd, $reactorId, $data);
});

$server->on('close', function (\Swoole\Server $server, int $fd) use ($tcpServer) {
    $tcpServer->onClose($server, $fd);
});

$server->start();
