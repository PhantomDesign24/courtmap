<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $name, array $vars = [], ?string $layout = 'app'): void
    {
        $content = self::renderRaw($name, $vars);
        if ($layout) {
            $vars['content'] = $content;
            echo self::renderRaw('layouts/' . $layout, $vars);
        } else {
            echo $content;
        }
    }

    public static function renderRaw(string $__name, array $__vars = []): string
    {
        $__path = __DIR__ . '/../Views/' . $__name . '.php';
        if (!is_file($__path)) {
            throw new \RuntimeException("View not found: $__name");
        }
        // 뷰에 사용자 변수 주입. 파라미터는 __ prefix 라 뷰 키와 충돌 없음.
        extract($__vars, EXTR_SKIP);
        ob_start();
        require $__path;
        return (string) ob_get_clean();
    }

    public static function e(?string $s): string
    {
        return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
