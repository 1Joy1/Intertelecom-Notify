<?php
/**
 * Class AbonentRepositorySourceMySql | AbonentRepositorySourceMySql.php
 */

/**
 */
require_once("ConfigLoader.php");
require_once("Abonent.php");
require_once("AbonentRepositorySource_Interface.php");


/**
 * MySQL реализация для хранилища абонентов.
 * Выполняет подключение к базе данных MySQL в которой хранится список абонентов
 * и их последних отправленных сообщений.
 * Возвращает список абонентов и их сообщения сгенерированные при успешном предыдущем запуске программы.
 * Возвращает колличество повторений сгенерированных сообщений одного и того же содержания и время последнего изменения состояния хранилища.
 * А так же реализует обновление сообщений в хранилище.
 *
 * @author Marshak Igor aka !Joy!
 * @package Intertelecom
 * @version v.1.1 (17.09.17)
 * @copyright Copyright (c) 2017 Marshak Igor aka !Joy!
 *
 */
class AbonentRepositorySourceMySql implements AbonentRepositorySource_Interface
{
    const NAME_TABLE = "Intertelecom_abonent_repository";

    const CONFIG_FILE_PATH = "config/config_db.json";


    /**
     * Объект соеденения с базой данных MySQL
     *
     * @var mysqli
     */
    protected $mysqli;



    /**
     * Конструктор, при создании объекта загружает настройки базы данных, а так же
     * запускает метод connect() для установки соеденения с базой данных.
     *
     * @return void
     */
    public function __construct() {

        $config_loader = new ConfigLoader(static::CONFIG_FILE_PATH);

        $config = $config_loader->getConfig();

        $this->validateConfig($config);

        $this->connect($config);
    }


    /**
     * Проверка на валидность конфигурационных данных для подключения к БД.
     *
     * @param StdClass $config
     * @throws AbonentRepository_Exception
     * @return void
     */
    protected function validateConfig($config) {

        if (!is_object($config)) {

            throw new AbonentRepository_Exception("Ошибка config должен быть объектом");
        }

        if (!isset($config->user_db) || !isset($config->pasword_db) ||
            !isset($config->host_db) || !isset($config->name_db)) {

            throw new AbonentRepository_Exception("Ошибка. В конфигурации должны быть установлены user_db, pasword_db, host_db, name_db");
        }
    }



    /**
     * Настраивает и устанавливает соединение с базой данных.
     *
     * @param StdClass $config
     * @throws AbonentRepository_Exception
     * @return void
     */
    protected function connect($config) {

        $this->mysqli = new mysqli($config->host_db, $config->user_db,
                                   $config->pasword_db, $config->name_db);

        if ($this->mysqli->connect_errno) {

            throw new AbonentRepository_Exception("Во время проверки сообщений, скрипту не удалось подключиться к базе данных. \r\n" . $this->mysqli->connect_error);
        }

        if (!$this->mysqli->set_charset("utf8")) {

            throw new AbonentRepository_Exception("Во время проверки сообщений, скрипту не удалось в базе данных установить кодировку на utf8. \r\n" .  $this->mysqli->error);
        }
    }



    /**
     * Обновление (увеличение) счетчика сообщений в базе данных.
     *
     * @param int $count
     * @param string $abonent_number
     * @throws AbonentRepository_Exception
     * @return void
     */
    public function updateCount($count, $abonent_number) {

        $count = $this->mysqli->real_escape_string($count);

        $abonent_number = $this->mysqli->real_escape_string($abonent_number);

        if ($this->mysqli->query("UPDATE `" . static::NAME_TABLE . "` SET `count` = '$count' WHERE `number` LIKE '$abonent_number'")) {

        } else {

            throw new AbonentRepository_Exception("Не удалось обновить запись (счетчика) в БД. Ошибка:" . $this->mysqli->error );

        }
    }



    /**
     * Обновление сообщения и сброс счетчика сообщений в базе данных.
     *
     * @param string $message
     * @param string $abonent_number
     * @throws AbonentRepository_Exception
     * @return void
     */
    public function updateMessageAndResetCount($message, $abonent_number) {

        $message = $this->mysqli->real_escape_string($message);

        $abonent_number = $this->mysqli->real_escape_string($abonent_number);

        if ($this->mysqli->query("UPDATE `" . static::NAME_TABLE . "` SET `message` = '$message', `count` = '1' WHERE `number` LIKE '$abonent_number'")) {

        } else {

            throw new AbonentRepository_Exception("Не удалось обновить запись в БД. Ошибка:" . $this->mysqli->error );

        }
    }

    /**
     * Получает всех абонентов
     *
     * @throws AbonentRepository_Exception
     * @return Abonent[]
     */
    public function getAllAbonents() {

        if ($result = $this->mysqli->query("SELECT `number`,`param`,`message`,`count` FROM `" . static::NAME_TABLE . "` ORDER BY `number` DESC")) {

            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {

                $abonents[] = new Abonent ($row["number"], $row["param"], $row["message"], $row["count"]);
            }

            $result->free();

            return $abonents;

        } else {

            throw new AbonentRepository_Exception("Не удалось получить всех абонентов из базы данных. Ошибка:" . $this->mysqli->error );
        }

    }



    /**
     * Диструктор закрывает соеденение с базой данных при уничтожении объекта
     *
     * @return void
     */
    public function __destruct() {

        $this->mysqli->close();
    }
}
