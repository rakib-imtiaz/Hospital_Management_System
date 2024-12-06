<?php
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_GET['department_id'])) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT doctor_id, name, specialization 
        FROM doctor 
        WHERE department_id = ? 
        ORDER BY name
    ");
    $stmt->execute([$_GET['department_id']]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($doctors);
} catch (PDOException $e) {
    error_log("Error fetching doctors: " . $e->getMessage());
    echo json_encode([]);
}
?> 