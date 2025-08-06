<?php
class Language {
    private static $instance = null;
    private $language = 'en';
    private $translations = [];

    private function __construct() {
        $this->detectLanguage();
        $this->loadTranslations();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function detectLanguage() {
        if (isset($_SESSION['language'])) {
            $this->language = $_SESSION['language'];
        } elseif (isset($_COOKIE['language'])) {
            $this->language = $_COOKIE['language'];
        } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $this->language = in_array($lang, ['en', 'zh']) ? $lang : 'en';
        }
    }

    private function loadTranslations() {
        $langFile = __DIR__ . "/../assets/lang/{$this->language}.php";
        if (file_exists($langFile)) {
            $this->translations = include $langFile;
        } else {
            $this->translations = include __DIR__ . "/../assets/lang/en.php";
        }
    }

    public function setLanguage($lang) {
        if (in_array($lang, ['en', 'zh'])) {
            $this->language = $lang;
            $_SESSION['language'] = $lang;
            setcookie('language', $lang, time() + (86400 * 30), "/");
            $this->loadTranslations();
        }
    }

    public function get($key, $default = '') {
        return $this->translations[$key] ?? $default;
    }

    public function getCurrentLanguage() {
        return $this->language;
    }
}