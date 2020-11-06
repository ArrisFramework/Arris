
В классе DB реализованы методы

- query
- fetchAll
- fetchRow
- fetchColumn

(ну, как в Steamboat\PDOWrapper)

Все они используют "текущий" PDO-коннекшен.

Текущий коннекшен определяется либо вызовом:

- DB::setCurrentConnection(default NULL)
- либо если существует единственный init() у синглтона

+ не забудем про "простая обёртка над PDO"
 