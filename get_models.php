<?php
require_once 'config.php';
checkLogin();

header('Content-Type: application/json');

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if ($category_id > 0) {
    $models = [];
    $result = $conn->query("SELECT id, model_name, manufacturer 
                           FROM asset_models 
                           WHERE category_id = $category_id AND status = 'active' 
                           ORDER BY manufacturer, model_name");
    
    while ($row = $result->fetch_assoc()) {
        $models[] = [
            'id' => $row['id'],
            'model_name' => $row['model_name'],
            'manufacturer' => $row['manufacturer']
        ];
    }
    
    echo json_encode(['success' => true, 'models' => $models]);
} else {
    echo json_encode(['success' => false, 'models' => [], 'message' => 'Invalid category']);
}
?>