# iqsms_json
JSON API интерфейс для сайта iqsms.ru (СМС Дисконт)

Пример использования класса

```
$gate = new iqsms_json('api_login', 'api_password');

var_dump($gate->credits()); // узнаем текущий баланс
var_dump($gate->senders()); // получаем список доступных подписей

$messages = array(
  array(
    "clientId" => "1",
    "phone"=> "71234567890",
    "text"=> "first message",
    "sender"=> "TEST" 
  ),
  array(
    "clientId" => "2",
    "phone"=> "71234567891",
    "text"=> "second message", 
    "sender"=> "TEST",
  ),
  array(
    "clientId" => "3",
    "phone"=> "71234567892",
    "text"=> "third message",
    "sender"=> "TEST",
  ),
);
var_dump($gate->send($messages, 'testQueue')); // отправляем пакет sms

$messages = array(
  array("clientId"=>"1","smscId"=>11255142),
  array("clientId"=>"2","smscId"=>11255143),
  array("clientId"=>"3","smscId"=>11255144),
);
var_dump($gate->status($messages)); // получаем статусы для пакета sms
var_dump($gate->statusQueue('testQueue', 10)); // получаем статусы из очереди 'testQueue'
```
