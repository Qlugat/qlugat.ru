<?php
header('Content-Type: application/json; charset=utf-8');

function get_stem($word) {
    $word = strtolower($word);
    $word = str_replace('ё', 'е', $word);
    $word = str_replace('â', 'a', $word);
    $word = str_replace('ç', 'c', $word);
    $word = str_replace('ğ', 'g', $word);
    $word = str_replace('ı', 'i', $word);
    $word = str_replace('ñ', 'n', $word);
    $word = str_replace('ö', 'o', $word);
    $word = str_replace('q', 'k', $word);
    $word = str_replace('ş', 's', $word);
    $word = str_replace('ü', 'u', $word);

    return $word;
}


$word = $_GET['word'];
$action = $_GET['action'];

// Соединение, выбор базы данных
$dbconn = pg_connect("host=localhost dbname=qlugat user=qlugat password=qlugat")
    or die('Не удалось соединиться: ' . pg_last_error());


// экшоны
if ($action == 'suggest') {
    $query = 'SELECT word FROM "WORD" WHERE stem LIKE $1 ORDER BY stem';
    $result = pg_query_params($query, [get_stem($word) . '%'])
        or die('Ошибка запроса: ' . pg_last_error());

    $arr = pg_fetch_all_columns($result);
    echo '["' . implode('","', $arr) . '"]';
}
else if ($action == 'get_word') {
    $result = pg_query_params('SELECT * FROM "WORD" WHERE word = $1', [$word])
        or die('Ошибка запроса: ' . pg_last_error());

    $word = pg_fetch_assoc($result);
    
    if ($word) {
        $result = pg_query_params('SELECT text, accent_pos FROM "ARTICLE" WHERE word_id = $1', [$word['id']])
            or die('Ошибка запроса: ' . pg_last_error());

        echo json_encode(array(
            'word' => $word['word'],
            'dict' => $word['dict'],
            'shortening_pos' => $word['shortening_pos'],
            'articles' => pg_fetch_all($result)
        ));
    } else {
        echo '{}';
    }
}


// Очистка результата
pg_free_result($result);
// Закрытие соединения
pg_close($dbconn);
