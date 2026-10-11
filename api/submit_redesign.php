<?php
// api/submit_redesign.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../admin/config/db.php';

$action = $_POST['action'] ?? '';

if ($action === 'start') {
    // Track the start of the form
    $order_id = !empty($_POST['order_id']) ? intval($_POST['order_id']) : null;
    $customer_name = htmlspecialchars($_POST['customer_name'] ?? '');
    $customer_email = htmlspecialchars($_POST['customer_email'] ?? '');
    $customer_whatsapp = htmlspecialchars($_POST['customer_whatsapp'] ?? '');

    if (!$customer_name) {
        echo json_encode(["error" => "Name is required"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO cv_redesign_requests (order_id, customer_name, customer_email, customer_whatsapp, status) VALUES (?, ?, ?, ?, 'started')");
        $stmt->execute([$order_id, $customer_name, $customer_email, $customer_whatsapp]);
        $request_id = $pdo->lastInsertId();
        echo json_encode(["success" => true, "request_id" => $request_id]);
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} elseif ($action === 'complete') {
    // Complete the form submission
    $request_id = !empty($_POST['request_id']) ? intval($_POST['request_id']) : null;
    $reason = $_POST['reason'] ?? null;
    
    if (!$request_id) {
        echo json_encode(["error" => "Request ID is required"]);
        exit;
    }

    try {
        $payment_proof_path = null;
        $old_cv_path = null;
        
        $upload_dir = '../uploads/redesigns/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (!empty($_FILES['payment_proof']['name'])) {
            $filename = time() . '_' . basename($_FILES['payment_proof']['name']);
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target)) {
                $payment_proof_path = 'uploads/redesigns/' . $filename;
            }
        }

        if (!empty($_FILES['old_cv']['name'])) {
            $filename = time() . '_cv_' . basename($_FILES['old_cv']['name']);
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['old_cv']['tmp_name'], $target)) {
                $old_cv_path = 'uploads/redesigns/' . $filename;
            }
        }

        // Update the record
        $stmt = $pdo->prepare("UPDATE cv_redesign_requests SET reason = ?, payment_proof_path = IFNULL(?, payment_proof_path), old_cv_path = IFNULL(?, old_cv_path), status = 'pending' WHERE id = ?");
        $stmt->execute([$reason, $payment_proof_path, $old_cv_path, $request_id]);

        echo json_encode(["success" => true, "message" => "Request completed successfully"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid action"]);
}
?>
