<?php
declare(strict_types=1);

namespace App\Contract;

interface CommandHandlerInterface
{
    /**
     * @param string $command
     * @param array $params
     * @return string
     * 根据命令名称和参数执行相应的业务逻辑
     */
    public function execute(string $command, array $params): string;
}