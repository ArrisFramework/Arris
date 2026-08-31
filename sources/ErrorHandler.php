<?php

declare(strict_types=1);

namespace Arris;

class ErrorHandler
{
    /**
     * Режим отладки. Если true — в вебе выводится полный HTML-дамп,
     * если false — безопасная заглушка (см. renderSafe()).
     */
    private bool $debug = false;

    /**
     * Заголовок заглушки для безопасного режима.
     */
    private string $safeHeading = 'Что-то пошло не так';

    /**
     * Колбэк кастомизации безопасной заглушки.
     * Сигнатура: function (array $event): array — возвращает переопределения 'heading'/'text'.
     */
    private ?\Closure $safeTextCallback = null;

    /**
     * Выводить ли исходник вокруг строки исключения.
     */
    private bool $snippets = true;

    /**
     * Сколько строк вокруг точки броска показывать в сниппете.
     */
    private int $snippetRadius = 4;

    /**
     * Максимальная длина аргумента в трейсе (символов).
     */
    private int $maxArgChars = 2000;

    /**
     * Устанавливает режим отладки.
     */
    public function setDebug(bool $debug = true): static
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Текущий режим отладки.
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Регистрирует обработчики исключений и ошибок.
     */
    public function register(): void
    {
        set_exception_handler([$this, 'handle']);
        set_error_handler(function (int $severity, string $message, string $file, int $line): never {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    /**
     * Колбэк кастомизации безопасной заглушки (для прода).
     *
     * Возвращает массив переопределений (полей можно задать частично):
     *   function (array $event): array {
     *       $event['heading'] = '...';
     *       $event['text']    = '...';
     *       return $event;
     *   }
     */
    public function safeText(callable $callback): static
    {
        $this->safeTextCallback = $callback;
        return $this;
    }

    /**
     * Единая точка вызова обработчиков.
     */
    public function handle(\Throwable $e): void
    {
        // Логируем всегда, даже без логгера
        error_log(sprintf(
            "[FATAL] %s: %s in %s:%d",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));

        $isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
        if ($isCli) {
            fwrite(STDERR, $this->toText($e) . PHP_EOL);
            exit($this->exitCode($e));
        }

        // Веб: чистим буфер и отдаём HTML
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        echo $this->debug ? $this->dump($e) : $this->renderSafe($e);

        exit(1);
    }

    /**
     * Безопасная заглушка для прода: показываем last-исключение,
     * но без стека/путей. Заголовок и текст переопределяются колбэком safeText().
     */
    public function renderSafe(\Throwable $e): string
    {
        $event = [
            'heading' => $this->safeHeading,
            'text'    => $e->getMessage(),
        ];

        if ($this->safeTextCallback !== null) {
            $result = ($this->safeTextCallback)($event);
            if (is_array($result) && $result !== []) {
                $event = array_merge($event, $result);
            }
        }

        $heading = $this->esc((string)($event['heading'] ?? $this->safeHeading));
        $text    = $this->esc((string)($event['text'] ?? $e->getMessage()));

        return '<h1>' . $heading . '</h1><p>' . $text . '</p>';
    }

    /**
     * Плоский дамп для CLI/логов.
     */
    public function toText(\Throwable $e): string
    {
        return sprintf(
            '[FATAL] %s: %s at %s:%d',
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ) . PHP_EOL . $e->getTraceAsString();
    }

    /**
     * Exit-код для CLI: severity для ErrorException (в error-handler код=0, но severity важен),
     * иначе код исключения; ненулевой код гарантирован (иначе мониторинг не увидит ошибку).
     */
    private function exitCode(\Throwable $e): int
    {
        $code = $e instanceof \ErrorException ? $e->getSeverity() : $e->getCode();
        $code = (int)$code;
        return $code !== 0 ? $code : 1;
    }

    /**
     * Полный HTML-дамп: класс, message, цепочка Previous, стек вызовов
     * со сниппетами исходника и аргументами.
     */
    public function dump(\Throwable $e): string
    {
        $frames = $e->getTrace();
        if ($frames === []) {
            $frames = [[
                'file'     => $e->getFile(),
                'line'     => $e->getLine(),
                'function' => 'thrown',
                'class'    => $e::class,
                'args'     => [],
            ]];
        }

        $html  = "<!DOCTYPE html>\n";
        $html .= '<html lang="ru">' . "\n";
        $html .= "<head>\n";
        $html .= "<meta charset=\"utf-8\">\n";
        $html .= '<title>' . $this->esc($e::class . ': ' . $e->getMessage()) . "</title>\n";
        $html .= '<style>' . $this->css() . "</style>\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '<header class="catcher-head">' . "\n";
        $html .= '<div class="class">' . $this->esc($e::class) . "</div>\n";
        $html .= '<div class="message">' . $this->esc($e->getMessage()) . "</div>\n";
        $html .= '<dl class="meta">' . "\n";
        $html .= '<dt>File</dt><dd>' . $this->esc($e->getFile()) . '<b>:' . (int)$e->getLine() . "</b></dd>\n";
        $html .= '<dt>Code</dt><dd>' . (int)$e->getCode() . "</dd>\n";
        $html .= '<dt>Time</dt><dd>' . date('c') . "</dd>\n";
        $html .= '<dt>Memory</dt><dd>' . round(memory_get_peak_usage(true) / 1048576, 2) . " MiB</dd>\n";
        $html .= "</dl>\n</header>\n";

        $chain = [];
        $prev = $e->getPrevious();
        while ($prev !== null) {
            $chain[] = $prev;
            $prev = $prev->getPrevious();
        }
        foreach ($chain as $n => $prev) {
            $html .= '<section class="previous">' . "\n";
            $html .= '<h2>Previous #' . ($n + 1) . ' <span class="class">' . $this->esc($prev::class) . "</span></h2>\n";
            $html .= '<div class="message">' . $this->esc($prev->getMessage()) . "</div>\n";
            $html .= '<div class="meta">' . $this->esc($prev->getFile() . ':' . $prev->getLine()) . "</div>\n";
            $html .= "</section>\n";
        }

        $html .= '<section class="frames"><h2>Call Stack</h2>' . "\n<ol>\n";
        foreach ($frames as $i => $frame) {
            $html .= $this->frameHtml($i, $frame) . "\n";
        }
        $html .= "</ol>\n</section>\n";

        $html .= "</body>\n</html>\n";
        return $html;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function css(): string
    {
        return <<<CSS
body{background:#11151c;color:#e6edf3;font:14px/1.55 Menlo,Consolas,"DejaVu Sans Mono",monospace;margin:0;padding:28px}
h2{color:#79c0ff;font-size:16px;margin:22px 0 8px}
.catcher-head .class{font-size:19px;color:#ff7b72;font-weight:700}
.catcher-head .message{font-size:15px;margin:6px 0 12px}
dl.meta{display:grid;grid-template-columns:64px 1fr;gap:2px 12px;margin:0;color:#8b949e}
dl.meta dd{margin:0;color:#c9d1d9}.meta b{color:#ffa657}
.previous{margin-top:16px;border-left:3px solid #30363d;padding-left:14px}
.previous .class{font-size:13px;color:#d29922}
.frames ol{padding-left:0;list-style:none;margin:0}
.frame{margin:10px 0 18px;border:1px solid #30363d;border-radius:8px;overflow:hidden}
.frame-head{background:#161b22;padding:6px 10px;color:#8b949e;word-break:break-all}
.frame-head b{color:#ffa657}
.snippet{background:#0d1117;padding:8px 10px;overflow-x:auto}
.snippet pre{margin:0;color:#c9d1d9}
.snippet span{display:block;white-space:pre}
.snippet i{color:#484f58;font-style:normal;user-select:none}
.snippet span.hit{background:#3d2f00;color:#ffa657}
CSS;
    }

    private function frameHtml(int $i, array $frame): string
    {
        $file = $frame['file'] ?? '';
        $line = $frame['line'] ?? 0;

        $call = '';
        if (isset($frame['class'])) {
            $call .= $this->esc((string)$frame['class']) . $this->esc((string)($frame['type'] ?? '->'));
        }
        if (isset($frame['function'])) {
            $call .= $this->esc((string)$frame['function']) . '(';
            if (!empty($frame['args'])) {
                $parts = [];
                foreach ($frame['args'] as $arg) {
                    $parts[] = $this->argValue($arg);
                }
                $call .= implode(', ', $parts);
            }
            $call .= ')';
        }

        $head = '#' . $i . ' ' . $this->esc($file !== '' ? $file : '[internal]');
        if ($line) {
            $head .= '<b>:' . (int)$line . '</b>';
        }
        if ($call !== '') {
            $head .= ' &mdash; ' . $call;
        }

        $html  = '<li class="frame">' . "\n";
        $html .= '<div class="frame-head">' . $head . "</div>\n";
        if ($this->snippets && $file !== '' && $line > 0 && is_readable($file)) {
            $html .= $this->snippetHtml($file, $line);
        }
        $html .= "</li>";
        return $html;
    }

    private function snippetHtml(string $file, int $line): string
    {
        $content = @file($file);
        if ($content === false) {
            return '';
        }

        $total = count($content);
        $r = $this->snippetRadius;
        $from = max(1, $line - $r);
        $to = min($total, $line + $r);

        $out = '<div class="snippet"><pre>';
        for ($n = $from; $n <= $to; $n++) {
            $text = rtrim((string)($content[$n - 1] ?? ''), "\r\n");
            $text = $this->cut($text, 200);
            $cls = $n === $line ? ' class="hit"' : '';
            $out .= '<span' . $cls . '><i>' . str_pad((string)$n, 4, ' ', STR_PAD_LEFT) . "</i> " . $this->esc($text) . "</span>\n";
        }
        $out .= "</pre></div>\n";
        return $out;
    }

    private function cut(string $value, int $max): string
    {
        if ($max <= 0 || strlen($value) <= $max) {
            return $value;
        }
        // режем по границе UTF-8, чтобы не выводить битый символ
        $cut = substr($value, 0, $max);
        while ($cut !== '' && $cut !== false && !preg_match('//u', $cut)) {
            $cut = substr($cut, 0, -1);
        }
        return ($cut !== false ? $cut : '') . '...';
    }

    private function argValue(mixed $v): string
    {
        $value = match (true) {
            is_string($v)  => '"' . addcslashes($v, "\"\n\r\t") . '"',
            is_int($v), is_float($v), is_bool($v), is_null($v) => var_export($v, true),
            is_array($v)   => 'array(' . count($v) . ')',
            $v instanceof \Closure => 'Closure',
            is_object($v)  => $v::class,
            is_resource($v) => 'resource(' . get_resource_type($v) . ')',
            default        => get_debug_type($v),
        };
        return $this->esc($this->cut($value, $this->maxArgChars));
    }
}
