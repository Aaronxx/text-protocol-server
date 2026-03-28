<?php
declare(strict_types=1);

namespace App\Command;

use App\Contract\JsonFormatterInterface;

class DefaultJsonFormatter implements JsonFormatterInterface
{
    private const FIELDS = ['id', 'id_path', 'is_leaf', 'level', 'name', 'name_path', 'parent_id'];

    private const NL = "\r\n";

    public function format(array $data): string
    {
        if (empty($data)) {
            return '[]';
        }

        // 调用递归格式化方法
        return $this->formatArray($data, 0);
    }

    private function formatArray(array $data, int $indent): string
    {
        $space = str_repeat('  ', $indent);

        $output = '[' . self::NL;

        foreach ($data as $item) {
            $output .= $space . "  {" . self::NL;
            $output .= $this->formatFields($item, $indent);
            $output .= $space . "  }," . self::NL;
        }

        return rtrim($output,  "," . self::NL) . self::NL . $space . "]";
    }

    private function formatFields(array $item, int $indent): string
    {
        $space = str_repeat('  ', $indent);
        $output = '';

        // 判断是否有children属性
        // 如果有，最后一个字段时children
        // 如果没有，最后一个字段是parent_id
        $hasChildren = !empty($item['children']);
        $lastField = $hasChildren ? 'children' : 'parent_id';

        foreach (self::FIELDS as $field) {
            if (!isset($item[$field])) {
                continue;
            }

            $value = $item[$field];
            // 判断是否为最后一个字段，最后一个字段不加逗号
            $comma = ($field !== $lastField) ? "," : "";
            // 格式化值：字段穿加引号，数字直接输出
            $valueStr = is_string($value) ? "\"$value\"" : $value;
            // 输出属性行
            $output .= $space . "     \"$field\": $valueStr$comma" . self::NL;
        }

        // 如果有children,递归格式化children数组
        if ($hasChildren) {
            $output .= $space . "    \"children\": " . $this->formatArray($item['children'], $indent + 2);
        }

        return $output;
    }
}