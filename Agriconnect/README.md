# 🌾 AgriConnect – Farmer Friendly Crop & Resource Management System

## Overview
AgriConnect is a full-stack web application built to empower Indian farmers with:
- 🌱 Smart Crop Recommendations (soil + season + water)
- 🌦️ Real-time Weather Data (via Open-Meteo API – no API key needed!)
- 📅 Crop Calendar with sowing/harvesting schedules
- 💧 Resource Management (water, fertilizer, labour tracking)
- 🛒 Agricultural Marketplace (seeds, fertilizers, pesticides)
- 🔬 Crop Disease Detection (image upload)
- 🌐 Multilingual Support (English, Kannada, Hindi)

---

## Technology Stack
| Layer     | Technology         |
|-----------|--------------------|
| Frontend  | HTML5, CSS3, JavaScript (ES6+) |
| Backend   | PHP 8.x            |
| Database  | MySQL 8.x          |
| Icons     | Font Awesome 6     |
| Fonts     | Google Fonts (Playfair Display + DM Sans) |
| Weather   | Open-Meteo API (free, no key required) |

---

## Project Structure
```
Agriconnect/
├── index.html          ← Home page
├── about.html          ← About page
├── crop.html           ← Crop Recommendation
├── weather.html        ← Weather Updates
├── calendar.html       ← Crop Calendar
├── resource.html       ← Resource Management
├── purchase.html       ← Purchase Products
├── crop-health.html    ← Crop Health Detection
├── language.html       ← Language Selection
│
├── css/
│   └── style.css       ← Main stylesheet (green ag theme)
│
├── js/
│   └── script.js       ← All JavaScript logic
│
├── images/             ← Place your images here
│
├── backend/
│   ├── config.php              ← DB config & app settings
│   ├── db_connect.php          ← MySQL connection & helpers
│   ├── crop_recommendation.php ← Crop logic + DB save
│   ├── resource_management.php ← Resource CRUD
│   ├── purchase_product.php    ← Order processing
│   └── upload_crop_image.php   ← Image upload & analysis
│
├── database/
│   └── agriconnect.sql         ← Full DB schema + sample data
│
└── uploads/
    └── crop_images/            ← Created automatically by PHP
```

---

## Setup Instructions

### 1. Requirements
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server (XAMPP recommended for local)
- Web browser (Chrome, Firefox, Edge)

### 2. Installation Steps

#### Step 1: Copy files to web server
```bash
# For XAMPP on Windows:
Copy the Agriconnect/ folder to:  C:\xampp\htdocs\

# For XAMPP on Mac:
Copy to: /Applications/XAMPP/htdocs/

# For Linux (Apache):
Copy to: /var/www/html/
```

#### Step 2: Create the database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click **Import** → Choose file → Select `database/agriconnect.sql`
3. Click **Go** — this creates all tables and sample data

OR via MySQL CLI:
```bash
mysql -u root -p < database/agriconnect.sql
```

#### Step 3: Configure database connection
Open `backend/config.php` and update:
```php
define('DB_HOST', 'localhost');     // Your MySQL host
define('DB_USER', 'root');          // Your MySQL username
define('DB_PASS', '');              // Your MySQL password
define('DB_NAME', 'agriconnect');   // Database name
```

#### Step 4: Launch the app
Open your browser and go to:
```
http://localhost/Agriconnect/
```

---

## Features Detail

### 🌱 Crop Recommendation
- Input: Soil type, season, water availability, region
- PHP backend maps inputs to crop database
- Returns 4-5 recommended crops with farming tips
- Data saved to MySQL `crops` table for analytics

### 🌦️ Weather Page
- Uses Open-Meteo API (completely free, no registration)
- Search any city — auto-geocodes location
- Shows temperature, humidity, rainfall, wind speed
- Provides farming advisory based on current conditions

### 📅 Crop Calendar
- Filter by crop (Rice, Wheat, Cotton, Maize, Sugarcane)
- Color-coded phases: Sowing, Fertilizing, Irrigation, Harvesting
- Month-by-month schedule with detailed instructions

### 💧 Resource Management
- Form saves data to MySQL `resources` table
- View/track water, fertilizer, labour records
- Summary stats (total water, cost, labour)

### 🛒 Purchase Page
- Filter by category: Seeds, Fertilizers, Pesticides, Tools
- "Add to Cart" auto-fills the order form
- Order saved to MySQL `orders` table with unique order ID

### 🔬 Crop Health
- Drag-and-drop image upload with preview
- PHP validates file type and size
- Saves to `uploads/crop_images/` + records in MySQL
- Returns simulated disease analysis (connect ML API in production)

### 🌐 Language Support
- 3 languages: English, Kannada (ಕನ್ನಡ), Hindi (हिंदी)
- Saved to localStorage (persists across pages)
- Key UI elements translate on selection

---

## Database Tables

| Table        | Purpose                                    |
|--------------|--------------------------------------------|
| `users`      | Farmer accounts and profiles               |
| `crops`      | Crop recommendation queries and results    |
| `resources`  | Water, fertilizer, labour usage records    |
| `orders`     | Product purchase orders                    |
| `crop_images`| Uploaded crop health images and analysis   |

---

## API Endpoints (PHP Backend)

| Endpoint                        | Method | Purpose                          |
|----------------------------------|--------|----------------------------------|
| `backend/crop_recommendation.php`| POST   | Get crop suggestions             |
| `backend/resource_management.php`| POST   | Save resource entry              |
| `backend/resource_management.php`| GET    | Fetch resource records           |
| `backend/purchase_product.php`   | POST   | Place product order              |
| `backend/upload_crop_image.php`  | POST   | Upload crop image for analysis   |

---

## Customization

### Adding More Crops
Edit the `$cropDB` array in `backend/crop_recommendation.php` to add new soil/season/water combinations.

### Connecting Real ML for Disease Detection
In `upload_crop_image.php`, replace `simulateDiseaseAnalysis()` with:
```php
// Example: Call a Flask ML API
$response = file_get_contents('http://your-ml-api.com/predict?image=' . $filename);
$result   = json_decode($response, true);
```

### Adding More Languages
In `js/script.js`, add a new entry to the `translations` object and add a language card in `language.html`.

---

## Credits
- Built with ❤️ for Indian farmers
- Weather data: [Open-Meteo](https://open-meteo.com/) (free API)
- Icons: [Font Awesome](https://fontawesome.com/)
- Fonts: [Google Fonts](https://fonts.google.com/)

---

## License
This project is for educational purposes. Free to use and modify.
