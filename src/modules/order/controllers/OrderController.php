<?php

declare(strict_types=1);

namespace app\modules\order\controllers;

use app\controllers\BaseController;
use app\modules\order\components\OrderComponent;
use app\modules\order\exceptions\ApprovedOrderAlreadyExistsException;
use yii\base\InvalidConfigException;

class OrderController extends BaseController
{
    public $enableCsrfValidation = false;

    private OrderComponent $component;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->component = \Yii::$app->getModule('order')->order;
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionAdd(): array
    {
        $args = $this->request->getBodyParams();

        $orderId = null;

        try {
            $orderId = $this->component->add(
                $args['user_id'],
                $args['amount'],
                $args['term'],
            );
        } catch (ApprovedOrderAlreadyExistsException ) {
            //
        } catch (\Throwable $exception) {
            \Yii::error($exception->getMessage());
        }

        if ($orderId) {
            $this->response->statusCode = 201;
            return [
                'result' => true,
                'id' => $orderId,
            ];
        }

        $this->response->statusCode = 400;
        return ['result' => false];

    }

    /**
     * @throws InvalidConfigException
     */
    public function actionProcesses(): array
    {
        $args = $this->request->getQueryParams();

        try {
            $delay = (int)($args['delay'] ?? 0);
            $delay = max(0, $delay);

            $limit = (int)($args['limit'] ?? 100);

            if ($delay) sleep($delay);

            $this->component->processAll($limit);

        } catch (\Throwable $exception) {
            \Yii::error($exception->getMessage());
            $this->response->statusCode = 500;
            return ['result' => false];
        }

        return ['result' => true];
    }
}
