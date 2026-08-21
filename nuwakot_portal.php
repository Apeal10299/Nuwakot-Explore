<?php
// Initialize SQLite Database Connection
try {
    $db = new PDO('sqlite:' . __DIR__ . '/nuwakot_portal.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Guides Table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS guides (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        area TEXT NOT NULL,
        specialty TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Handle Guide Registration Form
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register_guide'])) {
    $name = trim(htmlspecialchars($_POST['guide_name']));
    $area = trim(htmlspecialchars($_POST['area']));
    $specialty = trim(htmlspecialchars($_POST['specialty']));

    if (!empty($name) && !empty($area) && !empty($specialty)) {
        $stmt = $db->prepare("INSERT INTO guides (name, area, specialty) VALUES (:name, :area, :specialty)");
        $stmt->execute([':name' => $name, ':area' => $area, ':specialty' => $specialty]);
        $message = "🎉 Guide <strong>$name</strong> successfully registered and stored in the database!";
    }
}

// Fetch All Registered Guides from DB
$guides_query = $db->query("SELECT * FROM guides ORDER BY id DESC");
$registered_guides = $guides_query->fetchAll(PDO::FETCH_ASSOC);

// Simulated Weather Report Generator
$weather_data = null;
if (isset($_GET['get_weather'])) {
    $time_slot = $_GET['weather_time'] ?? 'morning';
    $location = $_GET['weather_location'] ?? 'Nuwakot Fort';
    
    $forecasts = [
        'morning'   => ['temp' => '18°C', 'condition' => 'Partly Cloudy', 'icon' => '⛅', 'humidity' => '78%', 'wind' => '8 km/h'],
        'afternoon' => ['temp' => '25°C', 'condition' => 'Sunny & Clear', 'icon' => '☀️', 'humidity' => '55%', 'wind' => '12 km/h'],
        'evening'   => ['temp' => '20°C', 'condition' => 'Breezy Dusk', 'icon' => '🌤️', 'humidity' => '65%', 'wind' => '10 km/h'],
        'night'     => ['temp' => '15°C', 'condition' => 'Clear Night', 'icon' => '🌙', 'humidity' => '82%', 'wind' => '5 km/h'],
    ];
    $weather_data = $forecasts[$time_slot] ?? $forecasts['morning'];
    $weather_data['location'] = $location;
    $weather_data['time'] = ucfirst($time_slot);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>GreenGrowth Nuwakot | Eco-Tourism Portal</title>
    <!-- CSS Frameworks & Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #047857;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text-main); line-height: 1.5; }

        /* Mobile-First Hero Section */
        .hero {
            background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.85)), 
                        url('https://images.unsplash.com/photo-1544735716-392fe2489ffa?auto=format&fit=crop&w=1200&q=80') center/cover;
            color: white;
            padding: 40px 16px;
            text-align: center;
        }
        .hero h1 { font-size: clamp(1.8rem, 5vw, 2.8rem); font-weight: 700; margin-bottom: 8px; }
        .hero p { font-size: clamp(0.95rem, 3vw, 1.15rem); color: #cbd5e1; max-width: 600px; margin: 0 auto; }

        /* Responsive Layout Container */
        .container { max-width: 1200px; margin: 0 auto; padding: 16px; }

        /* Flex & Grid Systems */
        .grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 16px; }
        @media (min-width: 768px) {
            .grid-2 { grid-template-columns: repeat(2, 1fr); }
            .grid-form { grid-template-columns: repeat(2, 1fr); }
            .container { padding: 24px; }
        }

        /* Card Component */
        .card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
        .card-header i { font-size: 1.25rem; color: var(--primary); }
        .card h2 { font-size: 1.2rem; font-weight: 700; }

        /* Fluid Map Container */
        .map-wrapper { width: 100%; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }
        #map { height: 300px; width: 100%; }
        @media (min-width: 768px) { #map { height: 400px; } }

        /* Touch-Friendly Form Elements */
        .form-group { margin-bottom: 14px; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 6px; }
        input, select {
            width: 100%; min-height: 44px; padding: 10px 12px; border-radius: 8px;
            border: 1px solid #cbd5e1; font-size: 0.95rem; background: #f8fafc;
        }
        input:focus, select:focus { border-color: var(--primary); outline: none; background: #fff; }

        .btn {
            width: 100%; min-height: 46px; background: var(--primary); color: white;
            border: none; border-radius: 8px; font-weight: 600; font-size: 0.95rem;
            cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;
        }
        .btn:hover { background: var(--primary-dark); }

        /* Dynamic Guide List Cards */
        .search-box { position: relative; margin-bottom: 12px; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-box input { padding-left: 36px; }

        .guide-list { display: flex; flex-direction: column; gap: 10px; max-height: 280px; overflow-y: auto; }
        .guide-card {
            background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px;
            display: flex; align-items: center; gap: 12px;
        }
        .avatar {
            width: 38px; height: 38px; border-radius: 50%; background: #dcfce7;
            color: var(--primary-dark); display: flex; align-items: center; justify-content: center;
            font-weight: 700; flex-shrink: 0;
        }

        /* Weather UI Widget */
        .weather-display {
            background: #047857; color: white; border-radius: 10px; padding: 16px;
            margin-top: 16px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; text-align: center;
        }
        .weather-main { grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 12px; }
        .temp { font-size: 2rem; font-weight: 700; }
        .metric { background: rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 6px; }

        /* Alert Box */
        .alert { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="hero">
    <h1>greengrowth.life</h1>
    <p>Nuwakot (नुवाकोट) Eco-Tourism, Climate Action & Guide Directory</p>
</div>

<div class="container">
    
    <?php if ($message): ?>
        <div class="alert"><i class="fa-solid fa-circle-check"></i> <?= $message ?></div>
    <?php endif; ?>

    <!-- Section 1: Responsive Map -->
    <div class="card" style="margin-bottom: 16px;">
        <div class="card-header">
            <i class="fa-solid fa-map-location-dot"></i>
            <h2>नुवाकोट (Nuwakot) Interactive Map</h2>
        </div>
        <div class="map-wrapper">
            <div id="map"></div>
        </div>
    </div>

    <!-- Section 2: Guide Registration & Guide Search (2-Column Desktop Grid) -->
    <div class="grid grid-2">
        <!-- Registration Form -->
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-user-plus"></i>
                <h2>Register Local Guide</h2>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="guide_name" required placeholder="e.g. Pasang Tamang">
                </div>
                
                <div class="form-group">
                    <label>Destination / Area</label>
                    <select name="area" required>
                        <option value="Nuwakot Seven Story Fort">Nuwakot Fort (साततले दरबार)</option>
                        <option value="Myagang Agro Farm">Myagang (म्यागङ) Orange Farm</option>
                        <option value="Kakani Ridge Trail">Kakani Great Himalayan Trail</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Specialty</label>
                    <select name="specialty" required>
                        <option value="Agro Tourism & Permaculture">Agro Tourism & Permaculture</option>
                        <option value="Low-Carbon Trekking">Low-Carbon Trekking</option>
                        <option value="Indigenous Knowledge & Homestay">Indigenous Knowledge & Homestay</option>
                    </select>
                </div>

                <button type="submit" name="register_guide" class="btn">
                    <i class="fa-solid fa-database"></i> Save to Database
                </button>
            </form>
        </div>

        <!-- Dynamic DB Search -->
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-magnifying-glass"></i>
                <h2>Database Guides Search</h2>
            </div>
            <div class="search-box">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="searchInput" onkeyup="filterGuides()" placeholder="Search stored guides...">
            </div>
            
            <div class="guide-list" id="guideList">
                <?php if (!empty($registered_guides)): ?>
                    <?php foreach ($registered_guides as $guide): ?>
                        <div class="guide-card">
                            <div class="avatar"><?= strtoupper(substr($guide['name'], 0, 2)) ?></div>
                            <div style="overflow: hidden;">
                                <strong style="font-size:0.95rem;"><?= htmlspecialchars($guide['name']) ?></strong> 
                                <small style="color:var(--primary-dark); font-weight:bold;">• <?= htmlspecialchars($guide['area']) ?></small>
                                <p style="font-size:0.8rem; color:var(--text-muted); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($guide['specialty']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">No guides found in the database. Use the form to add one.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Section 3: Weather Report by Choose Time -->
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-cloud-sun"></i>
            <h2>Nuwakot Weather Report By Time</h2>
        </div>
        
        <form method="GET" action="">
            <div class="grid grid-form" style="margin-bottom:0;">
                <div class="form-group">
                    <label>Select Location</label>
                    <select name="weather_location">
                        <option value="Nuwakot Palace Fort">Nuwakot Palace Fort</option>
                        <option value="Myagang Farm Valley">Myagang Farm Valley</option>
                        <option value="Trishuli River Basin">Trishuli River Basin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Choose Time</label>
                    <select name="weather_time">
                        <option value="morning">Morning (6:00 AM - 12:00 PM)</option>
                        <option value="afternoon">Afternoon (12:00 PM - 5:00 PM)</option>
                        <option value="evening">Evening (5:00 PM - 8:00 PM)</option>
                        <option value="night">Night (8:00 PM Onwards)</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="get_weather" class="btn" style="background:#0f172a;">
                <i class="fa-solid fa-temperature-half"></i> Check Forecast
            </button>
        </form>

        <?php if ($weather_data): ?>
            <div class="weather-display">
                <div class="weather-main">
                    <div style="text-align:left;">
                        <h3 style="font-size:1.1rem; font-weight:600;"><?= $weather_data['location'] ?></h3>
                        <small style="opacity:0.8;"><?= $weather_data['time'] ?> Forecast</small>
                    </div>
                    <div>
                        <span class="temp"><?= $weather_data['temp'] ?></span>
                        <div style="font-size:0.9rem;"><?= $weather_data['icon'] ?> <?= $weather_data['condition'] ?></div>
                    </div>
                </div>
                <div class="metric">
                    <small style="opacity:0.8;">Humidity</small>
                    <div style="font-weight:bold;"><?= $weather_data['humidity'] ?></div>
                </div>
                <div class="metric">
                    <small style="opacity:0.8;">Wind Speed</small>
                    <div style="font-weight:bold;"><?= $weather_data['wind'] ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Leaflet JS Map Script -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Initialize Map with Touch Dragging Enabled
    var map = L.map('map', { tap: true, touchZoom: true }).setView([27.9142, 85.1652], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Map Locations
    L.marker([27.9142, 85.1652]).addTo(map).bindPopup('<b>📍 Nuwakot Historic Fort</b>');
    L.marker([27.9500, 85.1000]).addTo(map).bindPopup('<b>🍊 Myagang Orange Farm</b>');
    L.marker([27.8086, 85.2536]).addTo(map).bindPopup('<b>🚴 Kakani Trail Base</b>');

    // Filter Guides in Real-Time
    function filterGuides() {
        var input = document.getElementById('searchInput').value.toLowerCase();
        var cards = document.getElementsByClassName('guide-card');
        for (var i = 0; i < cards.length; i++) {
            var content = cards[i].innerText.toLowerCase();
            cards[i].style.display = content.includes(input) ? 'flex' : 'none';
        }
    }
</script>
</body>
</html>