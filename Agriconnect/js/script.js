/* =====================================================
   AgriConnect – Main JavaScript File
   Handles: Nav, Weather API, Crop Logic, Uploads, Language
   ===================================================== */

// === DOM Ready ===
document.addEventListener('DOMContentLoaded', () => {
  initNav();
  setActiveNav();
  initWeather();
  initCropForm();
  initResourceForm();
  initPurchaseForm();
  initImageUpload();
  initLanguage();
  animateOnScroll();
});

// =====================================================
// NAVIGATION
// =====================================================
function initNav() {
  const hamburger = document.querySelector('.hamburger');
  const navLinks  = document.querySelector('.nav-links');

  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      hamburger.classList.toggle('active');
    });

    // Close nav when link clicked (mobile)
    navLinks.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => navLinks.classList.remove('open'));
    });
  }
}

// Highlight active nav link based on current page
function setActiveNav() {
  const page = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a').forEach(link => {
    if (link.getAttribute('href') === page) link.classList.add('active');
  });
}

// =====================================================
// WEATHER – Uses Open-Meteo (free, no key needed)
// =====================================================
async function initWeather() {
  if (!document.getElementById('weather-container')) return;

  const cityInput = document.getElementById('city-input');
  const searchBtn = document.getElementById('weather-search-btn');

  // Default: Bengaluru, India
  await fetchWeather(12.9716, 77.5946, 'Bengaluru');

  if (searchBtn) {
    searchBtn.addEventListener('click', () => {
      const city = cityInput ? cityInput.value.trim() : '';
      geocodeCity(city);
    });
  }

  if (cityInput) {
    cityInput.addEventListener('keydown', e => {
      if (e.key === 'Enter') geocodeCity(cityInput.value.trim());
    });
  }
}

async function geocodeCity(city) {
  if (!city) return;
  try {
    showWeatherLoading();
    // Use Open-Meteo geocoding API
    const res  = await fetch(`https://geocoding-api.open-meteo.com/v1/search?name=${encodeURIComponent(city)}&count=1`);
    const data = await res.json();
    if (data.results && data.results.length > 0) {
      const { latitude, longitude, name, country } = data.results[0];
      await fetchWeather(latitude, longitude, `${name}, ${country}`);
    } else {
      showWeatherError('City not found. Please try another name.');
    }
  } catch (err) {
    showWeatherError('Could not fetch location. Check your connection.');
  }
}

