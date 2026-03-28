<?php
declare(strict_types=1);

namespace App\Command;

use App\Contract\JsonFormatterInterface;

class ConvTreeCommand
{
     private JsonFormatterInterface $jsonFormatter;

     public function __construct(?JsonFormatterInterface $jsonFormatter = null)
     {
         $this->jsonFormatter = $jsonFormatter ?? new DefaultJsonFormatter();
     }

     public function execute(array $params): string
     {
         //设置内部字符为UTF-8
         mb_internal_encoding("UTF-8");

         $data = $this->parseInput($params);

         if ($data === null) {
             return "Error: Invalid JSON - " . json_last_error_msg();
         }

         $tree = $this->buildTreeRecursive($data);

         return $this->jsonFormatter->format($tree);
     }

     public function buildTreeRecursive(array $data): array
     {
         $treeMap = [];

         foreach ($data as $item) {
             if (!isset($item["namePath"])) {
                 continue;
             }

             //按逗号分割namePath
             $parts = array_map('trim', explode(',', $item["namePath"]));
             //namePath 必须至少包含三级
             if (count($parts) < 3) {
                 continue;
             }

             [$level1Name, $level2Name, $level3Name] = $parts;

             // 初始化数组结构
             if (!isset($treeMap[$level1Name])) {
                 $treeMap[$level1Name] = [];
             }

             if (!isset($treeMap[$level1Name][$level2Name])) {
                 $treeMap[$level1Name][$level2Name] = [];
             }

             $treeMap[$level1Name][$level2Name][] = [
                 'name' => $level3Name,
                 'id' => $item["id"] ?? null,
                 'namePath' => $item["namePath"],
             ];
         }

         // 第二遍： 构建树节点
         $result = [];
         // 遍历一级分类
         foreach ($treeMap as $level1Name => $level2Nodes) {
             // 创建一级节点
             $level1Node = new TreeNode($level1Name, 1, 0, null, $level1Name);

             // 遍历二级分类
             foreach ($level2Nodes as $level2Name => $level3Nodes) {
                 // 创建二级节点
                 $level2Node = new TreeNode(
                     $level2Name,
                     2,
                     $level1Node->id,
                     null,
                     $level1Name . ',' . $level2Name
                 );
                 //遍历三级分类
                 foreach ($level3Nodes as $level3Data) {
                     $level3Node = new TreeNode(
                       $level3Data['name'],
                       3,
                       $level2Node->id,
                       $level3Data['id'],
                       $level3Data['namePath']
                     );

                     $level2Node->addChild($level3Node);
                 }

                 $level1Node->addChild($level2Node);
             }

             $result[] = $level1Node->toArray();
         }

         return $result;
     }

     //自动处理编码转化和UTF-8校验
     public function parseInput(array $params): array
     {
         //如果没有输入参数，返回空数组
         if (empty($params)) {
             return [];
         }

         $jsonString = implode(',', $params);

         $jsonString = $this->convertEncoding($jsonString);

         $data = json_decode($jsonString, true);

         //检查json解析错误
         if (json_last_error() !== JSON_ERROR_NONE) {
             return [];
         }

         return $this->ensureUtf8($data);
     }

    /**
     * @param array $data
     * @return array
     * 递归确保所有字符串都熟UTF-8变啊
     */
     private function ensureUtf8(array $data): array
     {
         // 遍历数组的每个元素
         foreach ($data as $key => $value) {
             // 如果是数组，递归处理
             if (is_array($value)) {
                 $data[$key] = $this->ensureUtf8($value);
             } elseif (is_string($value)) {
                 if (mb_check_encoding($value, "UTF-8")) {
                     continue;
                 }

                 //尝试多种编码转化
                 foreach (['GBK', 'GB2312', 'GB18030', 'Windows-1252', 'ISO-8859-1'] as $encoding) {
                    $converted = mb_convert_encoding($value, "UTF-8", $encoding);
                    if ($converted !== false && mb_check_encoding($converted, "UTF-8")) {
                        $data[$key] = $converted;
                        break;
                    }
                 }

                 if (!mb_check_encoding($data[$key] ?? $value, "UTF-8")) {
                     $converted = @iconv('GBK', 'UTF-8//IGONRE', $value);
                     if ($converted !== false && strlen($converted) > 0) {
                         $data[$key] = $converted;
                     }
                 }
             }
         }

         return $data;
     }

    /**
     * @param string $data
     * @return string
     * 转化字符串编码
     */
     private function convertEncoding(string $data): string
     {
         // 如果是UTF-8且能解析为Json, 直接返回
        if (mb_check_encoding($data, "UTF-8") && json_decode($data) !== null) {
            return $data;
        }
        // 尝试自动检测编码
        $detected = mb_detect_encoding($data, ["UTF-8", "GBK", "GB2312", "GB18030", "ASCII"], true);

        // 如果检测到非UTF-8编码，尝试转化
        if ($detected && $detected != 'UTF-8') {
            $converted = mb_convert_encoding($data, "UTF-8", $detected);
            if ($converted !== false) {
                return $converted;
            }
        }

        // 最后尝试使用 iconv
        $converted = @iconv('GBK', "UTF-8//IGNORE", $data);
        if ($converted !== false && $converted !== $data) {
            return $converted;
        }

        // 如果都失败，返回原始数据
        return $data;
     }


}