<?php
// modules/clients/save.php
session_start();
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../includes/db.php';

// Auth Check
if (!isset($_SESSION['user_id'])) exit(json_encode(['success'=>false, 'message'=>'Unauthorized']));

$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;
$company = $input['company_name'];
$contact = $input['contact_person'];
$email = $input['email'];
$address = $input['address'];

if ($id) {
    // Update
    $stmt = $conn->prepare("UPDATE clients SET company_name=?, contact_person=?, email=?, address=? WHERE id=?");
    $stmt->bind_param("ssssi", $company, $contact, $email, $address, $id);
} else {
    // Insert
    $stmt = $conn->prepare("INSERT INTO clients (company_name, contact_person, email, address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $company, $contact, $email, $address);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
?>