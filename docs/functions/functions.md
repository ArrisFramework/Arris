Все функции объявлены в неймспейсе `Arris` и могут использоваться так:

```
use function Arris\http_redirect as foobar;

...
foobar()
```

... или через неймспейс:

```
Arris\http_redirect(...);
```
 

# Общего назначения 

## setOption
`function setOption(array $options, $key, $env_key = null, $default_value = '');`

## checkAllowedValue
`function checkAllowedValue( $value, $allowed_values_array , $invalid_value = NULL );`

## http_redirect
`function http_redirect($uri, $replace_prev_headers = false, $code = 302, $scheme = '');`

## GUID
`function GUID();` 


# Multibyte string

## mb_trim_text
`function mb_trim_text($input, $length, $ellipses = true, $strip_html = true, $ellipses_text = '...'):string;`

## mb_str_replace
`function mb_str_replace($search, $replace, $subject, &$count = 0):string;`

# Array helpers

## array_map_to_integer
`function array_map_to_integer(array $input): array;`

## array_fill_like_list
`function array_fill_like_list(array &$target_array, array $indexes, array $source_array, $default_value = NULL);` 

## array_search_callback
`function array_search_callback(array $a, callable $callback);`

## array_sort_in_given_order
`function array_sort_in_given_order(array $array, array $order, $sort_key):array;`


# Language 

## pluralForm
`function pluralForm($number, $forms, string $glue = '|'):string;`

