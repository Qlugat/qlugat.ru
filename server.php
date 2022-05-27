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
$host="127.0.0.1";
$port=3306;
$socket="";
$user="admin";
$password="admin";
$dbname="qlugat";

$mysqli = new mysqli($host, $user, $password, $dbname, $port, $socket)
	or die ('Could not connect to the database server' . mysqli_connect_error());
$mysqli->set_charset("utf8");

function first($x) {
    return $x[0];
}

// экшоны
if ($action == 'suggest') {
    $stmt = $mysqli->prepare('SELECT word FROM word WHERE stem LIKE ? ORDER BY stem');
    $word = get_stem($word) . '%';
    $stmt->bind_param('s', $word);
    $stmt->execute();
    $result = $stmt->get_result();
    $arr = $result->fetch_all();

    echo '["' . implode('","', array_map('first', $arr)) . '"]';
}
else if ($action == 'get_word') {
    $stmt = $mysqli->prepare('SELECT * FROM word WHERE word = ?');
    $stmt->bind_param('s', $word);
    $stmt->execute();
    $result = $stmt->get_result();
    $word = $result->fetch_assoc();
    
    if ($word) {
        $stmt = $mysqli->prepare('SELECT text, accent_pos FROM article WHERE word_id = ?');
        $stmt->bind_param('s', $word['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        for ($set = array (); $row = $result->fetch_assoc(); $set[] = $row);

        echo json_encode(array(
            'word' => $word['word'],
            'dict' => $word['dict'],
            'shortening_pos' => $word['shortening_pos'],
            'articles' => $set
        ));
    } else {
        echo '{}';
    }
}


$mysqli->close();
