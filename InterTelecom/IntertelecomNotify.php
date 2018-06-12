<?php
/**
 * Class IntertelecomNotify | IntertelecomNotify.php
 */

/**
 */
require_once("HttpRequest.php");
require_once("EmailSender.php");
require_once("AbonentRepository.php");
require_once("ConfigLoader.php");
require_once("AbonentRepositorySourceMySql.php");

/**
 * Получает данные из личного кабинета пользователя ищет сколько осталось пакетных минут на мобильные,
 * при понижение порогового значения, указанного в константе LEFT_SEC, отсылает сообщение на email.
 *
 * @author Marshak Igor aka !Joy!
 * @package Intertelecom
 * @version v.1.1 (25.09.17)
 * @copyright Copyright (c) 2017 Marshak Igor aka !Joy!
 *
 */
class IntertelecomNotify
{

    const URL_LOGIN = "https://assa.intertelecom.ua/ru/login";
    const URL_STATISTIC = "https://assa.intertelecom.ua/ru/statistic/";
    const URL_LOGOUT ="https://assa.intertelecom.ua/ru?logout";

    const CONFIG_FILE_PATH = __DIR__."/config/config_auth.json";

    const LEFT_SEC = 900;  // 900 секунд = 15 минут

    const REGEXP_BAL_BASE = "/\<td\>Украина\+Моб\.Украина\<\/td\>.*?\<td.*?\>(.*?) по.*?\<\/td\>/s";
    const REGEXP_BAL_100 = "/\<td\>Украина \(моб\.\) \[100 мин\]\<\/td\>.*?\<td.*?\>(.*?) по.*?\<\/td\>/s";
    const REGEXP_BAL_200 = "/\<td\>Украина \(моб\.\) \[200 мин\]\<\/td\>.*?\<td.*?\>(.*?) по.*?\<\/td\>/s";
    const REGEXP_BONUS_FIN = "/\<td\>Наилучшее общение\<\/td\>.*?\<td.*?\>(.*?) (\(.*?\)).*?\<\/td\>/s";
    const REGEXP_SALDO_FIN = "/\<td\>Сальдо\<\/td\>.*?\<td.*?\>(.*?)\<\/td\>/s";
    const REGEXP_ERROR = "/\<p class=\"(error)\"\>(.*?)\<\/p\>/";



    /**
     * Объект реализующий отправку сообщений
     *
     * @var EmailSender
     */
    protected $email_sender;

    /**
    * Объект реализующий подключение и выполняющий запросы к базе данных
    *
    * @var AbonentRepositorySourceMySql | AbonentRepository
    */
    protected $abonent_repo;

    /**
     * Объект HTTP request, совершает http запросы post и get.
     *
     * @var HttpRequest
     */
    protected $request;


    /**
     * Конструктор. Устанавливает свойства объекта
     *
     * @return void
     */
    public function __construct() {

        $this->email_sender = new EmailSender();

        $this->abonent_repo = new AbonentRepository(new AbonentRepositorySourceMySql);

        $this->request = new HttpRequest();

        $this->init();
    }



    /**
     * Получает конфигурационные данные.
     * Вызывает методы валидации конфига, метод аутентификации на сайте,
     * Получает список абонентов и для каждого запускает получение текущего
     * баланса и его оброботкую
     *
     * @return void
     */
    protected function init() {

        $config_loader = new ConfigLoader(static::CONFIG_FILE_PATH);

        $config = $config_loader->getConfig();

        $this->validateConfig($config);

        $this->auth($config->log_pass);

        $abonents = $this->abonent_repo->getAllAbonents();

        foreach ($abonents as $abonent) {

            $this->getAndProcessData($abonent);
        }

        $this->logout();
    }



    /**
     * Проверка на валидность конфигурационных данных для аутентификации.
     *
     * @param StdClass $config
     * @throws IntertelecomNotify_Exception
     * @return void
     */
    protected function validateConfig($config) {

        if (!is_object($config)) {

            throw new IntertelecomNotify_Exception('Ошибка: $config должен быть объектом');
        }

        if (!isset($config->log_pass)) {

            throw new IntertelecomNotify_Exception('Ошибка. В объекте $config должен быть установлен log_pass');
        }
    }




    /**
     * Производит аутентификацию на сайте, при успехе возвращает true, при неудачи кидает исключение
     *
     * @param string $auth_data
     * @throws IntertelecomNotify_Exception
     * @return bool
     */
    protected function auth($auth_data) {

        $result = $this->request->post(static::URL_LOGIN, $auth_data);

        if ($result["url_request"] !== static::URL_STATISTIC && $result["http_code"] != 200) {

            throw new IntertelecomNotify_Exception("Ошибка при попытке авторизации. После отправки пароля не происходит редирект на страницу статистики.");

        } else {

            return true;

        }
    }




