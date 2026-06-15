### 📋 Итоговая таблица миграции

| Было | Стало | Класс |
|------|-------|-------|
| `getJSONPayload()` | `Http::jsonPayload()` | `Http.php` |
| `_env('KEY', null, 'bool')` | `Env::get('KEY', null, 'bool')` | `Env.php` |
| `checkAllowedValue($v, $arr)` | `Arrays::allowed($v, $arr)` | `Arrays.php` |
| `setOptionEnv($opts, 'k', 'ENV')` | `Env::option($opts, 'k', 'ENV')` | `Env.php` |
| `setOption($opts, 'k', 'def')` | `Arrays::get($opts, 'k', 'def')` | `Arrays.php` |

Все методы — `public static`, все параметры типизированы, все оптимизации безопасны и обратно совместимы по поведению (кроме `bool`-парсинга, который стал **корректным**).