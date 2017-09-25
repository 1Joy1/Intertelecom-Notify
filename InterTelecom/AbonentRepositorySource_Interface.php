<?php
/**
 * AbonentRepositorySource_Interface | AbonentRepositorySource_Interface.php
 */
/**
 * Интерфейс репозитория абонентов
 *
 * @author Marshak Igor aka !Joy!
 * @package Intertelecom
 * @version v.1.1 (17.09.17)
 * @copyright Copyright (c) 2017 Marshak Igor aka !Joy!
 */
interface AbonentRepositorySource_Interface
{
    /**
     * Обновление (увеличение) счетчика сообщений в базе данных.
     *
     * @param int $count
     * @param string $abonent_number
     * @return void
     */
    public function updateCount($count, $abonent_number);


    /**
     * Обновление сообщения и сброс счетчика сообщений в базе данных.
     *
     * @param string $message
     * @param string $abonent_number
     * @return void
     */
    public function updateMessageAndResetCount($message, $abonent_number);


    /**
     * Получает всех абонентов
     *
     * @return array
     */
    public function getAllAbonents();
}