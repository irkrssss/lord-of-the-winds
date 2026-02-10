<?php
// api_results.php
require 'db.php';
header('Content-Type: application/json');

// Получаем параметры от сайта
$class_id = $_GET['class_id'] ?? 1;      // ID класса (например, 2 = Kite Ski)
$discipline = $_GET['discipline'] ?? 'speed'; // speed, marathon, konyuhov, gump
$gender = $_GET['gender'] ?? 'all';      // all, male, female

try {
    // Базовый запрос: объединяем результаты и пользователей
    // ВАЖНО: Проверь названия своих таблиц! (run_results, run_users)
    $sql = "
        SELECT 
            u.first_name, 
            u.last_name, 
            u.city, 
            u.country,
            r.result_value, 
            r.points
        FROM run_results r
        JOIN run_users u ON r.user_id = u.id
        WHERE r.class_id = :class_id 
        AND r.discipline_type = :discipline
        AND r.status = 'approved'
    ";

    // Фильтр по полу (если нужен)
    if ($gender !== 'all') {
        $sql .= " AND u.gender = :gender";
    }

    // Сортировка
    // Марафоны (время) - кто меньше, тот лучше (ASC)
    // Остальное (дистанция, скорость) - кто больше, тот лучше (DESC)
    if ($discipline === 'marathon') {
        $sql .= " ORDER BY r.result_value ASC";
    } else {
        $sql .= " ORDER BY r.result_value DESC";
    }

    $stmt = $pdo->prepare($sql);
    
    // Привязываем параметры
    $params = [
        ':class_id' => $class_id,
        ':discipline' => $discipline
    ];
    if ($gender !== 'all') {
        $params[':gender'] = $gender;
    }

    $stmt->execute($params);
    $results = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $results]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
