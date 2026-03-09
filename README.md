## SPFW – Simple PHP Framework

SPFW – это **простой и мощный PHP‑фреймворк** для быстрого создания веб‑приложений. Он предоставляет базовую инфраструктуру (роутинг, контроллеры, представления, работу с БД и сессиями), сохраняя при этом лаконичность и понятную структуру проекта.

### Возможности

- **Маршрутизация**: гибкая система маршрутов через `config/routes.php`.
- **MVC‑структура**: разделение на контроллеры (`app/Controllers`), модели (`app/Models`), представления (`app/Views`).
- **Шаблоны**: поддержка шаблонов с использование PHP или шаблонизатора Twig.
- **Работа с БД**: класс `Database` на базе `ext-pdo`.
- **Сессии и кэш**: удобные фасады `session()`, `cache()`.
- **Валидация форм и helper‑ы**: функции `old()`, `get_errors()` и др.
- **Загрузка файлов**: helper `upload_file()`.
- **Отправка почты**: интеграция с `PHPmailer` через helper `send_mail()`.
- **CSRF‑защита**: helper‑ы `get_csrf_token()` и `get_csrf_meta()`.

### Требования

- **PHP**: 8.1+ (рекомендуется)
- **Composer**
- Расширение **PDO** для работы с базой данных

### Установка

1. **Клонируйте или скопируйте проект** в директорию веб‑сервера, например:

```bash
git clone <your-repo-url> spfw
cd spfw
```

2. **Установите зависимости Composer** (если ещё не установлены `vendor`‑пакеты):

```bash
composer install
```

3. **Настройте веб‑сервер** так, чтобы корнем сайта была папка `public`:

- **Nginx** – укажите `root /path/to/spfw/public;`
- **Apache** – настройте `DocumentRoot` на `public` и включите `mod_rewrite`

4. **Настройте конфигурацию**:

- Отредактируйте файлы в папке `config` (подключение к БД, email‑параметры, базовый URL и т.п.).

### Быстрый старт

- Главная страница обрабатывается контроллером `HomeController`:
  - Контроллер: `app/Controllers/HomeController.php`
  - Шаблон: `app/Views/home/index.php`
- Для рендера представлений используется helper `view()` из `helpers/helpers.php`.

Создание нового контроллера (пример):

```php
namespace App\Controllers;

class BlogController extends BaseController
{
    public function index()
    {
        return view('blog/index', [
            'title' => 'Блог',
        ]);
    }
}
```

И соответствующий маршрут в `config/routes.php` (пример):

```php
$router->get('/blog', [\App\Controllers\BlogController::class, 'index']);
```

### Полезные helper‑ы

- **`view($view, $data = [], $layout = '', $twig_template_enable = false)`** – рендер представления.
- **`request()` / `response()`** – доступ к текущему запросу и ответу.
- **`session()`** – работа с сессией (flash‑сообщения, хранение данных пользователя и т.п.).
- **`db()`** – доступ к экземпляру `Database`.
- **`abort($message, $code = 404)`** – выброс страницы ошибки.
- **`old()`, `get_errors()`, `get_validation_class()`** – работа с формами и валидацией.
- **`upload_file()`** – загрузка файлов с автоматическим созданием папок по дате.
- **`send_mail()`** – отправка писем через PHPMailer.

### Запуск в режиме разработки (встроенный сервер PHP)

Можно использовать встроенный сервер PHP, указав корень `public`:

```bash
php -S localhost:8000 -t public
```

После этого откройте в браузере `http://localhost:8000`.

### Структура проекта (упрощённо)

- `public/` – публичный корень, точка входа `index.php`, статические файлы.
- `app/Controllers/` – контроллеры приложения.
- `app/Models/` – модели приложения.
- `app/Views/` – представления и layout‑ы.
- `core/` – ядро фреймворка (`Application`, `Router`, `Request`, `Response`, `Database`, `Session`, `View` и др.).
- `config/` – конфигурация приложения (`routes.php`, `init.php` и т.п.).
- `helpers/` – глобальные helper‑функции.
- `vendor/` – зависимости Composer.

### Лицензия

Проект распространяется под лицензией, указанной в файле `LICENSE`.

