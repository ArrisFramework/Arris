# Init 

```
AppLogger::init('<application id>');
```

# Add Scope

```php
AppLogger::addScope(scope_name, options);
```

where options is array of options. Each option are:
```php
[
    0   =>  log file
    1   =>  logging level (Logger::DEBUG, Logger::INFO, etc (see Monolog)
    2   =>  bubbling flag (default false)
]
```
  
Example: 
```
AppLogger::addScope('mysql', [
        [ '__mysql.debug-100.log', Logger::DEBUG],
        [ '__mysql.notice-250.log', Logger::NOTICE],
        [ '__mysql.warning-300.log', Logger::WARNING],
        [ '__mysql.error-400.log', Logger::ERROR],
    ]);
```

# Usage

```php
AppLogger::scope('mysql')->warn("mysql::Warning ");

AppLogger::scope('mysql')->error('mysql::Error', ['foobar']);

AppLogger::scope('mysql')->notice('mysql::Notice', ['x', 'y']);

AppLogger::scope('mysql')->debug("mysql::Debug", [ ['x'], ['y']]);

AppLogger::scope('usage')->debug('Usage', [0, 1, 2]);
```

# todo

- lazy initialization with AppLogger::addScope()
- standalone class