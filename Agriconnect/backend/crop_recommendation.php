<?php
/**
 * AgriConnect – Crop Recommendation Backend
 * ==========================================
 * Receives farmer input (soil, season, water availability)
 * and returns PHP-based crop recommendations.
 * Also stores the query to MySQL for analytics.
 *
 * POST Parameters:
 *   soil_type          : clay | loamy | sandy | black | red
 *   season             : kharif | rabi | zaid
 *   water_availability : high | medium | low
 *   region             : (optional) text
 *   land_size          : (optional) float in acres
 */

require_once 'db_connect.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(false, 'Invalid request method. Use POST.');
}

// === Collect & Sanitize Input ===
$soil   = sanitize($conn, $_POST['soil_type']          ?? '');
$season = sanitize($conn, $_POST['season']             ?? '');
$water  = sanitize($conn, $_POST['water_availability'] ?? '');
$region = sanitize($conn, $_POST['region']             ?? 'General');
$land   = floatval($_POST['land_size']                 ?? 1);

// === Validate Required Fields ===
if (empty($soil) || empty($season) || empty($water)) {
    sendJSON(false, 'Please fill in all required fields: Soil Type, Season, and Water Availability.');
}

// =====================================================
// CROP DATABASE
// Structure: [soil_type][season][water_level] => array of recommended crops
// =====================================================
$cropDB = [
    'clay' => [
        'kharif' => [
            'high'   => ['Rice (Paddy)', 'Sugarcane', 'Jute', 'Cotton', 'Arhar/Toor Dal'],
            'medium' => ['Maize', 'Sorghum (Jowar)', 'Groundnut', 'Green Gram'],
            'low'    => ['Pearl Millet (Bajra)', 'Finger Millet (Ragi)', 'Sorghum'],
        ],
        'rabi' => [
            'high'   => ['Wheat', 'Mustard', 'Gram (Chickpea)', 'Sugarcane'],
            'medium' => ['Barley', 'Linseed', 'Sunflower', 'Peas'],
            'low'    => ['Gram', 'Peas', 'Lentils', 'Barley'],
        ],
        'zaid' => [
            'high'   => ['Cucumber', 'Watermelon', 'Muskmelon', 'Bottle Gourd'],
            'medium' => ['Moong Dal', 'Sesame', 'Cowpea'],
            'low'    => ['Sesame', 'Cowpea', 'Cluster Bean'],
        ],
    ],
    'loamy' => [
        'kharif' => [
            'high'   => ['Rice', 'Maize', 'Groundnut', 'Soybean', 'Sunflower'],
            'medium' => ['Maize', 'Bajra', 'Arhar Dal', 'Sesame', 'Cotton'],
            'low'    => ['Bajra', 'Finger Millet', 'Cowpea', 'Cluster Bean'],
        ],
        'rabi' => [
            'high'   => ['Wheat', 'Potato', 'Sugarcane', 'Onion', 'Mustard'],
            'medium' => ['Wheat', 'Mustard', 'Gram', 'Peas', 'Carrot'],
            'low'    => ['Gram', 'Barley', 'Lentils', 'Peas'],
        ],
        'zaid' => [
            'high'   => ['Watermelon', 'Muskmelon', 'Vegetables', 'Groundnut'],
            'medium' => ['Moong Dal', 'Lentils', 'Sesame', 'Cowpea'],
            'low'    => ['Moong Dal', 'Sesame', 'Cowpea'],
        ],
    ],
    'sandy' => [
        'kharif' => [
            'high'   => ['Groundnut', 'Bajra', 'Maize', 'Watermelon'],
            'medium' => ['Bajra', 'Guar (Cluster Bean)', 'Cowpea', 'Sesame'],
            'low'    => ['Bajra', 'Cluster Bean', 'Moth Bean', 'Sesame'],
        ],
        'rabi' => [
            'high'   => ['Mustard', 'Barley', 'Gram', 'Sunflower'],
            'medium' => ['Mustard', 'Gram', 'Barley'],
            'low'    => ['Gram', 'Lentils', 'Barley'],
        ],
        'zaid' => [
            'high'   => ['Cucumber', 'Bottle Gourd', 'Watermelon', 'Ridge Gourd'],
            'medium' => ['Moong Dal', 'Cowpea'],
            'low'    => ['Sesame', 'Cowpea', 'Cluster Bean'],
        ],
    ],
    'black' => [
        'kharif' => [
            'high'   => ['Cotton', 'Soybean', 'Rice', 'Sugarcane', 'Banana'],
            'medium' => ['Sorghum', 'Pigeonpea (Arhar)', 'Sunflower', 'Cotton'],
            'low'    => ['Sorghum', 'Sunflower', 'Sesame', 'Cowpea'],
        ],
        'rabi' => [
            'high'   => ['Wheat', 'Chickpea', 'Safflower', 'Mustard'],
            'medium' => ['Wheat', 'Chickpea', 'Linseed'],
            'low'    => ['Lentils', 'Gram', 'Safflower'],
        ],
        'zaid' => [
            'high'   => ['Vegetables', 'Sunflower', 'Watermelon'],
            'medium' => ['Moong Dal', 'Sesame', 'Cowpea'],
            'low'    => ['Sesame', 'Cowpea'],
        ],
    ],
    'red' => [
        'kharif' => [
            'high'   => ['Groundnut', 'Cotton', 'Finger Millet (Ragi)', 'Maize'],
            'medium' => ['Ragi', 'Maize', 'Pearl Millet', 'Groundnut'],
            'low'    => ['Ragi', 'Pearl Millet', 'Horsegram', 'Cowpea'],
        ],
        'rabi' => [
            'high'   => ['Wheat', 'Gram', 'Mustard', 'Sunflower'],
            'medium' => ['Lentils', 'Mustard', 'Gram'],
            'low'    => ['Gram', 'Peas', 'Horsegram'],
        ],
        'zaid' => [
            'high'   => ['Vegetables', 'Melons', 'Cucumber'],
            'medium' => ['Moong Dal', 'Cowpea'],
            'low'    => ['Sesame', 'Cowpea', 'Horsegram'],
        ],
    ],
];

