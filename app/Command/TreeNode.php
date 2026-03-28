<?php
declare(strict_types=1);

namespace App\Command;

class TreeNode
{
    // 随机ID字符集
    private const CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public string $id;

    public string $idPath;

    public int $isLeaf;

    public string $name;

    public int $level;

    public string $namePath;

    public int|string $parentId;

    public array $children = [];

    public function __construct(
        string $name,
        int $level,
        int|string $parentId = 0,
        int|string|null $originalId = null,
        string $namePath = null)
    {
        $this->name = $name;
        $this->level = $level;
        $this->parentId = $parentId;

        $this->isLeaf = $level === 3 ? 1 : 2;

        $this->id = $originalId !== null ? (string)$originalId : $this->generateRandomId(10);
        // 构建id_path
        // 根节点：.id,
        // 子节点：,parentId,id,
        $this->idPath = ($parentId === 0 || $parentId === '0')
            ? ',' . $this->id . ','
            : ',' .  $parentId . ',' . $this->id . ',';

        $this->namePath = $namePath ?? $name;
    }

    /**
     * @param int $length
     * @return string
     * @throws \Random\RandomException
     * 生成随机 ID
     */
    private function generateRandomId(int $length): string
    {
        $result = '';
        $charsLength = strlen(self::CHARS);

        for ($i = 0; $i < $length; $i++) {
            $result .= self::CHARS[random_int(0, $charsLength - 1)];
        }

        return $result;
    }

    /**
     * @param TreeNode $node
     * @return void
     * 添加子节点
     */
    public function addChild(TreeNode $node): void
    {
        $this->children[] = $node;
    }

    /**
     * @return array
     * 转化为数组
     */
    public function toArray(): array
    {
        $result = [
            'id' => $this->id,
            'id_path' => $this->idPath,
            'is_leaf' => $this->isLeaf,
            'level' => $this->level,
            'name' => $this->name,
            'name_path' => $this->namePath,
            'parent_id' => $this->parentId,
        ];

        if (!empty($this->children)) {
            $result['children'] = array_map(fn(TreeNode $node) => $node->toArray(), $this->children);
        }

        return $result;
    }
}