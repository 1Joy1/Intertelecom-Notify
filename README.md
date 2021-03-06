# Intertelecom Notify
##

Небольшой php скрипт парсящий страничку личного кабинета абонента Интертелеком (Украина), для поиска оставшихся пакетных минут на мобильные, и в случае их уменьшения ниже порогового значени 15 минут, шлет сообщение на email.

Скрипт может мониторить нескольких абонентов, если основной абонент является их куратором и они, как и основной абонент добавленны в таблицу базы данных.

##### Требования к установке.

  - Apach
  - MySQL
  - PHP

### Установка

Для установки, разместите папку InterTelecom на сервере, переименуйте в папке ***config*** файлы ***config\_\*.json.example в-> config\_\*.json*** и отредактируйте их содержимое согласно вашим данным.

- _**config\_auth.json**_
 ```
"log_pass": "phone=xxxxxxxxxx&pass=xxxxxx&ref_link=&js=1"
```
   где    `phone` ваш логин в личный кабинет, `pass` ваш пароль.

- _**config\_email.json**_
```
"mail_to": "your@email.com"
"mail_header_from": "InterTelecom@your.domain.com"
```
`"your@email.com"`- email куда слать оповещения,
`"InterTelecom@your.domain.com"` - обратный адрес отправителя.  _При этом `your.domain.com` реальный домен вашего хостинга._

- _**config\_db.json**_
```
"user_db": "user_db",
"pasword_db": "pasword_db",
"host_db": "localhost",
"name_db": "name_db"
```
Настройки подключения к вашей базе данных MySQL.

##### Далее
Создайте в базе данных на сервере таблицу Intertelecom_abonent_repository, структура таблицы находится в папке mysql, и заполните её данными.
 **number** - номер телефона абонента в произвольной форме.
 **param** - строка вида `phone=ххххххххх&cp=1`, где `phone` ваш логин.

Настройте **cron**(планировщик задач) на файл **it.php** для запуска его по расписанию, перенаправив стандартный вывод в null  (/.../.../it.php > /dev/null)

P.S. Если возникнет потребность увеличить пороговое значение после которого начинают слаться письма, это можно сделать в классе IntertelecomNotify в константе LEFT_SEC = 900.
