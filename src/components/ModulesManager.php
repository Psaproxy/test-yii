<?php

declare(strict_types=1);

namespace app\components;

use yii\base\BootstrapInterface;

class ModulesManager extends BaseComponent implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        $modules = require __DIR__ . '/../config/modules.php';
        if (!is_array($modules)) return;

        foreach ($modules as $moduleId => $moduleConfig) {
            $app->setModule($moduleId, $moduleConfig);
            $app->getModule($moduleId);
        }
    }
}
