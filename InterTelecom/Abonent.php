<?php
/**
 * Class Abonent | Abonent.php
 */

/**
 * Объекты типа Abonent
 *
 * @author Marshak Igor aka !Joy!
 * @package Intertelecom
 * @version v.1.0 (11.09.17)
 * @copyright Copyright (c) 2017 Marshak Igor aka !Joy!
 *
 */
class Abonent
{
    /**
     * Номер индетифицируйщий абонента.
     *
     * @var string
     */
    protected $user_number;

    /**
     * Параметр абонента для POST запроса.
     * Строка вида "phone=487123456&cp=1"
     * где 487123456 номер телефона с кодом города без первого нуля.
     *
     * @var string
     */
    protected $user_param;

    /**
     * Последнее сообщение, сгенерированное при предыдущем запуске программы.
     *
     * @var string
     */
    protected $user_last_mess;

    /**
     * Колличество повторений сгенерированных сообщений одного и того же содержания.
     *
     * @var string
     */
    protected $user_mess_count;


    /**
     * Устанавливает свойства объекта: user_number, user_param, user_last_mess, user_mess_count.
     * @param string $user_number
     * @param string $user_param
     * @param string $user_last_mess
     * @param string $user_mess_count
     * @throws Abonent_Exception
     * @return void
     */
    public function  __construct($user_number, $user_param, $user_last_mess, $user_mess_count) {

        if (!isset($user_number) ) {

            throw new Abonent_Exception('Не получен параметр $number для создания объекта.');
        }

        if (!isset($user_param)) {

            throw new Abonent_Exception('Не получен параметр $param для создания объекта.');
        }

        if (!isset($user_last_mess) ) {

            throw new Abonent_Exception('Не получен параметр $user_last_mess для создания объекта.');
        }

        if (!isset($user_mess_count) ) {

            throw new Abonent_Exception('Не получен параметр $user_mess_count для создания объекта.');
        }

        $this->user_number = $user_number;

        $this->user_param = $user_param;

        $this->user_last_mess = $user_last_mess;

        $this->user_mess_count = $user_mess_count;
    }


    /**
     * Возвращает значение свойства user_number
     *
     * @return string
     */
    public function getUserNumber() {

        return $this->user_number;
    }

    /**
     * Возвращает значение свойства user_param
     *
     * @return string
     */
    public function getUserParam() {

        return $this->user_param;
    }

    /**
     * Возвращает значение свойства user_last_mess
     *
     * @return string
     */
    public function getUserLastMess() {

        return $this->user_last_mess;
    }

    /**
     * Возвращает значение свойства user_mess_count
     *
     * @return string
     */
    public function getMessageCount() {

        return $this->user_mess_count;
    }
}