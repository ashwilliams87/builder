<?php
//Предполагается, что данный файл лежит в папке jirbis2/components/com_irbis
// Все функции, необходимые клиенту, реализованы в классе BaseJsonRpcClient.php. Эта библиотека распространяется свободно (по лицензии GPL)

use App\Http\Helper\Bonch\BaseJsonRpcClient;

require_once('includes/BaseJsonRpcClient.php');
require_once('includes/u.php');
require_once('includes/Record.php');
require_once('includes/jrecord.php');


/**
 * Инициализация JSON-RPC провайдера
 * @param string $url Адрес JSON-RPC провайдера. В данном примере: http://localhost//jirbis2//components//com_irbis//ajax_provider.php -- адрес файла JSON провайдера. task=rpc&class=jwrapper -- обязательные параметры запроса
 * @return object Интерфейс
 */

$client = new BaseJsonRpcClient('http://lib.sut.ru//jirbis2_spbgut//components//com_irbis//ajax_provider.php?task=rpc&class=jwrapper');


// Начало выполнения пакетного запроса, состоящего из нескольких команд.
$client->BeginBatch();
// Авторизация. Первый параметр -- логин, второй параметр -- пароль.

/**
 * Авторизация. На данный момент учетная запись соответствует той,
 * которая указывались при установке и используются для  доступа к ИРБИС TCP/IP серверу. Откорректируйте логин и пароль, если ваша запись отличется от этой!
 *
 * @param string $login
 * @param string $password
 * @return int Количество найденных записей
 */
$client->rpc_auth('1', '1');


$profile = array('brief' => array('format' => '@jbrief', 'type' => 'bo'));

/**
 * Получает поля и характеристики записи в виде сложного массива, соответствующего по своей структуре классу jrocord.php
 *
 * @param string $base Название базы
 * @param string $req Запрос на языке запросов ИРБИС
 * @param string $seq Название формата, используемого для последовательного поиска в базе
 * @param string $first Номер первой считываемой записи
 * @param string $portion Количество считываемых записей
 * @param string $profile Профиль расформатирования записей
 * @return object BaseJsonRpcCall В элементе Result Массив записей типа jirbisrec
 */
// Отбор учебниоков, учебных пособий, курсов лекций, методических указаний.
$recs_formated = $client->find_jrecords('IBIS', '(<.>V=EXT<.>)*(<.>HD=J<.>+<.>HD=J0<.>+<.>HD=J2<.>+<.>HD=J4<.>+<.>HD=J5<.>)', '', 1, 10, $profile);

// Отправка пакета запросов
if (!$client->CommitBatch())
    echo 'Ошибка при выполнении запроса. Возможно, неправильный ответ от сервера, или отсутствие связи с сервером.';

// Поскольку выполнение запросов происходит не сразу, обработка ошибок должна быть здесь, после отправки пакета запросов.
if (!empty($results_count->Error))
    echo "Тип ошибки: $results_count->message Расшифровка $results_count->data Код ошибки: $results_count->code";
$i = 1;


foreach ($recs_formated->Result as $r) {
    echo ($i++) . '. ' . $r['formating']['brief']['value'] . "\n";
}


// Берём первую запись
$rec_as_array = $recs_formated->Result[0];

print_r($rec_as_array);

/* Трансформируем массив в объект типа jRecord. Это не обязательно,  но жалетельно, так как позволяет значительно легче работать с подполями
    Помимо подполей, которые описаны в описании текстового формата САБ ИРБИС, требуется получить и использовать 2 поля: поле с адресом файла (951) и поле с идентификатором записи (903)
  [951] => Array
            (
                [0] => ^A/a546/e6f80a89/napravlini_regionalnoi_politiki_chast21.pdf
            )
  [903] => Array
            (
                [0] => 32/М 43-884087
            )

*/

//==================================== ВЫГРУЗКА ФАЙЛА	========================================================
$rec = new jrecord();
$rec->SetContent($rec_as_array['Content']);
// Относительный путь к файлу
$file_relative_path = $rec->GetSubField(951, 1, 'A');


// Выгрузка файла.
$client->BeginBatch();
$client->rpc_auth('1', '1');


$result = $client->GetFile(10, 'IBIS', $file_relative_path);

if (!$client->CommitBatch())
    echo 'Ошибка при выполнении запроса. Возможно, неправильный ответ от сервера, или отсутствие связи с сервером.';


$file_content_encoded = $result->Result;

file_put_contents(getVarDir() . '/bonch', base64_decode($file_content_encoded));


?>
