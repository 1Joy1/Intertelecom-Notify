<?php
/**
 * Class ConfigLoader | ConfigLoader.php
 */

/**
 * Загружает конфигурационные данные из файла JSON
 *
 * @author Marshak Igor aka !Joy!
 * @package Intertelecom
 * @version v.1.0 (11.09.17)
 * @copyright Copyright (c) 2017 Marshak Igor aka !Joy!
 *
 */
class ConfigLoader
{

    /**
     * Объект получаемый из файла JSON при помощи json_decode()
     *
     * @var stdClass
     */
    protected $config;



    /**
     * Конструктор. Принимает путь к  конфиггурационному файлу,
     * вызывает метод проверки наличия файла и метод получения конфига.
     *
     * @param string $file
     * @return void
     */
    public function __construct($file) {

        $this->checkAvailableFile($file);

        $this->createConfigFromFile($file);
    }


    /**
     * Проверка наличия файла
     *
     * @param string $file
     * @throws ConfigLoader_Exception
     * @return void
     */
    protected function checkAvailableFile($file) {

        if (!file_exists($file)) {

            throw new ConfigLoader_Exception("Ошибка загрузки конфигурационного файла. Файл $file отсутствует.");
        }
    }


    /**
     * Создание объекта с конфигом
     *
     * @param string $file
     * @throws ConfigLoader_Exception
     * @return void
     */
    protected function createConfigFromFile($file) {

        $this->config = json_decode(file_get_contents($file));


        if (json_last_error() !== JSON_ERROR_NONE) {

            throw new ConfigLoader_Exception("Ошибка парсера JSON. Конфигурационный файл $file должен быть JSON");
        }

    }


    /**
     * Возвращает конфиг
     *
     * @return stdClass
     */
    public function getConfig() {

        return $this->config;
    }

}