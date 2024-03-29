# PHP Curl Class

This library provides an object-oriented wrapper of the PHP cURL extension.

[![Maintainability](https://api.codeclimate.com/v1/badges/6c34bb31f3eb6df36c7d/maintainability)](https://codeclimate.com/github/php-mod/curl/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/6c34bb31f3eb6df36c7d/test_coverage)](https://codeclimate.com/github/php-mod/curl/test_coverage)
[![Total Downloads](https://poser.pugx.org/curl/curl/downloads)](//packagist.org/packages/curl/curl)

If you have questions or problems with installation or usage [create an Issue](https://github.com/php-mod/curl/issues).

## it is a fork of curl/curl library

## Usage examples

```php
$curl = new Curl\Curl();
$curl->get('http://www.example.com/');
```

```php
$curl = new Curl\Curl();
$curl->get('http://www.example.com/search', array(
    'q' => 'keyword',
));
```

```php
$curl = new Curl\Curl();
$curl->post('http://www.example.com/login/', array(
    'username' => 'myusername',
    'password' => 'mypassword',
));
```

```php
$curl = new Curl\Curl();
$curl->setBasicAuthentication('username', 'password');
$curl->setUserAgent('');
$curl->setReferrer('');
$curl->setHeader('X-Requested-With', 'XMLHttpRequest');
$curl->setCookie('key', 'value');
$curl->get('http://www.example.com/');

if ($curl->error) {
    echo $curl->error_code;
}
else {
    echo $curl->response;
}

var_dump($curl->request_headers);
var_dump($curl->response_headers);
```

```php
$curl = new Curl\Curl();
$curl->setOpt(CURLOPT_RETURNTRANSFER, TRUE);
$curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
$curl->get('https://encrypted.example.com/');
```

```php
$curl = new Curl\Curl();
$curl->put('http://api.example.com/user/', array(
    'first_name' => 'Zach',
    'last_name' => 'Borboa',
));
```

```php
$curl = new Curl\Curl();
$curl->patch('http://api.example.com/profile/', array(
    'image' => '@path/to/file.jpg',
));
```

```php
$curl = new Curl\Curl();
$curl->delete('http://api.example.com/user/', array(
    'id' => '1234',
));
```

```php
$curl->close();
```

```php
// Example access to curl object.
curl_set_opt($curl->curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1');
curl_close($curl->curl);
```

```php
// Example of downloading a file or any other content
$curl = new Curl\Curl();
// open the file where the request response should be written
$file_handle = fopen($target_file, 'w+');
// pass it to the curl resource
$curl->setOpt(CURLOPT_FILE, $file_handle);
// do any type of request
$curl->get('https://github.com');
// disable writing to file
$curl->setOpt(CURLOPT_FILE, null);
// close the file for writing
fclose($file_handle);
```


## Testing

In order to test the library:

1. Create a fork
2. Clone the fork to your machine
3. Install the depencies `composer install`
4. Build and start the docker image (in `tests/server`) `docker build . -t curlserver` start `docker run -p 1234:80 curlserver`
4. Run the unit tests `./vendor/bin/phpunit tests`
