<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;

return [
    'app' => [
        'name' => 'text-protocol-server',
        'debug' => true,
        'timezone' => 'UTC',
    ],
    'server' => [
        'mode' => SWOOLE_BASE,
        'settings' => [],
    ],
    'dependencies' => [],
];
