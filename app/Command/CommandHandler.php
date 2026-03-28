<?php
declare(strict_types=1);

namespace App\Command;

use App\Contract\CommandHandlerInterface;

class CommandHandler implements CommandHandlerInterface
{
    public function execute(string $command, array $params): string
    {
        return match ($command) {
            'mul' => $this->handleMul($params),
            'incr' => $this->handleIncr($params),
            'div' => $this->handleDiv($params),
            'conv_tree' => $this->handleConvTree($params)
        };
    }

    /**
     * @param array $params
     * @return string
     * 处理乘法运输
     */
    protected function handleMul(array $params): string
    {
        //参数数量验证：必须两个参数
        if (count($params) !== 2) {
            return "Usage: mul <x> <y>";
        }

        $x = (float) $params[0];
        $y = (float) $params[1];

        $result = $x * $y;

        return "Result: " . number_format($result, 2, '.', '');

    }

    /**
     * @param array $params
     * @return string
     * 处理自增运算
     */
    protected function handleIncr(array $params): string
    {
        if (count($params) !== 1) {
            return "Usage: incr <x>";
        }

        $x = (int) $params[0];

        return "Result: " . ($x + 1);
    }

    /**
     * @param array $params
     * @return string
     * 处理除法运算
     */
    protected function handleDiv(array $params): string
    {
        if (count($params) !== 2) {
            return "Usage: div <x> <y>";
        }

        $x = (float) $params[0];
        $y = (float) $params[1];

        //除数不能为零
        if ($y === 0.0) {
            return "Error: Division by zero";
        }

        $result = $x / $y;

        //格式化结果，保留两位小数
        return "Result: " . number_format($result, 2, '.', '');
    }


    protected function handleConvTree(array $params): string
    {
        $convTree = new ConvTreeCommand();
        return $convTree->execute($params);
    }
}