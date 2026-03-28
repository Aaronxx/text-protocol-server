<?php
declare(strict_types=1);

namespace App\Contract;

interface JsonFormatterInterface
{
    /**
     * @param array $data
     * @return string
     * 格式化数据位JSON格式
     */
    public function format(array $data): string;
}