// === Get Crop Recommendations ===
$recommendations = [];
if (isset($cropDB[$soil][$season][$water])) {
    $recommendations = $cropDB[$soil][$season][$water];
} else {
    // Fallback defaults for unknown combinations
    $recommendations = ['Maize', 'Sorghum', 'Pearl Millet', 'Cowpea'];
}

// === Farming Tips Based on Season ===
$seasonTips = [
    'kharif' => 'Kharif crops depend on monsoon rainfall. Ensure proper field preparation before June. Monitor waterlogging in low-lying areas.',
    'rabi'   => 'Rabi crops need irrigated conditions. Sow after Kharif harvest. Protect from frost in December-January.',
    'zaid'   => 'Zaid crops thrive in summer. Provide shade nets if temperature exceeds 40°C. Increase irrigation frequency.',
];

$tip = $seasonTips[$season] ?? 'Follow local agricultural department guidelines for best practices.';

// === Save Recommendation Query to Database ===
// This helps AgriConnect analyze which crops are most commonly requested
$stmt = $conn->prepare(
    "INSERT INTO crops (soil_type, season, water_availability, recommended_crops, region, land_size, created_at)
     VALUES (?, ?, ?, ?, ?, ?, NOW())"
);

if ($stmt) {
    $cropsStr = implode(', ', $recommendations);
    $stmt->bind_param('sssssd', $soil, $season, $water, $cropsStr, $region, $land);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

// === Return JSON Response ===
sendJSON(true, 'Crop recommendations generated successfully!', [
    'crops'      => $recommendations,
    'soil'       => $soil,
    'season'     => $season,
    'water'      => $water,
    'region'     => $region,
    'land_size'  => $land,
    'tip'        => $tip,
    'count'      => count($recommendations),
]);
