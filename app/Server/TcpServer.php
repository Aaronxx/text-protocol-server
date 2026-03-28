<?php
declare(strict_types=1);

namespace App\Server;

use App\Contract\CommandHandlerInterface;

use Swoole\Server;

class TcpServer
{
    protected CommandHandlerInterface $commandHandler;

    protected array $buffers = [];

    public function __construct(CommandHandlerInterface $commandHandler)
    {
        $this->commandHandler = $commandHandler;
    }

    /**
     * @param Server $server
     * @param int $fd
     * @return void
     * 客户端连接回调
     */
    public function onConnect(Server $server, int $fd): void
    {
        // 初始化客户端缓冲区
        $this->buffers[$fd] = '';
        // 发送欢迎信息
        $server->send($fd, "Welcome to Text Protocol Server\r\n");
        // 发送可用命令列表
        $server->send($fd, "Commands: mul, incr, div, conv_tree\r\n");

    }

    /**
     * @param Server $server
     * @param int $fd
     * @param int $reactorId
     * @param string $data
     * @return void
     *
     * 接受数据回调
     */
    public function onReceive(Server $server, int $fd, int $reactorId, string $data): void
    {
        // 如果缓冲区不存在，初始化为空字符串
        if (!isset($this->buffers[$fd])) {
            $this->buffers[$fd] = '';
        }

        $this->buffers[$fd] .= $data;

        while (($pos = strpos($this->buffers[$fd], "\n")) !== false) {
            // 取出第一行
            $line = substr($this->buffers[$fd], 0, $pos);
            // 移除已处理的部分
            $this->buffers[$fd] = substr($this->buffers[$fd], $pos + 1);
            // 处理这一行的命令
            $this->processLine($server, $fd, $line);
        }
    }

    /**
     * @param Server $server
     * @param int $fd
     * @return void
     * 客户端关闭回调
     */
    public function onClose(Server $server, int $fd): void
    {
        unset($this->buffers[$fd]);
    }

    /**
     * @param string $data
     * @return string
     * 转化输入为UTF-8
     */
    protected function convertEncoding(string $data): string
    {
        if (mb_check_encoding($data, "UTF-8") && preg_match('/[^\x00-\x7F]/', $data)) {
            return $data;
        }

        $converted = @mb_convert_encoding($data, "UTF-8", "GBK");

        return ($converted !== false) ?  $converted : $data;
    }

    protected function processLine(Server $server, int $fd, string $line): void
    {
        $line = trim($line);

        if (empty($line)) {
            return;
        }

        $line = $this->convertEncoding($line);

        $parts = explode(" ", $line);
        // 提取命令名称
        $command = strtolower(array_shift($parts));

        try {
            $result = $this->commandHandler->execute($command, $parts);

            $server->send($fd, $result . "\r\n");
        } catch (\Throwable $exception) {
            $server->send($fd, "Error: " . $exception->getMessage() . "\r\n");
        }
    }
}
