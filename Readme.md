# Установка, настройка и запуск

Изменить входящие порты в `/compose.override.yml`, те, что до двоеточия.

Добавить в `/etc/hosts` строку `127.0.0.1 test-yii.local`

Выполнить из корневой директории приложения:
```
make build && make install && make make caddy-ssl-ca-install-browsers && make restart
```

# Подключение к БД

- DSN: `jdbc:postgresql://localhost:ПОРТ-ИЗ-compose.override.yml/test`
- Логин: `test`
- Пароль: `test`

# Использование

### Добавление заявок
Выполнить запрос `/http/order.http`

### Запуск обработки заявок
Выполнить в браузере `https://test-yii2.local:446/processor?delay=20&limit=1`

# Реализация

```
/src/modules/order/controllers/OrderController.php
/src/modules/order/components/OrderComponent.php
```
