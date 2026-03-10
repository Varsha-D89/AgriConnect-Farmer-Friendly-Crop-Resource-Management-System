<?php
/**
 * AgriConnect – Crop Image Upload & Health Detection
 * ====================================================
 * Receives an uploaded crop leaf image from the frontend,
 * validates it, saves it to the uploads directory,
 * and records the submission in the crop_images database table.
 *
 * In production: integrate with a Plant Disease ML API
 * (e.g., PlantVillage, Custom TensorFlow model, or Google Vision AI).
 *
 * POST Parameters (multipart/form-data):
 *   crop_image    : file (image/jpeg, image/png, image/webp)
 *   crop_type     : string (rice, wheat, maize, etc.)
 *   symptom_desc  : string (optional description of symptoms)
 *   farmer_name   : string (optional farmer name)
 */

require_once 'db_connect.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(false, 'Invalid request method. Use POST.');
}

// === Check if file was uploaded ===
if (!isset($_FILES['crop_image']) || $_FILES['crop_image']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory missing.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
    ];
    $errCode = $_FILES['crop_image']['error'] ?? UPLOAD_ERR_NO_FILE;
    sendJSON(false, $uploadErrors[$errCode] ?? 'Unknown upload error.');
}

$file      = $_FILES['crop_image'];
$cropType  = sanitize($conn, $_POST['crop_type']     ?? 'unknown');
$symptoms  = sanitize($conn, $_POST['symptom_desc']  ?? '');
$farmerName= sanitize($conn, $_POST['farmer_name']   ?? 'Anonymous');

// === Validate File Type ===
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($mimeType, $allowedMimes)) {
    sendJSON(false, 'Invalid file type. Only JPG, PNG, WEBP, and GIF images are allowed.');
}

// === Validate File Size (max 5MB) ===
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    sendJSON(false, 'Image too large. Maximum allowed size is 5MB.');
}

// === Create Upload Directory if it doesn't exist ===
$uploadDir = __DIR__ . '/../uploads/crop_images/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// === Generate Unique Filename ===
$ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
$filename = 'crop_' . date('Ymd_His') . '_' . uniqid() . '.' . strtolower($ext);
$savePath = $uploadDir . $filename;

// === Move Uploaded File ===
if (!move_uploaded_file($file['tmp_name'], $savePath)) {
    sendJSON(false, 'Failed to save the uploaded file. Check directory permissions.');
}

// === Save to Database ===
$fileSizeKB = round($file['size'] / 1024, 2);
$stmt = $conn->prepare(
    "INSERT INTO crop_images 
     (farmer_name, crop_type, filename, file_size_kb, mime_type, symptom_desc, analysis_status, upload_date)
     VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())"
);

if (!$stmt) {
    sendJSON(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param('sssds s',
    $farmerName,
    $cropType,
    $filename,
    $fileSizeKB,
    $mimeType,
    $symptoms
);

// Note: In real production, here you would:
// 1. Send the image to an ML API (TensorFlow Serving / Flask microservice)
// 2. Parse the response for disease probabilities
// 3. Store the analysis result in the database
// For now, we use a simulated response

$analysisResult = simulateDiseaseAnalysis($cropType);

// Update stmt to include analysis
$stmt->close();
$stmt = $conn->prepare(
    "INSERT INTO crop_images 
     (farmer_name, crop_type, filename, file_size_kb, mime_type, symptom_desc, analysis_result, analysis_status, upload_date)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', NOW())"
);

if ($stmt) {
    $analysisJSON = json_encode($analysisResult);
    $stmt->bind_param('sssdsss',
        $farmerName, $cropType, $filename, $fileSizeKB, $mimeType, $symptoms, $analysisJSON
    );
    $stmt->execute();
    $insertId = $conn->insert_id;
    $stmt->close();
}

$conn->close();

// === Respond with Success ===
sendJSON(true, '✅ Image uploaded and analyzed successfully!', [
    'filename'   => $filename,
    'crop_type'  => $cropType,
    'file_size'  => "$fileSizeKB KB",
    'analysis'   => $analysisResult,
    'record_id'  => $insertId ?? null,
]);

/**
 * Simulated disease analysis result.
 * In production, replace this with actual ML model API call.
 *
 * @param string $cropType
 * @return array Analysis results with disease probabilities
 */
function simulateDiseaseAnalysis($cropType) {
    // Crop-specific common diseases
    $cropDiseases = [
        'rice'    => ['Leaf Blast', 'Bacterial Blight', 'Brown Spot', 'Sheath Blight'],
        'wheat'   => ['Stem Rust', 'Leaf Rust', 'Powdery Mildew', 'Yellow Rust'],
        'maize'   => ['Northern Leaf Blight', 'Gray Leaf Spot', 'Common Rust'],
        'cotton'  => ['Leaf Curl Virus', 'Alternaria Blight', 'Bacterial Blight'],
        'tomato'  => ['Early Blight', 'Late Blight', 'Leaf Mold', 'Mosaic Virus'],
        'potato'  => ['Late Blight', 'Early Blight', 'Common Scab', 'Black Leg'],
        'default' => ['Powdery Mildew', 'Leaf Blight', 'Root Rot', 'Nutrient Deficiency'],
    ];

    $diseases = $cropDiseases[$cropType] ?? $cropDiseases['default'];
    $results  = [];
    $totalProb = 0;

    foreach ($diseases as $disease) {
        // Generate simulated (random-ish) probabilities
        $prob = rand(2, 25);
        $totalProb += $prob;
        $results[] = [
            'disease'    => $disease,
            'probability'=> $prob . '%',
            'status'     => $prob < 10 ? '✅ Healthy' : ($prob < 20 ? '⚠️ Monitor' : '🚨 Treat'),
        ];
    }

    // Add overall health score (100 minus total disease probability)
    $healthScore = max(0, 100 - $totalProb);

    return [
        'diseases'     => $results,
        'health_score' => $healthScore . '%',
        'recommendation' => $healthScore > 80
            ? 'Crop appears healthy. Continue regular monitoring.'
            : 'Some disease risk detected. Apply appropriate fungicide and monitor closely.',
    ];
}
