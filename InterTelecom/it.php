<?php
/**
 * it.php
 *
 * @author Marshak Igor aka !Joy!
 * @package Intertelecom
 * @version v.1.0 (11.09.17)
 * @copyright Copyright (c) 2017 Marshak Igor aka !Joy!
 *
 */

//ini_set("display_errors",1);
//ini_set('error_reporting',2047);
//error_reporting(E_ALL);

echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';

require_once("IntertelecomNotify.php");
require_once("EmailSender.php");
require_once("IT_Exception.php");



$time_start = microtime(true);



try {

    new IntertelecomNotify();

} catch (AbonentRepository_Exception $e) {

    $email_sender = new EmailSender;

    $email_sender->send("Ошибка базы данных.\r\n" . $e->getMessage() .
        "\r\nCбой произошёл в файле: " . $e->getFile() . " на строке " .  $e->getLine());

    echo "<pre>" . $e . "</pre>";

} catch (Exception $e) {

    $email_sender = new EmailSender;

    $email_sender->send($e->getMessage() .
        "\r\nCбой произошёл в файле: " . $e->getFile() . " на строке " .  $e->getLine());

    echo "<pre>" . $e . "</pre>";
}



$time = microtime(true) - $time_start;

echo "<br>" . date('d/m/Y G:i:s', time()) . " - Выполнение скрипта " . $time . " секунд <br>";
