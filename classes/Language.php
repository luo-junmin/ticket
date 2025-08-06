<?php
class Language {
    private $defaultLang = 'en';
    private $currentLang;
    private $translations = [];
    private static $instance;

    public function __construct() {
        // Check if language is set in session
        if (isset($_SESSION['lang'])) {
            $this->currentLang = $_SESSION['lang'];
        } else {
            // Default to browser language or English
            $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $this->currentLang = in_array($browserLang, ['en', 'zh']) ? $browserLang : $this->defaultLang;
            $_SESSION['lang'] = $this->currentLang;
        }

        // Load translations
        $this->loadTranslations();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    private function loadTranslations() {
//        $langFile = __DIR__ . "/../assets/lang/{$this->currentLang}.php";
        $langFile = $_SERVER['DOCUMENT_ROOT']  . "/ticket/assets/lang/{$this->currentLang}.php";

        if (file_exists($langFile)) {
            $this->translations = include $langFile;
        } else {
            // Fallback to English
            $this->translations = include $_SERVER['DOCUMENT_ROOT']  . "/ticket/assets/lang/en.php";
        }
    }

    public function setLanguage($lang) {
        if (in_array($lang, ['en', 'zh'])) {
            $this->language = $lang;
            $_SESSION['lang'] = $lang;  // 确保这里设置了session
            setcookie('language', $lang, time() + (86400 * 30), "/");

//            $_SESSION['lang'] = $lang;
//            $this->currentLang = $lang;

            $this->loadTranslations();
            return true;
        }
        return false;
    }

    public function get($key) {
        return $this->translations[$key] ?? $key;
    }

    public function getCurrentLanguage() {
        return $this->currentLang;
    }

    public function getLanguageSwitcher() {
        $currentUrl = $_SERVER['REQUEST_URI'];
        $output = '<div class="language-switcher">';
        $output .= ($this->currentLang == 'en')
            ? '<span>English</span>'
            : '<a href="' . $this->modifyUrl($currentUrl, 'en') . '">English</a>';
        $output .= ' | ';
        $output .= ($this->currentLang == 'zh')
            ? '<span>中文</span>'
            : '<a href="' . $this->modifyUrl($currentUrl, 'zh') . '">中文</a>';
        $output .= '</div>';
        return $output;
    }

    private function modifyUrl($url, $lang) {
        $parsed = parse_url($url);
        $query = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }
        $query['lang'] = $lang;
        $parsed['query'] = http_build_query($query);
        return $this->unparseUrl($parsed);
    }

    private function unparseUrl($parsed) {
        $scheme   = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host     = isset($parsed['host']) ? $parsed['host'] : '';
        $port     = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $user     = isset($parsed['user']) ? $parsed['user'] : '';
        $pass     = isset($parsed['pass']) ? ':' . $parsed['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed['path']) ? $parsed['path'] : '';
        $query    = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}