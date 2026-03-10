<?php
/**
 * AgriConnect – Resource Management Backend
 * ==========================================
 * Handles saving and retrieving farmer resource records:
 * - Water usage
 * - Fertilizer usage and type
 * - Labour count
 * - Total cost
 *
 * POST: Save new resource entry
 * GET:  Retrieve existing records
 */

require_once 'db_connect.php';

// === Handle GET: Fetch all resource records ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit  = intval($_GET['limit']  ?? 20);
    $offset = intval($_GET['offset'] ?? 0);

    $result = $conn->query(
        "SELECT * FROM resources ORDER BY record_date DESC, id DESC LIMIT $limit OFFSET $offset"
    );

    $records = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }

    // Get summary stats
    $stats = $conn->query(
        "SELECT 
            SUM(water_usage)      AS total_water,
            SUM(fertilizer_usage) AS total_fertilizer,
            SUM(labour_count)     AS total_labour,
            SUM(total_cost)       AS total_cost,
            COUNT(*)              AS total_entries
         FROM resources"
    );
    $summary = $stats ? $stats->fetch_assoc() : [];

    sendJSON(true, 'Records fetched successfully.', [
        'records' => $records,
        'summary' => $summary,
        'count'   => count($records),
    ]);
}

// === Handle POST: Save new resource entry ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and sanitize input
    $record_date      = sanitize($conn, $_POST['record_date']      ?? date('Y-m-d'));
    $field_name       = sanitize($conn, $_POST['field_name']       ?? '');
    $crop_name        = sanitize($conn, $_POST['crop_name']        ?? '');
    $water_usage      = floatval($_POST['water_usage']             ?? 0);
    $fertilizer_usage = floatval($_POST['fertilizer_usage']        ?? 0);
    $fertilizer_type  = sanitize($conn, $_POST['fertilizer_type']  ?? 'NPK');
    $labour_count     = intval($_POST['labour_count']              ?? 0);
    $total_cost       = floatval($_POST['total_cost']              ?? 0);
    $notes            = sanitize($conn, $_POST['notes']            ?? '');

    // Validate required fields
    if (empty($field_name) || empty($crop_name)) {
        sendJSON(false, 'Field name and Crop name are required.');
    }

    if ($water_usage < 0 || $fertilizer_usage < 0 || $labour_count < 0) {
        sendJSON(false, 'Resource values cannot be negative.');
    }

    // Insert into resources table
    $stmt = $conn->prepare(
        "INSERT INTO resources 
         (record_date, field_name, crop_name, water_usage, fertilizer_usage, fertilizer_type, labour_count, total_cost, notes, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );

    if (!$stmt) {
        sendJSON(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param(
        'sssddsdds',
        $record_date,
        $field_name,
        $crop_name,
        $water_usage,
        $fertilizer_usage,
        $fertilizer_type,
        $labour_count,
        $total_cost,
        $notes
    );

    if ($stmt->execute()) {
        $insertId = $conn->insert_id;
        $stmt->close();
        $conn->close();

        sendJSON(true, '✅ Resource data saved successfully!', [
            'id'        => $insertId,
            'field'     => $field_name,
            'crop'      => $crop_name,
            'water'     => "$water_usage L",
            'fertilizer'=> "$fertilizer_usage kg $fertilizer_type",
            'labour'    => $labour_count,
            'cost'      => "₹$total_cost",
        ]);
    } else {
        sendJSON(false, 'Failed to save resource data: ' . $stmt->error);
    }
}

// If neither GET nor POST
sendJSON(false, 'Invalid request method.');
