<?php
require 'db.php';
header('Content-Type: application/json');

// 1. Получаем параметры от сайта
// class_id должен совпадать с тем, что в твоем HTML (data-class-id)
$class_id = $_GET['class_id'] ?? 1;
$discipline = $_GET['discipline'] ?? 'speed'; // speed, marathon, konyuhov, gump
$gender = $_GET['gender'] ?? 'all';

// 2. ТОЧНАЯ КАРТА ДИСЦИПЛИН (ИЗ ТВОЕЙ БД)
$routes_map = [
    'speed'    => 1, // ID 1: GPS-speed
    'marathon' => 2, // ID 2: Marathon
    'konyuhov' => 3, // ID 3: Fedor_Konyhov
    'gump'     => 4  // ID 4: Forrest_Gamp
];

// Определяем ID маршрута для поиска в базе
$route_id = $routes_map[$discipline] ?? 1;

try {
    // 3. СТРОИМ ЗАПРОС
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

    // Фильтр по полу (если нажат фильтр М или Ж)
    if ($gender !== 'all') {
        $sql .= " AND u.gender = :gender";
    }

    // 4. СОРТИРОВКА (КРИТИЧЕСКИ ВАЖНО)
    if ($route_id == 1 || $route_id == 2) {
        // ID 1 (Скорость) и ID 2 (Марафон):
        // Результат — это ВРЕМЯ. Кто потратил МЕНЬШЕ времени, тот первый.
        $sql .= " ORDER BY r.route_time ASC"; 
    } else {
        // ID 3 (Конюхов) и ID 4 (Гамп):
        // Результат — это ДИСТАНЦИЯ. У кого БОЛЬШЕ, тот первый.
        $sql .= " ORDER BY r.route_time DESC"; 
    }

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

    // 5. ОБРАБОТКА ДАННЫХ (ФОРМАТИРОВАНИЕ)
    $final_results = [];
    
    foreach ($raw_results as $row) {
        $val = $row['route_time']; // Значение из базы
        $display_value = '';
        $points = 0; // Место для очков

        // ID 1: GPS-SPEED
        // Значение в базе = время (секунды) на 500м (или другое, если логика сложнее).
        // Считаем: (500м / время) * 3.6 = км/ч
        if ($route_id == 1) {
            if ($val > 0) {
                // ПРИМЕР: Если val = время на 500м
                $speed = (500 / $val) * 3.6; 
                $display_value = number_format($speed, 2) . ' км/ч';
            } else {
                $display_value = '0.00 км/ч';
            }
        } 
        // ID 2: MARATHON
        // Значение в базе = время (секунды)
        // Формат: ЧЧ:ММ:СС
        elseif ($route_id == 2) {
            $hours = floor($val / 3600);
            $mins = floor(($val / 60) % 60);
            $secs = $val % 60;
            $display_value = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        }
        // ID 3 & 4: DISTANCE (Конюхов и Гамп)
        // Значение в базе = метры (или км)
        // Выводим как км
        else {
            // Если в базе метры:
            $dist_km = $val / 1000;
            $display_value = number_format($dist_km, 2) . ' км';
            
            // Если в базе уже километры, просто:
            // $display_value = $val . ' км';
        }

        $final_results[] = [
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'city' => $row['city'],
            'country' => $row['country'],
            'display_value' => $display_value,
            'points' => $points
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $final_results]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
