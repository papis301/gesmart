<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * @param string|null $layout null = la vue produit la page HTML complète
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $content = self::capture($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::capture($layout, $data + ['content' => $content]);
    }

    /** Rend une vue et renvoie le HTML (pour les morceaux de page réutilisables). */
    public static function partial(string $view, array $data = []): string
    {
        return self::capture($view, $data);
    }

    private static function capture(string $view, array $data): string
    {
        $file = APP_DIR . '/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Vue introuvable : ' . $view);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}