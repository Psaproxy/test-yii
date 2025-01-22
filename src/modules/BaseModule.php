<?php

declare(strict_types=1);

namespace app\modules;

abstract class BaseModule extends \yii\base\Module
{
    public function init(): void
    {
        $configPath = $this->getBasePath() . '/config/module.php';
        if (file_exists($configPath)) {
            \Yii::configure($this, require $configPath);
        }

        $configPath = $this->getBasePath() . '/config/routes.php';
        if (file_exists($configPath)) {
            \Yii::$app->getUrlManager()->addRules(require $configPath);
        }

        parent::init();
    }
}