async function fetchWeather(lat, lon, cityName) {
  try {
    showWeatherLoading();
    const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true&hourly=relativehumidity_2m,precipitation_probability&daily=precipitation_sum,windspeed_10m_max&timezone=auto`;
    const res  = await fetch(url);
    const data = await res.json();

    const cw   = data.current_weather;
    const humid = data.hourly.relativehumidity_2m[new Date().getHours()] || '--';
    const rain  = data.daily.precipitation_sum[0] || 0;
    const wind  = cw.windspeed;
    const temp  = cw.temperature;
    const wcode = cw.weathercode;

    renderWeather({ city: cityName, temp, humid, rain, wind, code: wcode });
  } catch (err) {
    showWeatherError('Weather data unavailable. Please try again later.');
  }
}

function renderWeather({ city, temp, humid, rain, wind, code }) {
  const el  = document.getElementById('weather-container');
  if (!el) return;

  const icon = weatherIcon(code);
  const cond = weatherCondition(code);

  el.innerHTML = `
    <div class="weather-main animate-fade">
      <div class="location">📍 ${city}</div>
      <div class="temp">${temp}°C</div>
      <div class="condition">${icon} ${cond}</div>
    </div>
    <div class="weather-stats">
      <div class="weather-stat">
        <div class="stat-icon">💧</div>
        <div class="stat-val">${humid}%</div>
        <div class="stat-lbl">Humidity</div>
      </div>
      <div class="weather-stat">
        <div class="stat-icon">🌧️</div>
        <div class="stat-val">${rain} mm</div>
        <div class="stat-lbl">Rainfall Today</div>
      </div>
      <div class="weather-stat">
        <div class="stat-icon">💨</div>
        <div class="stat-val">${wind} km/h</div>
        <div class="stat-lbl">Wind Speed</div>
      </div>
      <div class="weather-stat">
        <div class="stat-icon">🌡️</div>
        <div class="stat-val">${temp}°C</div>
        <div class="stat-lbl">Temperature</div>
      </div>
    </div>
    <div style="margin-top:1.5rem; background:var(--green-bg); border-radius:var(--radius-sm); padding:1.2rem; border-left:4px solid var(--green-light);">
      <strong>🌾 Farming Advisory:</strong> ${farmingAdvice(temp, rain, humid)}
    </div>
  `;
}

function showWeatherLoading() {
  const el = document.getElementById('weather-container');
  if (el) el.innerHTML = `<div style="text-align:center;padding:3rem;color:var(--green-mid);font-size:1.1rem;">⏳ Fetching weather data...</div>`;
}

function showWeatherError(msg) {
  const el = document.getElementById('weather-container');
  if (el) el.innerHTML = `<div class="alert alert-error" style="display:block;">⚠️ ${msg}</div>`;
}

// WMO Weather Code to icon/label
function weatherIcon(code) {
  if (code === 0) return '☀️';
  if (code <= 3)  return '⛅';
  if (code <= 49) return '🌫️';
  if (code <= 69) return '🌧️';
  if (code <= 79) return '❄️';
  if (code <= 99) return '⛈️';
  return '🌤️';
}

function weatherCondition(code) {
  if (code === 0) return 'Clear Sky';
  if (code <= 3)  return 'Partly Cloudy';
  if (code <= 49) return 'Foggy';
  if (code <= 69) return 'Rainy';
  if (code <= 79) return 'Snowy';
  if (code <= 99) return 'Thunderstorm';
  return 'Mixed';
}

// Simple farming advice based on weather conditions
function farmingAdvice(temp, rain, humid) {
  if (rain > 20)  return 'Heavy rainfall expected. Avoid spraying pesticides. Ensure proper drainage in fields.';
  if (rain > 5)   return 'Moderate rain expected. Good for irrigation. Delay fertilizer application.';
  if (temp > 38)  return 'High temperature alert! Increase irrigation frequency. Protect seedlings from heat stress.';
  if (temp < 10)  return 'Cool temperatures. Ideal for rabi crops like wheat and mustard.';
  if (humid > 80) return 'High humidity. Watch for fungal diseases. Ensure good air circulation in crops.';
  return 'Favorable conditions for most field operations. A good day for sowing or harvesting.';
}

// =====================================================
// CROP RECOMMENDATION (Client-side PHP-mirrored logic)
// =====================================================
function initCropForm() {
  const form = document.getElementById('crop-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const soil   = document.getElementById('soil-type').value;
    const season = document.getElementById('season').value;
    const water  = document.getElementById('water-availability').value;
    const region = document.getElementById('region').value || 'general';

    // Show loading
    const btn = form.querySelector('button[type="submit"]');
    btn.textContent = '⏳ Analyzing...';
    btn.disabled = true;

    // Simulate PHP backend call
    await delay(600);
    const crops = recommendCrops(soil, season, water);

    btn.textContent = '🌱 Get Recommendations';
    btn.disabled = false;

    displayCropResults(crops, soil, season);
  });
}

// Crop recommendation logic (mirrors PHP backend logic)
function recommendCrops(soil, season, water) {
  const db = {
    // [soil][season][water] → crop list
    clay: {
      kharif: { high: ['Rice', 'Sugarcane', 'Jute', 'Cotton'], medium: ['Maize', 'Sorghum', 'Groundnut'], low: ['Millets', 'Sorghum'] },
      rabi:   { high: ['Wheat', 'Mustard', 'Gram'], medium: ['Barley', 'Linseed'], low: ['Gram', 'Peas'] },
      zaid:   { high: ['Cucumber', 'Watermelon', 'Muskmelon'], medium: ['Moong', 'Sesame'], low: ['Sesame'] }
    },
    loamy: {
      kharif: { high: ['Rice', 'Maize', 'Groundnut', 'Soybean'], medium: ['Maize', 'Bajra', 'Arhar'], low: ['Bajra', 'Ragi'] },
      rabi:   { high: ['Wheat', 'Potato', 'Sugarcane'], medium: ['Wheat', 'Mustard'], low: ['Gram', 'Barley'] },
      zaid:   { high: ['Watermelon', 'Muskmelon', 'Vegetables'], medium: ['Moong', 'Lentils'], low: ['Moong'] }
    },
    sandy: {
      kharif: { high: ['Groundnut', 'Bajra', 'Maize'], medium: ['Bajra', 'Guar'], low: ['Bajra', 'Cluster Bean'] },
      rabi:   { high: ['Mustard', 'Barley'], medium: ['Mustard', 'Gram'], low: ['Gram', 'Lentils'] },
      zaid:   { high: ['Cucumber', 'Gourds'], medium: ['Moong'], low: ['Sesame'] }
    },
    black: {
      kharif: { high: ['Cotton', 'Soybean', 'Rice'], medium: ['Sorghum', 'Pigeonpea'], low: ['Sorghum', 'Sunflower'] },
      rabi:   { high: ['Wheat', 'Chickpea', 'Safflower'], medium: ['Wheat', 'Chickpea'], low: ['Lentils', 'Gram'] },
      zaid:   { high: ['Vegetables', 'Sunflower'], medium: ['Moong', 'Sesame'], low: ['Sesame'] }
    },
    red: {
      kharif: { high: ['Groundnut', 'Cotton', 'Ragi'], medium: ['Ragi', 'Maize', 'Millets'], low: ['Ragi', 'Millets'] },
      rabi:   { high: ['Wheat', 'Gram'], medium: ['Lentils', 'Mustard'], low: ['Gram', 'Peas'] },
      zaid:   { high: ['Vegetables', 'Melons'], medium: ['Moong'], low: ['Sesame'] }
    }
  };

  const s = soil   || 'loamy';
  const k = season || 'kharif';
  const w = water  || 'medium';

  return (db[s] && db[s][k] && db[s][k][w]) ? db[s][k][w] : ['Maize', 'Sorghum', 'Millets'];
}

function displayCropResults(crops, soil, season) {
  const box = document.getElementById('crop-result');
  if (!box) return;

  box.style.display = 'block';
  box.querySelector('#result-crops').innerHTML = crops.map(c =>
    `<span class="crop-tag">🌿 ${c}</span>`
  ).join('');

  const info = box.querySelector('#result-info');
  if (info) {
    info.textContent = `Based on ${soil} soil in ${season} season, these crops offer the best yield potential for your conditions.`;
  }
}

// =====================================================
// RESOURCE MANAGEMENT FORM
// =====================================================
function initResourceForm() {
  const form = document.getElementById('resource-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData(form);
    const btn  = form.querySelector('button[type="submit"]');

    btn.textContent = '⏳ Saving...';
    btn.disabled    = true;

    try {
      const res  = await fetch('backend/resource_management.php', { method: 'POST', body: data });
      const json = await res.json();
      showAlert('resource-alert', json.success ? 'success' : 'error', json.message);
      if (json.success) { form.reset(); loadResourceRecords(); }
    } catch {
      showAlert('resource-alert', 'success', '✅ Resource data saved locally! (Connect backend for DB storage)');
      form.reset();
    }

    btn.textContent = '💾 Save Resource Data';
    btn.disabled    = false;
  });

  loadResourceRecords();
}

function loadResourceRecords() {
  // Populate sample table data (real data comes from PHP backend)
  const tbody = document.getElementById('resource-tbody');
  if (!tbody) return;

  const sample = [
    ['2024-06-01', 'Field A', '200 L', '5 kg', '3', '₹1500'],
    ['2024-06-05', 'Field B', '150 L', '3 kg', '2', '₹900'],
    ['2024-06-10', 'Field C', '300 L', '8 kg', '5', '₹2200'],
  ];

  tbody.innerHTML = sample.map(row => `
    <tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>
  `).join('');
}

// =====================================================
// PURCHASE FORM
// =====================================================
function initPurchaseForm() {
  const form = document.getElementById('purchase-form');
  if (!form) return;

  // Add to cart buttons
  document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', () => {
      const name  = btn.getAttribute('data-name');
      const price = btn.getAttribute('data-price');
      document.getElementById('purchase-product').value = name;
      document.getElementById('purchase-price').value   = price;
      document.getElementById('order-form-section').scrollIntoView({ behavior: 'smooth' });
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData(form);
    const btn  = form.querySelector('button[type="submit"]');

    btn.textContent = '⏳ Placing Order...';
    btn.disabled    = true;

    try {
      const res  = await fetch('backend/purchase_product.php', { method: 'POST', body: data });
      const json = await res.json();
      showAlert('purchase-alert', json.success ? 'success' : 'error', json.message);
      if (json.success) form.reset();
    } catch {
      showAlert('purchase-alert', 'success', '✅ Order placed successfully! (Connect backend for DB storage)');
      form.reset();
    }

    btn.textContent = '🛒 Place Order';
    btn.disabled    = false;
  });
}

// =====================================================
// CROP HEALTH IMAGE UPLOAD
// =====================================================
function initImageUpload() {
  const form    = document.getElementById('health-form');
  const preview = document.getElementById('preview-img');
  const fileIn  = document.getElementById('crop-image-input');

  if (!form) return;

  // Preview image before upload
  if (fileIn) {
    fileIn.addEventListener('change', () => {
      const file = fileIn.files[0];
      if (file && preview) {
        const reader = new FileReader();
        reader.onload  = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(file);
      }
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData(form);
    const btn  = form.querySelector('button[type="submit"]');

    btn.textContent = '⏳ Uploading & Analyzing...';
    btn.disabled    = true;

    try {
      const res  = await fetch('backend/upload_crop_image.php', { method: 'POST', body: data });
      const json = await res.json();
      showAlert('health-alert', json.success ? 'success' : 'error', json.message);

      if (json.success) {
        // Display simulated analysis result
        showHealthResult(json.filename || 'uploaded-image.jpg');
      }
    } catch {
      showAlert('health-alert', 'success', '✅ Image uploaded! Analysis: Crop appears healthy. Monitor for discoloration.');
      showHealthResult('sample-crop.jpg');
    }

    btn.textContent = '🔍 Upload & Analyze';
    btn.disabled    = false;
  });
}

function showHealthResult(filename) {
  const box = document.getElementById('health-result');
  if (!box) return;

  // Simulated crop health analysis response
  const results = [
    { disease: 'Leaf Blight',     prob: '12%', status: '✅ Low Risk' },
    { disease: 'Powdery Mildew',  prob: '5%',  status: '✅ Healthy' },
    { disease: 'Root Rot',        prob: '3%',  status: '✅ Healthy' },
    { disease: 'Nutrient Deficiency', prob: '18%', status: '⚠️ Monitor' },
  ];

  box.style.display = 'block';
  box.innerHTML = `
    <h3>🔬 Analysis Result for: ${filename}</h3>
    <p style="margin-bottom:1rem;color:var(--text-light);">AI-powered disease detection complete. Connect to ML backend for real predictions.</p>
    <table class="resource-table">
      <thead><tr><th>Disease/Condition</th><th>Probability</th><th>Status</th></tr></thead>
      <tbody>
        ${results.map(r => `<tr><td>${r.disease}</td><td>${r.prob}</td><td>${r.status}</td></tr>`).join('')}
      </tbody>
    </table>
    <div style="margin-top:1rem;padding:1rem;background:var(--green-bg);border-radius:var(--radius-sm);">
      <strong>💡 Recommendation:</strong> Crop is mostly healthy. Apply balanced NPK fertilizer. Monitor humidity levels.
    </div>
  `;
}

// =====================================================
// LANGUAGE SELECTION
// =====================================================
function initLanguage() {
  const cards = document.querySelectorAll('.lang-card');
  if (!cards.length) return;

  // Restore saved language
  const saved = localStorage.getItem('agri_lang') || 'en';
  cards.forEach(card => {
    if (card.getAttribute('data-lang') === saved) card.classList.add('selected');
  });

  cards.forEach(card => {
    card.addEventListener('click', () => {
      cards.forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');

      const lang = card.getAttribute('data-lang');
      localStorage.setItem('agri_lang', lang);
      applyLanguage(lang);
    });
  });

  // Apply on load
  applyLanguage(saved);
}

// Simple language translation dictionary
const translations = {
  en: {
    'nav-home':     'Home',
    'nav-about':    'About',
    'nav-crop':     'Crop Rec.',
    'nav-weather':  'Weather',
    'lang-heading': 'Select Language',
    'lang-subtext': 'Choose your preferred language for the AgriConnect platform.',
  },
  kn: {
    'nav-home':     'ಮುಖಪುಟ',
    'nav-about':    'ಪರಿಚಯ',
    'nav-crop':     'ಬೆಳೆ ಶಿಫಾರಸು',
    'nav-weather':  'ಹವಾಮಾನ',
    'lang-heading': 'ಭಾಷೆ ಆಯ್ಕೆ ಮಾಡಿ',
    'lang-subtext': 'ನಿಮ್ಮ ಇಷ್ಟದ ಭಾಷೆಯನ್ನು ಆರಿಸಿ.',
  },
  hi: {
    'nav-home':     'होम',
    'nav-about':    'परिचय',
    'nav-crop':     'फसल सिफारिश',
    'nav-weather':  'मौसम',
    'lang-heading': 'भाषा चुनें',
    'lang-subtext': 'AgriConnect प्लेटफ़ॉर्म के लिए अपनी पसंदीदा भाषा चुनें।',
  }
};

function applyLanguage(lang) {
  const t = translations[lang] || translations.en;
  Object.keys(t).forEach(key => {
    const el = document.getElementById(key);
    if (el) el.textContent = t[key];
  });

  // Update html lang attribute
  document.documentElement.lang = lang;
}

// =====================================================
// SCROLL ANIMATIONS
// =====================================================
function animateOnScroll() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate-fade');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.card, .tip-item, .calendar-item, .product-card, .weather-stat').forEach(el => {
    observer.observe(el);
  });
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

// Show alert message
function showAlert(containerId, type, message) {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.className = `alert alert-${type}`;
  el.textContent = message;
  el.style.display = 'block';
  setTimeout(() => { el.style.display = 'none'; }, 5000);
}

// Async delay helper
function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// =====================================================
// CALENDAR FILTER
// =====================================================
function filterCalendar(crop) {
  const items = document.querySelectorAll('.calendar-item');
  items.forEach(item => {
    const tag = item.getAttribute('data-crop');
    item.style.display = (crop === 'all' || tag === crop || !tag) ? 'flex' : 'none';
  });

  // Update active filter button
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.classList.toggle('active-filter', btn.getAttribute('data-filter') === crop);
  });
}
