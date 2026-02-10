<?php
require 'db.php';
header('Content-Type: application/json');

// 1. Получаем параметры
$class_id = $_GET['class_id'] ?? 1;
$discipline = $_GET['discipline'] ?? 'speed';
$gender = $_GET['gender'] ?? 'all';

// 2. КАРТА ДИСЦИПЛИН (ВАЖНО!)
// Здесь мы говорим, какой ID маршрута соответствует какой вкладке.
// Проверь в таблице run_routes, какие ID за что отвечают. Пока предполагаем:
$routes_map = [
    'speed'    => 1, // route_id = 1 это Скорость
    'marathon' => 2, // route_id = 2 это Марафон
    'konyuhov' => 3, // route_id = 3 это Конюхов
    'gump'     => 4  // route_id = 4 это Гамп
];

$route_id = $routes_map[$discipline] ?? 1;

try {
    // 3. SQL ЗАПРОС (Адаптирован под твой скриншот)
    $sql = "
        SELECT 
            u.first_name, 
            u.last_name, 
            u.city, 
            u.country,
            r.route_time, 
            r.id as result_id
        FROM run_results r
        LEFT JOIN run_users u ON r.user_id = u.id
        WHERE r.class_id = :class_id 
        AND r.route_id = :route_id
        AND r.finished = 1
    ";

    // Фильтр по полу (предполагаем, что в run_users есть колонка gender)
    if ($gender !== 'all') {
        $sql .= " AND u.gender = :gender";
    }

    // СОРТИРОВКА
    // Для Скорости и Марафона: чем меньше время, тем лучше (ASC)
    // Для Дистанции (Конюхов): чем больше, тем лучше (DESC) - если route_time там хранит метры
    // Пока сортируем по времени (кто быстрее)
    $sql .= " ORDER BY r.route_time ASC"; 

    $stmt = $pdo->prepare($sql);
    
    $params = [
        ':class_id' => $class_id,
        ':route_id' => $route_id
    ];
    if ($gender !== 'all') {
        $params[':gender'] = $gender;
    }

    $stmt->execute($params);
    $raw_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. ОБРАБОТКА ДАННЫХ (Превращаем секунды в км/ч или красивое время)
    $final_results = [];
    
    foreach ($raw_results as $row) {
        $time_seconds = $row['route_time']; // Например, 771 секунда
        $display_value = '';
        $points = 0; // Пока ставим 0, если нет формулы очков

        if ($discipline === 'speed') {
            // ФОРМУЛА СКОРОСТИ (Если route_time это время на 500м)
            // Скорость (км/ч) = (Дистанция (м) / Время (с)) * 3.6
            // 500 / 20 сек * 3.6 = 90 км/ч
            if ($time_seconds > 0) {
                $speed = (500 / $time_seconds) * 3.6;
                $display_value = number_format($speed, 2) . ' км/ч';
            } else {
                $display_value = '0.00 км/ч';
            }
        } 
        elseif ($discipline === 'marathon') {
            // ФОРМАТ ВРЕМЕНИ (ЧЧ:ММ:СС)
            $hours = floor($time_seconds / 3600);
            $mins = floor(($time_seconds / 60) % 60);
            $secs = $time_seconds % 60;
            $display_value = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        }
        else {
            // Для остальных пока просто выводим число
            $display_value = $time_seconds; 
        }

        // Собираем красивый массив для JS
        $final_results[] = [
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'city' => $row['city'],
            'country' => $row['country'],
            'display_value' => $display_value, // Уже готовое значение (90 км/ч или 01:20:00)
            'points' => $points
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $final_results]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