    /**
     * Запрашивает и обрабатывает полученные данные
     *
     * @param Abonent $abonent
     * @return void
     */
    public function getAndProcessData($abonent) {

        $result = $this->request->post(static::URL_STATISTIC, $abonent->getUserParam());
        $sec = 0;
        $more_info = '';

        if ($result["http_code"] != 200) {

            $new_mess = "Нет доступа к странице статистики абонента " . $abonent->getUserNumber() . " http_code:" . $result["http_code"];

            $this->checkAndSendMessage($new_mess, $abonent);

            return;
        }

        if ($data = $this->parserData($result["content"], static::REGEXP_ERROR)) {

            $new_mess = "Нет доступа к странице статистики абонента. " . $abonent->getUserNumber() . " Сообщение:" . $data["error_message"];

            $this->checkAndSendMessage($new_mess, $abonent);

            return;
        }

        if ($data = $this->parserData($result["content"], static::REGEXP_BAL_BASE)) {

            $more_info = $more_info . "На основном счёте - " . $data["left_time"] . " мин.\r\n";

            $sec = $sec + $data["sec"];
        }

        if ($data = $this->parserData($result["content"], static::REGEXP_BAL_100)) {

            $more_info = $more_info . "На счёте '100 мин. на мобильные' - " . $data["left_time"] . " мин.\r\n";

            $sec = $sec + $data["sec"];
        }

        if ($data = $this->parserData($result["content"], static::REGEXP_BAL_200)) {

            $more_info = $more_info . "На счёте '200 мин. на мобильные' - " . $data["left_time"] . " мин.\r\n";

            $sec = $sec + $data["sec"];
        }

        if (preg_match(static::REGEXP_BONUS_FIN, $result["content"], $matches)) {

            $more_info = $more_info . "На бонусном счету 'Наилучшее общение' - " . trim($matches[1]) . " гр. " . trim($matches[2]) . "\r\n";
        }

        if (preg_match(static::REGEXP_SALDO_FIN, $result["content"], $matches)) {

            $more_info = $more_info . "На основном счету - " . trim($matches[1]) . " гр.\r\n";
        }



        if ($sec < static::LEFT_SEC && $sec !== 0) {

            $h=str_pad((floor($sec/3600)), 2, '0', STR_PAD_LEFT);
            $m=str_pad((floor(($sec%3600)/60)), 2, '0', STR_PAD_LEFT);
            $s=str_pad((floor(($sec%3600)%60)), 2, '0', STR_PAD_LEFT);

            $new_mess = "На счету абонента " . $abonent->getUserNumber() . ", осталось " . $h . ":" . $m . ":" . $s . " на мобильные\r\n\r\nПодробнее:\r\n" . $more_info;

            $this->checkAndSendMessage($new_mess, $abonent);

        }


        if ($sec == 0 && $more_info != '') {

            $new_mess = "На основном счёте абонента " . $abonent->getUserNumber() . " закончились минуты на мобильные.\r\n\r\nПодробнее:\r\n" . $more_info;

            $this->checkAndSendMessage($new_mess, $abonent);
        }

        if ($sec == 0 && $more_info == '') {

            $new_mess = "На основном счёте абонента закончились минуты на мобильные, или произошла ошибка парсера. Не возможно получить оставшиеся минуты по номеру " . $abonent->getUserNumber();

            $this->checkAndSendMessage($new_mess, $abonent);
        }
    }


    /**
     * Сверяет новое сообщение с предыдущим, если они не совпадают шлет email
     *
     * @param string $new_mess
     * @param Abonent $abonent
     * @return void
     */
    protected function checkAndSendMessage($new_mess, $abonent) {

        if ($new_mess != $abonent->getUserLastMess()) {

            $this->abonent_repo->updateMessageAndResetCount($new_mess, $abonent->getUserNumber());

            $this->email_sender->send($new_mess, "Интертелеком. Оповещение для абонента " . $abonent->getUserNumber() . ".");


             echo str_replace("\r\n", "<br>", $new_mess . "\r\n\r\n");


        } else {

            $count = $abonent->getMessageCount();

            $count++;

            $this->abonent_repo->updateCount($count, $abonent->getUserNumber());


            echo str_replace("\r\n", "<br>", "Счетчик увеличен до " . $count ."\r\nСообщение: " . $new_mess . "\r\n\r\n");


        }
    }



    /**
     * Ищет в полученом HTML полезные данные и возвращает их в виде асоциативного массива.
     *
     * @param string $html
     * @param string $regexp
     * @return bool|array
     */
    protected function parserData($html, $regexp) {

        if (!preg_match($regexp, $html, $matches)) {

            return false;
        }

        ////В случае наличия сообщения об ошибке на странице.////

        if ($matches[1] == "error") {

            return [
                "error_message"=>$matches[2],
                "left_time"=>null,
                "sec"=>null
                ];
        }


        ////     В случае наличия баланса на странице     ////

        $left_time = $matches[1];
        $left_time_sec = explode(":", $matches[1]);

        if (!is_array($left_time_sec) || count($left_time_sec) != 3) {

            return false;
        }

        $sec = $left_time_sec[0] * 60 * 60 + $left_time_sec[1] * 60 + $left_time_sec[2];

        return [
            "error_message"=>null,
            "left_time"=>$left_time,
            "sec"=>$sec
            ];
    }



    /**
     * Производит выход с ресурса.
     *
     * @throws IntertelecomNotify_Exception
     * @return void
     */
    public function logout() {

        $result = $this->request->get(static::URL_LOGOUT);

        if ($result["url_request"] !== static::URL_LOGIN && $result["http_code"] != 200) {

            throw new IntertelecomNotify_Exception("Ошибка при попытке разлогиниться. После запроса на выход не происходит редирект на страницу авторизации.");
        }
    }
}