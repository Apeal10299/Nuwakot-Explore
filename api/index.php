<?php
// ============================================================
// NUWAKOT ECO TOURISM
// ONE-FILE PHP + SQLITE WEBSITE
// ============================================================

// ============================================================
session_start();

// ============================================================
// DATABASE CONNECTION (Cloud PostgreSQL via Supabase)
// ============================================================

function clean($value)
{
    return htmlspecialchars(
        trim((string)$value),
        ENT_QUOTES,
        "UTF-8"
    );
}
try {
    $host = "aws-0-ap-southeast-2.pooler.supabase.com";
    $port = "5432";
    $dbName = "postgres";
    $user = "postgres.uekhkoxsjejeqmgquild";
    $pass = "@Peal9742912541"; // Put your real database password here

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";
    
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

} catch (PDOException $e) {
    die(
        "<h2>Database Error</h2>
        <p>" . htmlspecialchars($e->getMessage()) . "</p>"
    );
}

// ============================================================
// CREATE USERS TABLE
// ============================================================

$db->exec("
CREATE TABLE IF NOT EXISTS users (

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    name TEXT NOT NULL,

    email TEXT NOT NULL UNIQUE,

    password_hash TEXT NOT NULL,

    role TEXT DEFAULT 'tourist',

    points INTEGER DEFAULT 100,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
");


// ============================================================
// CREATE GUIDES TABLE FIRST
// ============================================================

$db->exec("
CREATE TABLE IF NOT EXISTS guides (

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    name TEXT NOT NULL,

    location TEXT,

    languages TEXT,

    experience INTEGER DEFAULT 1,

    specialty TEXT,

    points INTEGER DEFAULT 0,

    rating REAL DEFAULT 0
)
");


// ============================================================
// CREATE TOURISTS TABLE
// ============================================================

$db->exec("
CREATE TABLE IF NOT EXISTS tourists (

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    name TEXT NOT NULL,

    email TEXT NOT NULL,

    phone TEXT,

    country TEXT,

    visit_date TEXT,

    interests TEXT,

    guide_id INTEGER,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (guide_id)
    REFERENCES guides(id)
    ON DELETE SET NULL
)
");


// ============================================================
// DATABASE MIGRATION
// Add guide_id if an older database already exists
// ============================================================

try {

    $columns = $db
        ->query("PRAGMA table_info(tourists)")
        ->fetchAll(PDO::FETCH_ASSOC);

    $hasGuideId = false;

    foreach ($columns as $column) {

        if ($column["name"] === "guide_id") {

            $hasGuideId = true;

            break;
        }
    }

    if (!$hasGuideId) {

        $db->exec(
            "ALTER TABLE tourists
             ADD COLUMN guide_id INTEGER"
        );
    }

} catch (PDOException $e) {

    // Ignore migration error
}


// ============================================================
// FEEDBACK TABLE
// ============================================================

$db->exec("
CREATE TABLE IF NOT EXISTS feedback (

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    guide_id INTEGER NOT NULL,

    tourist_name TEXT NOT NULL,

    rating INTEGER NOT NULL,

    comment TEXT,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (guide_id)
    REFERENCES guides(id)
    ON DELETE CASCADE
)
");


// ============================================================
// TOUR HISTORY TABLE
// ============================================================

$db->exec("
CREATE TABLE IF NOT EXISTS tour_history (

    id INTEGER PRIMARY KEY AUTOINCREMENT,

    guide_id INTEGER NOT NULL,

    tourist_name TEXT NOT NULL,

    destination TEXT NOT NULL,

    tour_date TEXT NOT NULL,

    duration TEXT,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (guide_id)
    REFERENCES guides(id)
    ON DELETE CASCADE
)
");


// ============================================================
// ADD DEMO GUIDES
// ============================================================

$guideCount = $db
    ->query("SELECT COUNT(*) FROM guides")
    ->fetchColumn();


if ($guideCount == 0) {

    $stmt = $db->prepare("
        INSERT INTO guides
        (
            name,
            location,
            languages,
            experience,
            specialty,
            points,
            rating
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");


    $guides = [

        // BIDUR GUIDES
        [
            "Ram Thapa",
            "Bidur",
            "Nepali, English, Hindi",
            7,
            "Culture & Heritage",
            850,
            4.8
        ],

        [
            "Priya Sharma",
            "Bidur",
            "Nepali, English",
            6,
            "Local Culture & Crafts",
            750,
            4.7
        ],

        [
            "Gopal Sunuwar",
            "Bidur",
            "Nepali, English, Hindi",
            8,
            "Historical Tours",
            900,
            4.9
        ],

        // KAKANI GUIDES
        [
            "Sita Tamang",
            "Kakani",
            "Nepali, English",
            5,
            "Nature & Hiking",
            720,
            4.9
        ],

        [
            "Niran Rai",
            "Kakani",
            "Nepali, English, Japanese",
            4,
            "Mountain Trekking",
            680,
            4.8
        ],

        [
            "Chitra Poudel",
            "Kakani",
            "Nepali, English",
            6,
            "Bird Watching & Nature",
            800,
            4.7
        ],

        // NUWAKOT DURBAR GUIDES
        [
            "Bikash Lama",
            "Nuwakot Durbar",
            "Nepali, English, Hindi",
            9,
            "History & Architecture",
            1200,
            4.7
        ],

        [
            "Anita Gurung",
            "Nuwakot Durbar",
            "Nepali, English, Mandarin",
            7,
            "Palace Tours & Heritage",
            920,
            4.9
        ],

        [
            "Ravi Kumar",
            "Nuwakot Durbar",
            "Nepali, English, Hindi",
            5,
            "Photography & Tours",
            850,
            4.8
        ],

        // TARKESHWAR GUIDES
        [
            "Maya Gurung",
            "Tarkeshwar",
            "Nepali, English",
            4,
            "Village & Eco Tourism",
            640,
            4.6
        ],

        [
            "Deepak Thapa",
            "Tarkeshwar",
            "Nepali, English",
            5,
            "Traditional Village Life",
            700,
            4.8
        ],

        [
            "Sabita Magar",
            "Tarkeshwar",
            "Nepali, English",
            3,
            "Community Tourism",
            600,
            4.7
        ]

    ];


    foreach ($guides as $guide) {

        $stmt->execute($guide);
    }
}


// ============================================================
// ADD DEMO TOURISTS
// ============================================================

$touristCount = $db
    ->query("SELECT COUNT(*) FROM tourists")
    ->fetchColumn();


if ($touristCount == 0) {

    $stmt = $db->prepare("
        INSERT INTO tourists
        (
            name,
            email,
            phone,
            country,
            visit_date,
            interests,
            guide_id
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $tourists = [

        // RAM THAPA (Bidur) - Guide ID 1
        ["John Smith", "john.smith@email.com", "+1-555-0101", "USA", "2024-06-15", "", 1],
        ["Emma Wilson", "emma.wilson@email.com", "+44-7911-123456", "UK", "2024-06-20", "", 1],
        ["Maria Garcia", "maria.garcia@email.com", "+34-91-123-4567", "Spain", "2024-07-05", "", 1],
        ["Chen Wei", "chen.wei@email.com", "+86-10-1234-5678", "China", "2024-07-12", "", 1],

        // PRIYA SHARMA (Bidur) - Guide ID 2
        ["Sarah Johnson", "sarah.j@email.com", "+1-555-0202", "USA", "2024-06-18", "", 2],
        ["Anna Mueller", "anna.mueller@email.com", "+49-30-1234-567", "Germany", "2024-07-01", "", 2],
        ["Lisa Anderson", "lisa.anderson@email.com", "+61-2-1234-5678", "Australia", "2024-07-08", "", 2],
        ["Yuki Tanaka", "yuki.tanaka@email.com", "+81-3-1234-5678", "Japan", "2024-07-15", "", 2],

        // GOPAL SUNUWAR (Bidur) - Guide ID 3
        ["Michael Brown", "michael.b@email.com", "+1-555-0303", "USA", "2024-06-22", "", 3],
        ["David Kim", "david.kim@email.com", "+82-2-1234-5678", "South Korea", "2024-07-03", "", 3],
        ["Pierre Dubois", "pierre.dubois@email.com", "+33-1-1234-5678", "France", "2024-07-10", "", 3],
        ["Elena Rossi", "elena.rossi@email.com", "+39-6-1234-5678", "Italy", "2024-07-18", "", 3],

        // SITA TAMANG (Kakani) - Guide ID 4
        ["Robert Taylor", "robert.t@email.com", "+1-555-0404", "USA", "2024-06-25", "", 4],
        ["Sophie Martin", "sophie.martin@email.com", "+33-1-9876-5432", "France", "2024-07-02", "", 4],
        ["James O'Brien", "james.obrien@email.com", "+353-1-1234-567", "Ireland", "2024-07-09", "", 4],
        ["Karen White", "karen.white@email.com", "+1-555-0505", "Canada", "2024-07-16", "", 4],

        // NIRAN RAI (Kakani) - Guide ID 5
        ["Andrew Miller", "andrew.m@email.com", "+1-555-0606", "USA", "2024-06-28", "", 5],
        ["Laura Schmidt", "laura.schmidt@email.com", "+49-30-9876-543", "Germany", "2024-07-04", "", 5],
        ["Mark Thompson", "mark.thompson@email.com", "+44-20-1234-5678", "UK", "2024-07-11", "", 5],
        ["Natalie Young", "natalie.y@email.com", "+1-555-0707", "USA", "2024-07-19", "", 5],

        // CHITRA POUDEL (Kakani) - Guide ID 6
        ["Chris Davis", "chris.davis@email.com", "+1-555-0808", "USA", "2024-07-01", "", 6],
        ["Ingrid Bergman", "ingrid.bergman@email.com", "+46-8-1234-5678", "Sweden", "2024-07-06", "", 6],
        ["Henrik Nielsen", "henrik.n@email.com", "+45-33-1234-56", "Denmark", "2024-07-13", "", 6],
        ["Sofia Romano", "sofia.romano@email.com", "+39-2-1234-5678", "Italy", "2024-07-20", "", 6],

        // BIKASH LAMA (Nuwakot Durbar) - Guide ID 7
        ["Thomas Wilson", "thomas.w@email.com", "+1-555-0909", "USA", "2024-07-02", "", 7],
        ["Greta Andersen", "greta.a@email.com", "+45-40-1234-56", "Denmark", "2024-07-07", "", 7],
        ["Klaus Mueller", "klaus.mueller@email.com", "+49-89-1234-567", "Germany", "2024-07-14", "", 7],
        ["Victoria Martinez", "victoria.m@email.com", "+34-93-1234-567", "Spain", "2024-07-21", "", 7],

        // ANITA GURUNG (Nuwakot Durbar) - Guide ID 8
        ["Edward Brown", "edward.b@email.com", "+1-555-1010", "USA", "2024-07-03", "", 8],
        ["Fiona Campbell", "fiona.campbell@email.com", "+44-131-1234-567", "UK", "2024-07-08", "", 8],
        ["Giovanni Bianchi", "giovanni.b@email.com", "+39-10-1234-567", "Italy", "2024-07-15", "", 8],
        ["Michelle Green", "michelle.g@email.com", "+1-555-1111", "USA", "2024-07-22", "", 8],

        // RAVI KUMAR (Nuwakot Durbar) - Guide ID 9
        ["Frank Johnson", "frank.j@email.com", "+1-555-1212", "USA", "2024-07-04", "", 9],
        ["Helena Fischer", "helena.fischer@email.com", "+43-1-1234-5678", "Austria", "2024-07-09", "", 9],
        ["Marco Rossi", "marco.rossi@email.com", "+39-55-1234-567", "Italy", "2024-07-16", "", 9],
        ["Rachel Cohen", "rachel.cohen@email.com", "+972-3-1234-5678", "Israel", "2024-07-23", "", 9],

        // MAYA GURUNG (Tarkeshwar) - Guide ID 10
        ["George Harris", "george.h@email.com", "+1-555-1313", "USA", "2024-07-05", "", 10],
        ["Inge Aakjaer", "inge.aakjaer@email.com", "+45-50-1234-56", "Denmark", "2024-07-10", "", 10],
        ["Paolo Verdi", "paolo.verdi@email.com", "+39-6-1234-5670", "Italy", "2024-07-17", "", 10],
        ["Susan Adams", "susan.adams@email.com", "+1-555-1414", "USA", "2024-07-24", "", 10],

        // DEEPAK THAPA (Tarkeshwar) - Guide ID 11
        ["Henry Clark", "henry.clark@email.com", "+1-555-1515", "USA", "2024-07-06", "", 11],
        ["Joanna Kowalski", "joanna.k@email.com", "+48-22-1234-567", "Poland", "2024-07-11", "", 11],
        ["Luca Ferrari", "luca.ferrari@email.com", "+39-20-1234-567", "Italy", "2024-07-18", "", 11],
        ["Tina Roberts", "tina.roberts@email.com", "+1-555-1616", "USA", "2024-07-25", "", 11],

        // SABITA MAGAR (Tarkeshwar) - Guide ID 12
        ["Isaac Lewis", "isaac.lewis@email.com", "+1-555-1717", "USA", "2024-07-07", "", 12],
        ["Katja Schmidt", "katja.schmidt@email.com", "+49-30-1234-568", "Germany", "2024-07-12", "", 12],
        ["Matteo Ricci", "matteo.ricci@email.com", "+39-41-1234-567", "Italy", "2024-07-19", "", 12],
        ["Uma Patel", "uma.patel@email.com", "+91-98765-43210", "India", "2024-07-26", "", 12]

    ];

    foreach ($tourists as $tourist) {
        $stmt->execute($tourist);
    }
}


// ============================================================
// SESSION USER
// ============================================================

$currentUser = null;

if (!empty($_SESSION["user_id"])) {

    $userStmt = $db->prepare("
        SELECT *
        FROM users
        WHERE id = ?
    ");

    $userStmt->execute([
        $_SESSION["user_id"]
    ]);

    $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        unset($_SESSION["user_id"]);
    }
}


// ============================================================
// MESSAGE VARIABLES
// ============================================================

$message = "";

$messageType = "";


// ============================================================
// LOGIN / ACCOUNT CREATION
// ============================================================

if (isset($_POST["register_account"])) {

    $name = trim($_POST["account_name"] ?? "");
    $email = strtolower(trim($_POST["account_email"] ?? ""));
    $password = $_POST["account_password"] ?? "";
    $role = trim($_POST["account_role"] ?? "tourist");

    if ($name === "" || $email === "" || $password === "") {

        $message = "Please complete your account details.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif (strlen($password) < 6) {

        $message = "Your password must be at least 6 characters long.";
        $messageType = "error";

    } else {

        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {

            $message = "An account with that email already exists.";
            $messageType = "error";

        } else {

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO users
                (name, email, password_hash, role, points)
                VALUES (?, ?, ?, ?, 100)
            ");

            $stmt->execute([
                $name,
                $email,
                $passwordHash,
                $role
            ]);

            $_SESSION["user_id"] = (int)$db->lastInsertId();
            $_SESSION["user_name"] = $name;

            $message = "Account created successfully! You have been logged in and received 100 loyalty points.";
            $messageType = "success";

            $currentUser = [
                "id" => $_SESSION["user_id"],
                "name" => $name,
                "email" => $email,
                "role" => $role,
                "points" => 100
            ];
        }
    }
}

if (isset($_POST["login_user"])) {

    $email = strtolower(trim($_POST["login_email"] ?? ""));
    $password = $_POST["login_password"] ?? "";

    if ($email === "" || $password === "") {

        $message = "Please enter your email and password.";
        $messageType = "error";

    } else {

        $stmt = $db->prepare("
            SELECT *
            FROM users
            WHERE email = ?
        ");

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password_hash"])) {

            $_SESSION["user_id"] = (int)$user["id"];
            $_SESSION["user_name"] = $user["name"];
            $currentUser = $user;

            $message = "Welcome back, " . clean($user["name"]) . "!";
            $messageType = "success";

        } else {

            $message = "Invalid email or password.";
            $messageType = "error";
        }
    }
}

if (isset($_POST["logout_user"])) {

    session_unset();
    session_destroy();

    $currentUser = null;
    $message = "You have been logged out.";
    $messageType = "success";
}


// ============================================================
// TOURIST REGISTRATION
// ============================================================

if (isset($_POST["register_tourist"])) {

    if (!$currentUser) {

        $message = "Please log in to register as a tourist.";
        $messageType = "error";

    } else {

        $name = trim($currentUser["name"] ?? "");
        $email = strtolower(trim($currentUser["email"] ?? ""));
        $phone = trim($_POST["phone"] ?? "");
        $country = trim($_POST["country"] ?? "");
        $visitDate = trim($_POST["visit_date"] ?? "");
        $interests = "";
        $guideId = intval($_POST["guide_id"] ?? 0);

        if ($name === "" || $email === "") {

            $message = "Please enter your name and email.";
            $messageType = "error";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";
            $messageType = "error";

        } else {

            if ($guideId > 0) {

                $guideCheck = $db->prepare("
                    SELECT id
                    FROM guides
                    WHERE id = ?
                ");

                $guideCheck->execute([$guideId]);

                if (!$guideCheck->fetch()) {
                    $guideId = null;
                }
            } else {
                $guideId = null;
            }

            $stmt = $db->prepare("
                INSERT INTO tourists
                (
                    name,
                    email,
                    phone,
                    country,
                    visit_date,
                    interests,
                    guide_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $email,
                $phone,
                $country,
                $visitDate,
                $interests,
                $guideId
            ]);

            if ($guideId !== null) {

                $stmt = $db->prepare("
                    UPDATE guides
                    SET points = points + 10
                    WHERE id = ?
                ");

                $stmt->execute([$guideId]);
            }

            $message = "Registration successful! 🎉 Your selected guide has been saved.";
            $messageType = "success";
        }
    }
}


// ============================================================
// FEEDBACK
// ============================================================

if (isset($_POST["submit_feedback"])) {

    if (!$currentUser) {

        $message = "Please log in before submitting feedback.";
        $messageType = "error";

    } else {

        $guideId = intval($_POST["feedback_guide_id"] ?? 0);
        $touristName = trim($_POST["tourist_name"] ?? "");
        $rating = intval($_POST["rating"] ?? 0);
        $comment = trim($_POST["comment"] ?? "");

        if ($currentUser) {
            $touristName = trim($currentUser["name"] ?? "");
        }

        if (
            $guideId <= 0 ||
            $touristName === "" ||
            $rating < 1 ||
            $rating > 5
        ) {

            $message = "Please complete all feedback fields.";
            $messageType = "error";

        } elseif ((int)$currentUser["points"] < 5) {

            $message = "You need at least 5 loyalty points to leave feedback. Earn some by registering for a tour or submitting a review.";
            $messageType = "error";

        } else {

            $stmt = $db->prepare("
                INSERT INTO feedback
                (
                    guide_id,
                    tourist_name,
                    rating,
                    comment
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $guideId,
                $touristName,
                $rating,
                $comment
            ]);

            $stmt = $db->prepare("
                SELECT AVG(rating)
                FROM feedback
                WHERE guide_id = ?
            ");

            $stmt->execute([$guideId]);
            $newRating = round((float)$stmt->fetchColumn(), 1);

            $stmt = $db->prepare("
                UPDATE guides
                SET
                    rating = ?,
                    points = points + 20
                WHERE id = ?
            ");

            $stmt->execute([$newRating, $guideId]);

            $db->prepare("UPDATE users SET points = points - 5 WHERE id = ?")
                ->execute([(int)$currentUser["id"]]);

            $message = "Thank you for your feedback! ⭐ 5 loyalty points were deducted and the guide received 20 loyalty points.";
            $messageType = "success";

            $currentUser["points"] = (int)$currentUser["points"] - 5;
        }
    }
}


// ============================================================
// TOUR HISTORY
// ============================================================

if (isset($_POST["add_history"])) {

    if (!$currentUser) {

        $message = "Please log in before adding tour history.";
        $messageType = "error";

    } else {

        $guideId = intval($_POST["history_guide_id"] ?? 0);
        $touristName = $currentUser ? trim($currentUser["name"] ?? "") : trim($_POST["history_tourist"] ?? "");
        $destination = trim($_POST["destination"] ?? "");
        $tourDate = trim($_POST["tour_date"] ?? "");
        $duration = trim($_POST["duration"] ?? "");

        if (
            $guideId <= 0 ||
            $touristName === "" ||
            $destination === "" ||
            $tourDate === ""
        ) {

            $message = "Please complete the tour history form.";
            $messageType = "error";

        } else if ((int)$currentUser["points"] < 10) {

            $message = "You need at least 10 loyalty points to log a tour report. Your guide earns 50 points and you lose 10 for this report. Earn points by leaving feedback.";
            $messageType = "error";

        } else {

            $stmt = $db->prepare("
                INSERT INTO tour_history
                (
                    guide_id,
                    tourist_name,
                    destination,
                    tour_date,
                    duration
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $guideId,
                $touristName,
                $destination,
                $tourDate,
                $duration
            ]);

            $stmt = $db->prepare("
                UPDATE guides
                SET points = points + 50
                WHERE id = ?
            ");

            $stmt->execute([$guideId]);

            $db->prepare("UPDATE users SET points = points - 10 WHERE id = ?")
                ->execute([(int)$currentUser["id"]]);

            $message = "Tour report logged! 📝 Your guide earned 50 loyalty points, and 10 points were deducted from your account to support the reporting system.";
            $messageType = "success";

            $currentUser["points"] = (int)$currentUser["points"] - 10;
        }
    }
}


// ============================================================
// GUIDE SEARCH
// ============================================================

$search =
    trim($_GET["search"] ?? "");


if ($search !== "") {

    $stmt = $db->prepare("
        SELECT *
        FROM guides

        WHERE
            name LIKE ?
            OR location LIKE ?
            OR languages LIKE ?
            OR specialty LIKE ?

        ORDER BY
            rating DESC,
            points DESC
    ");


    $like =
        "%" . $search . "%";


    $stmt->execute([

        $like,

        $like,

        $like,

        $like

    ]);


    $guides =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

else {

    $guides =
        $db->query("
            SELECT *
            FROM guides

            ORDER BY
                rating DESC,
                points DESC
        ")->fetchAll(
            PDO::FETCH_ASSOC
        );
}


// ============================================================
// TOUR HISTORY DATA - PUBLIC GUIDE TOUR DISPLAY
// ============================================================

$history = array();
$selectedGuideId = intval($_GET["guide_filter"] ?? 0);
$selectedPlace = trim($_GET["place_filter"] ?? "");

// Get tourists - filter by place and/or guide
if ($selectedGuideId > 0) {
    // Show tourists for specific guide
    $stmt = $db->prepare("
        SELECT
            tourists.*,
            guides.location
        FROM tourists
        JOIN guides ON tourists.guide_id = guides.id
        WHERE tourists.guide_id = ?
        ORDER BY tourists.visit_date DESC
    ");
    
    $stmt->execute([$selectedGuideId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($selectedPlace !== "") {
    // Show tourists from selected place (all guides in that place)
    $stmt = $db->prepare("
        SELECT
            tourists.*,
            guides.location
        FROM tourists
        JOIN guides ON tourists.guide_id = guides.id
        WHERE guides.location = ?
        ORDER BY tourists.visit_date DESC
    ");
    
    $stmt->execute([$selectedPlace]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Show all tourists if no filter selected
    $stmt = $db->query("
        SELECT
            tourists.*,
            guides.location
        FROM tourists
        JOIN guides ON tourists.guide_id = guides.id
        ORDER BY tourists.visit_date DESC
    ");
    
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// ============================================================
// FEEDBACK DATA
// ============================================================

$feedback =
    $db->query("
        SELECT

            feedback.*,

            guides.name AS guide_name

        FROM feedback

        JOIN guides

        ON feedback.guide_id =
           guides.id

        ORDER BY
            feedback.created_at DESC

        LIMIT 10
    ")->fetchAll(
        PDO::FETCH_ASSOC
    );


// ============================================================
// TOURISTS WITH SELECTED GUIDES
// ============================================================

$registeredTourists =
    $db->query("
        SELECT

            tourists.*,

            guides.name AS guide_name

        FROM tourists

        LEFT JOIN guides

        ON tourists.guide_id =
           guides.id

        ORDER BY
            tourists.id DESC
    ")->fetchAll(
        PDO::FETCH_ASSOC
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>
Nuwakot Explore | Sustainable Tourism
</title>


<!-- ========================================================
     LEAFLET MAP
     ======================================================== -->

<link
rel="stylesheet"
href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>


<script
src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>


<style>

/* ============================================================
   GLOBAL
   ============================================================ */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f5f8f4;

    color: #26332a;
}

a {
    text-decoration: none;
}

.container {

    width: 92%;

    max-width: 1200px;

    margin: auto;
}


button {
    cursor: pointer;
}


/* ============================================================
   NAVBAR
   ============================================================ */

.navbar {

    background: #123d2a;

    color: white;

    padding: 15px 0;

    position: sticky;

    top: 0;

    z-index: 1000;
}


.account-bar {

    background: #ecf8e8;

    border-bottom: 1px solid #d2ebc8;

    padding: 12px 0;
}


.account-wrap {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    flex-wrap: wrap;
}


.account-box {

    display: flex;

    align-items: center;

    gap: 12px;

    flex-wrap: wrap;
}


.account-forms {

    display: flex;

    gap: 15px;

    align-items: center;

    flex-wrap: wrap;

    margin-top: 12px;
}


.account-form {

    background: white;

    border: 1px solid #dcead8;

    border-radius: 12px;

    padding: 18px;

    box-shadow: 0 5px 20px rgba(0,0,0,0.04);

    flex: 1 1 260px;
}


.form-row {

    display: flex;

    gap: 12px;

    flex-wrap: wrap;

    margin-bottom: 12px;
}


.user-badge {

    background: #dff4dc;

    color: #23652a;

    padding: 8px 12px;

    border-radius: 999px;

    font-weight: bold;
}


.user-floating-panel {

    position: fixed;

    top: 90px;

    right: 20px;

    z-index: 2000;

    background: rgba(18, 61, 42, 0.96);

    color: white;

    border-radius: 16px;

    box-shadow: 0 12px 30px rgba(0,0,0,0.2);

    padding: 16px 18px;

    min-width: 240px;

    max-width: 280px;
}


.user-floating-panel h4 {

    margin: 0 0 8px;

    font-size: 15px;

    color: #b7ed73;

}


.user-floating-panel p {

    margin: 5px 0;

    font-size: 13px;

    color: #eaf7ea;

}


.toast {

    position: fixed;

    top: 20px;

    right: 20px;

    z-index: 2100;

    background: #1d7c3f;

    color: white;

    padding: 14px 18px;

    border-radius: 12px;

    box-shadow: 0 10px 25px rgba(0,0,0,0.18);

    max-width: 360px;

    display: none;

    font-weight: 600;
}


.toast.error {

    background: #dc2626;

    color: white;

}


.toast.show {

    display: block;

    animation: fadeIn 0.25s ease;
}


@keyframes fadeIn {

    from { opacity: 0; transform: translateY(-8px); }

    to { opacity: 1; transform: translateY(0); }

}


.nav-inner {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;
}


.logo {

    font-size: 24px;

    font-weight: bold;

    white-space: nowrap;
}


.logo span {

    color: #a7df65;
}


.nav-links {

    display: flex;

    gap: 18px;

    flex-wrap: wrap;
}


.nav-links a {

    color: white;

    font-size: 14px;
}


.nav-links a:hover {

    color: #a7df65;
}


/* ============================================================
   HERO
   ============================================================ */

.hero {

    min-height: 570px;

    display: flex;

    align-items: center;

    background:

        linear-gradient(
            rgba(12, 51, 35, 0.78),
            rgba(12, 51, 35, 0.78)
        ),

        url("https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1600&q=80")

        center/cover;

    color: white;
}


.hero-content {

    max-width: 720px;
}


.hero h1 {

    font-size: 55px;

    line-height: 1.05;

    margin-bottom: 15px;
}


.hero h1 span {

    color: #b7ed73;
}


.hero p {

    font-size: 18px;

    line-height: 1.7;
}


.btn {

    display: inline-block;

    padding: 12px 20px;

    border-radius: 8px;

    border: none;

    background: #68a83e;

    color: white;

    font-weight: bold;

    margin-top: 10px;

    transition: .2s;
}


.btn:hover {

    background: #4d882d;

    transform: translateY(-1px);
}


.btn-dark {

    background: #123d2a;
}


.btn-dark:hover {

    background: #0b2a1d;
}


/* ============================================================
   SECTIONS
   ============================================================ */

section {

    padding: 65px 0;
}


.section-title {

    text-align: center;

    margin-bottom: 35px;
}


.section-title h2 {

    font-size: 34px;

    color: #174a31;

    margin-bottom: 8px;
}


.section-title p {

    color: #66736a;
}


/* ============================================================
   CARDS
   ============================================================ */

.cards {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(220px, 1fr));

    gap: 20px;
}


.card {

    background: white;

    border-radius: 15px;

    padding: 25px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.07);
}


.card h3 {

    color: #174a31;
}


.icon {

    font-size: 35px;
}


/* ============================================================
   MAP
   ============================================================ */

#map {

    height: 500px;

    width: 100%;

    border-radius: 18px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.1);
}


/* ============================================================
   FORMS
   ============================================================ */

.form-box {

    background: white;

    padding: 30px;

    border-radius: 15px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.07);
}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(220px, 1fr));

    gap: 18px;
}


.form-group {

    display: flex;

    flex-direction: column;
}


.form-group.full {

    grid-column: 1 / -1;
}


label {

    font-weight: bold;

    margin-bottom: 7px;
}


input,
select,
textarea {

    padding: 12px;

    border: 1px solid #ccd7cf;

    border-radius: 8px;

    font-size: 15px;

    width: 100%;

    background: white;
}


textarea {

    min-height: 100px;

    resize: vertical;
}


/* ============================================================
   MESSAGE
   ============================================================ */

.message {

    width: 92%;

    max-width: 1200px;

    margin: 20px auto;

    padding: 15px;

    border-radius: 8px;

    font-weight: bold;
}


.success {

    background: #dff4dc;

    color: #23652a;
}


.error {

    background: #ffe0e0;

    color: #9d2525;
}


/* ============================================================
   GUIDE SEARCH
   ============================================================ */

.search-box {

    display: flex;

    gap: 10px;

    margin-bottom: 30px;
}


.search-box input {

    flex: 1;
}


.guide-card {

    position: relative;
}


.guide-top {

    display: flex;

    justify-content: space-between;

    gap: 10px;
}


.rating {

    color: #e5a500;

    font-weight: bold;
}


.points {

    display: inline-block;

    background: #e9f6dd;

    color: #3e7b25;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 13px;

    margin-top: 10px;
}


.badge {

    display: inline-block;

    background: #e7f0eb;

    color: #174a31;

    padding: 6px 10px;

    border-radius: 20px;

    margin: 5px 3px 0 0;

    font-size: 12px;
}


/* ============================================================
   WEATHER
   ============================================================ */

.weather-box {

    background: white;

    padding: 30px;

    border-radius: 15px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.07);
}


.weather-result {

    margin-top: 25px;

    display: none;
}


.weather-main {

    font-size: 45px;

    font-weight: bold;
}


.weather-grid {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(150px, 1fr));

    gap: 15px;

    margin-top: 20px;
}


.weather-item {

    background: #f1f7ee;

    padding: 20px;

    border-radius: 10px;

    text-align: center;
}


/* ============================================================
   TABLE
   ============================================================ */

.table-wrapper {

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;

    background: white;

    border-radius: 10px;

    overflow: hidden;
}


th,
td {

    padding: 13px;

    border-bottom:
        1px solid #e1e7e2;

    text-align: left;
}


th {

    background: #174a31;

    color: white;
}


tr:hover {

    background: #f5f9f3;
}


/* ============================================================
   FEEDBACK
   ============================================================ */

.feedback {

    border-left:
        4px solid #68a83e;

    margin-bottom: 15px;
}


/* ============================================================
   SELECTED GUIDE BOX
   ============================================================ */

.guide-selected {

    margin-top: 15px;

    padding: 15px;

    background: #edf7e9;

    border: 1px solid #cce4c5;

    border-radius: 10px;

    display: none;
}


/* ============================================================
   FOOTER
   ============================================================ */

footer {

    background: #123d2a;

    color: white;

    padding: 35px 0;

    text-align: center;
}


footer p {

    margin: 6px;

    color: #d0dfd4;
}


/* ============================================================
   RESPONSIVE
   ============================================================ */

@media(max-width: 750px) {

    .nav-inner {

        flex-direction: column;
    }

    .hero h1 {

        font-size: 38px;
    }

    .hero {

        min-height: 500px;
    }

    .search-box {

        flex-direction: column;
    }

}

</style>

</head>


<body>


<!-- ==========================================================
     NAVBAR
     ========================================================== -->

<nav class="navbar">

<div class="container nav-inner">


<div class="logo">

🌿 Nuwakot
<span>Explore</span>

</div>


<div class="nav-links">

<a href="#home">
Home
</a>

<a href="#map-section">
Map
</a>

<a href="#guides">
Guides
</a>

<a href="#register">
Register
</a>

<a href="#weather">
Weather
</a>

<a href="#history">
History
</a>

<a href="#feedback">
Feedback
</a>

</div>


</div>

</nav>

<div class="account-bar">

<div class="container account-wrap">

<div class="account-box">

<?php if ($currentUser): ?>

<span class="user-badge">👤 <?= clean($currentUser["name"]) ?></span>
<?php if ($currentUser["role"] !== "tourist"): ?>
<span class="user-badge">🏆 <?= intval($currentUser["points"]) ?> points</span>
<?php endif; ?>

<form method="POST" style="margin:0;">
    <button class="btn btn-dark" type="submit" name="logout_user" style="margin-top:0; padding:10px 16px;">Logout</button>
</form>

<?php else: ?>

<span class="user-badge">🔐 Public browsing mode</span>
<?php endif; ?>

</div>

<?php if (!$currentUser): ?>

<div class="account-box">

<button class="btn" type="button" onclick="toggleAuthForm('login')" style="margin-top:0; padding:10px 16px;">Login</button>
<button class="btn" type="button" onclick="toggleAuthForm('signup')" style="margin-top:0; padding:10px 16px;">Sign In</button>

</div>
<?php endif; ?>

</div>

</div>

<?php if (!$currentUser): ?>
<div class="container" id="login-form-container" style="display:none;">
    <div class="account-forms">
        <div class="account-form">
            <h3 style="margin:0 0 12px; color:#174a31;">Login</h3>
            <form method="POST">
                <div class="form-row">
                    <input type="email" name="login_email" placeholder="Email" required style="flex:1; min-width:200px; padding:10px;">
                    <input type="password" name="login_password" placeholder="Password" required style="flex:1; min-width:200px; padding:10px;">
                    <button class="btn" type="submit" name="login_user" style="margin-top:0; padding:10px 16px;">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container" id="signup-form-container" style="display:none;">
    <div class="account-forms">
        <div class="account-form">
            <h3 style="margin:0 0 12px; color:#174a31;">Create an account</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="account_name" required placeholder="Your name">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="account_email" required placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="account_password" required placeholder="At least 6 characters">
                </div>
                <div class="form-group">
                    <label>Account Type</label>
                    <select name="account_role">
                        <option value="tourist">Tourist</option>
                        <option value="guide">Guide</option>
                    </select>
                </div>
                <button class="btn" type="submit" name="register_account">Create Account</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- ==========================================================
     MESSAGE
     ========================================================== -->

<?php if ($message !== ""): ?>

<div class="message <?= $messageType ?>" id="serverMessage" style="display:none;">

<?= clean($message) ?>

</div>

<?php endif; ?>

<?php if ($currentUser): ?>
<div class="user-floating-panel">
    <h4>👤 <?= clean($currentUser["name"]) ?></h4>
    <p><?= clean($currentUser["email"]) ?></p>
    <p>Role: <?= clean(ucfirst($currentUser["role"])) ?></p>
    <p>🏆 Loyalty Points: <strong><?= intval($currentUser["points"]) ?></strong></p>
</div>
<?php endif; ?>

<div class="toast <?= $messageType ?>" id="pointsToast">
    <?= clean($message) ?>
</div>


<!-- ==========================================================
     HERO
     ========================================================== -->

<section
class="hero"
id="home"
>

<div class="container">

<div class="hero-content">

<h1>

Discover
<span>Nuwakot</span>
Responsibly

</h1>


<p>

Explore Nuwakot's history, culture,
mountains, villages, indigenous knowledge
and natural beauty with local guides.

</p>


<p>

Our platform connects tourists,
local guides and communities while
promoting sustainable tourism.

</p>


<a
href="#register"
class="btn"
>
📝 Register as Tourist
</a>


<a
href="#guides"
class="btn btn-dark"
>
🧭 Find a Guide
</a>

</div>

</div>

</section>


<!-- ==========================================================
     KEY FEATURES
     ========================================================== -->

<section>

<div class="container">


<div class="section-title">

<h2>
🌱 Our Eco-Tourism Approach
</h2>

<p>
People • Planet • Purpose • Place
</p>

</div>


<div class="cards">


<div class="card">

<div class="icon">
🌱
</div>

<h3>
Sustainable Tourism
</h3>

<p>

Promote responsible travel while
protecting Nuwakot's environment
and communities.

</p>

</div>


<div class="card">

<div class="icon">
🧭
</div>

<h3>
Local Guides
</h3>

<p>

Connect tourists with experienced
local guides who know the area.

</p>

</div>


<div class="card">

<div class="icon">
🏛️
</div>

<h3>
Heritage
</h3>

<p>

Discover Nuwakot's historical
places, culture and stories.

</p>

</div>


<div class="card">

<div class="icon">
🌾
</div>

<h3>
Local Economy
</h3>

<p>

Support local guides, farmers,
homestays and communities.

</p>

</div>


</div>

</div>

</section>


<!-- ==========================================================
     MAP
     ========================================================== -->

<section id="map-section">

<div class="container">


<div class="section-title">

<h2>
🗺️ Explore Nuwakot
</h2>

<p>
Interactive map of tourism locations.
</p>

</div>


<div id="map"></div>


</div>

</section>


<!-- ==========================================================
     GUIDE SEARCH
     ========================================================== -->

<section id="guides">

<div class="container">


<div class="section-title">

<h2>
🧭 Find Your Local Guide
</h2>

<p>

Search by guide name, location,
language or specialty.

</p>

</div>


<form
class="search-box"
method="GET"
>


<input
type="text"
name="search"
placeholder="Search guide, location, language..."
value="<?= clean($search) ?>"
>


<button
class="btn"
type="submit"
>
🔎 Search
</button>


<a
href="index.php#guides"
class="btn btn-dark"
>
Reset
</a>


</form>


<div class="cards">


<?php foreach ($guides as $guide): ?>


<div class="card guide-card">


<div class="guide-top">


<div>

<h3>

👤
<?= clean($guide["name"]) ?>

</h3>


<p>

📍
<?= clean($guide["location"]) ?>

</p>

</div>


<div class="rating">

⭐
<?= number_format(
    (float)$guide["rating"],
    1
) ?>

</div>


</div>


<p>

<strong>
Experience:
</strong>

<?= intval(
    $guide["experience"]
) ?>

years

</p>


<p>

<strong>
Specialty:
</strong>

<?= clean(
    $guide["specialty"]
) ?>

</p>


<span class="badge">

🗣️
<?= clean(
    $guide["languages"]
) ?>

</span>


<div class="points">

🏆
<?= intval(
    $guide["points"]
) ?>

Loyalty Points

</div>


<br>


<a
href="#feedback"
class="btn"
onclick="selectGuide(<?= intval($guide['id']) ?>)"
>
⭐ Rate Guide
</a>


</div>


<?php endforeach; ?>


</div>

</div>

</section>


<!-- ==========================================================
     TOURIST REGISTRATION
     ========================================================== -->

<section id="register">

<div class="container">


<div class="section-title">

<h2>
📝 Tourist Registration
</h2>

<p>

Register and choose your preferred
local guide.

</p>

</div>


<div class="form-box">

<?php if (!$currentUser): ?>

<div class="message error" style="width:100%; margin:0 0 20px 0;">
    Create an account or log in to continue. Public browsing does not require sign-in.
</div>
<?php endif; ?>

<form method="POST">


<div class="form-grid">


<!-- NAME -->

<div class="form-group">

<label>
Full Name *
</label>

<input
type="text"
name="name"
required
placeholder="Enter your name"
value="<?= $currentUser ? clean($currentUser["name"]) : "" ?>"
<?= $currentUser ? "readonly" : "" ?>
>

</div>


<!-- EMAIL -->

<div class="form-group">

<label>
Email *
</label>

<input
type="email"
name="email"
required
placeholder="you@example.com"
value="<?= $currentUser ? clean($currentUser["email"]) : "" ?>"
<?= $currentUser ? "readonly" : "" ?>
>

</div>


<!-- PHONE -->

<div class="form-group">

<label>
Phone
</label>

<input
type="tel"
name="phone"
placeholder="+977..."
>

</div>


<!-- COUNTRY -->

<div class="form-group">

<label>
Country
</label>

<input
type="text"
name="country"
placeholder="Nepal"
>

</div>


<!-- DATE -->

<div class="form-group">

<label>
Planned Visit Date
</label>

<input
type="date"
name="visit_date"
>

</div>


<!-- ========================================================
     PLACE SELECTION
     ======================================================== -->

<div class="form-group">

<label>
📍 Choose a Place to Visit
</label>

<select
name="place"
id="placeSelect"
required
onchange="filterGuidesByPlace()"
>

<option value="">
-- Select a Place --
</option>

<option value="Bidur">
🏘️ Bidur (Culture & Crafts)
</option>

<option value="Kakani">
🏔️ Kakani (Mountain & Hiking)
</option>

<option value="Nuwakot Durbar">
🏰 Nuwakot Durbar (Palace & History)
</option>

<option value="Tarkeshwar">
🏡 Tarkeshwar (Village & Eco)
</option>

</select>

</div>


<!-- ========================================================
     GUIDE SELECTION
     ======================================================== -->

<div class="form-group">

<label>
🧭 Choose Your Guide
</label>

<select
name="guide_id"
id="registrationGuide"
onchange="showSelectedGuide()"
required
>

<option value="">
-- Select a Guide --
</option>

<?php foreach ($guides as $guide): ?>

<option
value="<?= intval($guide["id"]) ?>"
data-location="<?= clean($guide["location"]) ?>"
data-specialty="<?= clean($guide["specialty"]) ?>"
data-rating="<?= number_format((float)$guide["rating"], 1) ?>"
data-points="<?= intval($guide["points"]) ?>"
style="display:none;"
class="guide-option"
>

<?= clean($guide["name"]) ?>
-
⭐ <?= number_format((float)$guide["rating"], 1) ?>

</option>


<?php endforeach; ?>


</select>


</div>


<!-- SELECTED GUIDE INFORMATION -->

<div
class="form-group full"
>

<div
id="selectedGuideInfo"
class="guide-selected"
>

<strong>
Selected Guide
</strong>

<div id="selectedGuideText"></div>

</div>

</div>


<!-- NOTES -->

<div
class="form-group full"
>

<label>
Travel Preferences / Notes
</label>


<textarea
name="travel_notes"
placeholder="Tell us what kind of experience you want..."
></textarea>


</div>


<!-- SUBMIT -->

<div
class="form-group full"
>

<button
class="btn"
type="submit"
name="register_tourist"
>

📝 Register & Choose Guide

</button>

</div>


</div>


</form>


</div>

</div>

</section>


<!-- ==========================================================
     WEATHER
     ========================================================== -->

<section id="weather">

<div class="container">


<div class="section-title">

<h2>
🌦️ Weather Planner
</h2>

<p>

Choose your travel date and time
before planning outdoor activities.

</p>

</div>


<div class="weather-box">


<div class="form-grid">


<div class="form-group">

<label>
📍 Choose Place
</label>

<select id="weatherPlace">

<option value="Bidur">🏘️ Bidur</option>

<option value="Kakani">🏔️ Kakani</option>

<option value="Nuwakot Durbar" selected>🏰 Nuwakot Durbar</option>

<option value="Tarkeshwar">🏡 Tarkeshwar</option>

</select>

</div>


<div class="form-group">

<label>
Date
</label>

<input
type="date"
id="weatherDate"
>

</div>


<div class="form-group">

<label>
Time
</label>

<input
type="time"
id="weatherTime"
value="12:00"
>

</div>


<div class="form-group">

<label>
Check
</label>

<button
type="button"
class="btn"
onclick="getWeather()"
>

🌦️ Check Weather

</button>

</div>


</div>


<div
id="weatherResult"
class="weather-result"
>


<h3 id="weatherTitle">

Weather Information

</h3>


<div class="weather-main">

<span id="temperature">
--
</span>
°C

</div>


<p id="weatherDescription">

Select a date and time.

</p>


<div class="weather-grid">


<div class="weather-item">

<strong>
💧 Humidity
</strong>

<p id="humidity">
--
</p>

</div>


<div class="weather-item">

<strong>
💨 Wind
</strong>

<p id="wind">
--
</p>

</div>


<div class="weather-item">

<strong>
🌧️ Rain
</strong>

<p id="rain">
--
</p>

</div>


<div class="weather-item">

<strong>
☁️ Cloud
</strong>

<p id="cloud">
--
</p>

</div>


</div>


</div>

</div>

</div>

</section>


<!-- ==========================================================
     FEEDBACK
     ========================================================== -->

<section id="feedback">

<div class="container">


<div class="section-title">

<h2>
⭐ Guide Rating & Feedback
</h2>

<p>

Tell future tourists about your experience.

</p>

</div>


<div
class="form-box"
id="feedback-form"
>


<form method="POST">


<div class="form-grid">


<div class="form-group">

<label>
Your Name *
</label>

<input
type="text"
name="tourist_name"
required
placeholder="Your name"
value="<?= $currentUser ? clean($currentUser["name"]) : "" ?>"
<?= $currentUser ? "readonly" : "" ?>
>

</div>


<div class="form-group">

<label>
Guide *
</label>


<select
name="feedback_guide_id"
id="feedbackGuide"
required
>


<option value="">
-- Choose Guide --
</option>


<?php foreach ($guides as $guide): ?>


<option
value="<?= intval($guide["id"]) ?>"
>

<?= clean($guide["name"]) ?>

-

⭐
<?= number_format(
    (float)$guide["rating"],
    1
) ?>


</option>


<?php endforeach; ?>


</select>

</div>


<div class="form-group">

<label>
Rating *
</label>


<select
name="rating"
required
>


<option value="">
Select Rating
</option>


<option value="5">
⭐⭐⭐⭐⭐ Excellent
</option>


<option value="4">
⭐⭐⭐⭐ Very Good
</option>


<option value="3">
⭐⭐⭐ Good
</option>


<option value="2">
⭐⭐ Needs Improvement
</option>


<option value="1">
⭐ Poor
</option>


</select>

</div>


<div class="form-group full">

<label>
Your Feedback
</label>


<textarea
name="comment"
placeholder="How was your experience?"
></textarea>


</div>


<div class="form-group full">

<button
class="btn"
type="submit"
name="submit_feedback"
>

⭐ Submit Feedback

</button>

</div>


</div>


</form>


</div>

</div>

</section>


<!-- ==========================================================
     TOUR HISTORY - PUBLIC GUIDE HISTORY
     ========================================================== -->

<section id="history">

<div class="container">


<div class="section-title">

<h2>
📜 Guide Tour History
</h2>

<p>

Choose a place and guide to see tourists they've served.

</p>

</div>


<div class="form-box">

<form method="GET" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin:0;">

<!-- PLACE SELECTION -->

<div class="form-group" style="margin:0;">

<label>
📍 Choose Place
</label>

<select name="place_filter" onchange="this.form.submit();" style="width:100%; padding:10px;">

<option value="">-- All Places --</option>

<option value="Bidur" <?= ($_GET["place_filter"] ?? "") === "Bidur" ? "selected" : "" ?>>
🏘️ Bidur
</option>

<option value="Kakani" <?= ($_GET["place_filter"] ?? "") === "Kakani" ? "selected" : "" ?>>
🏔️ Kakani
</option>

<option value="Nuwakot Durbar" <?= ($_GET["place_filter"] ?? "") === "Nuwakot Durbar" ? "selected" : "" ?>>
🏰 Nuwakot Durbar
</option>

<option value="Tarkeshwar" <?= ($_GET["place_filter"] ?? "") === "Tarkeshwar" ? "selected" : "" ?>>
🏡 Tarkeshwar
</option>

</select>

</div>

<!-- GUIDE SELECTION -->

<div class="form-group" style="margin:0;">

<label>
🧭 Choose Guide
</label>

<select name="guide_filter" onchange="this.form.submit();" style="width:100%; padding:10px;">

<option value="">-- All Guides --</option>

<?php foreach ($guides as $guide): 

    $placeFilter = $_GET["place_filter"] ?? "";
    
    // Only show guides from selected place (or all if no place selected)
    if ($placeFilter === "" || $guide["location"] === $placeFilter):

?>

<option value="<?= intval($guide["id"]) ?>" <?= ($_GET["guide_filter"] ?? "") === (string)$guide["id"] ? "selected" : "" ?>>

<?= clean($guide["name"]) ?> (<?= clean($guide["location"]) ?>)

</option>

<?php endif; endforeach; ?>

</select>

</div>

</form>

</div>


<br>


<div class="table-wrapper">


<table>


<thead>

<tr>

<th>
Tourist Name
</th>

<th>
Email
</th>

<th>
Phone
</th>

<th>
Country
</th>

<th>
Visit Date
</th>

<th>
Interests
</th>

</tr>

</thead>


<tbody>


<?php if (count($history) > 0): ?>


<?php foreach ($history as $tour): ?>


<tr>

<td>
<?= clean($tour["name"]) ?>
</td>

<td>
<?= clean($tour["email"]) ?>
</td>

<td>
<?= clean($tour["phone"]) ?>
</td>

<td>
<?= clean($tour["country"]) ?>
</td>

<td>
<?= clean($tour["visit_date"]) ?>
</td>

<td>
<?= clean($tour["interests"]) ?>
</td>

</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td colspan="6">

No tourists for this guide yet.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>

</div>

</section>


<!-- ==========================================================
     REGISTERED TOURISTS
     ========================================================== -->

<section>

<div class="container">


<div class="section-title">

<h2>
👥 Registered Tourists
</h2>

<p>

Tourists and their selected guides.

</p>

</div>


<div class="table-wrapper">


<table>


<thead>

<tr>

<th>
Tourist
</th>

<th>
Email
</th>

<th>
Visit Date
</th>

<th>
Interest
</th>

<th>
Selected Guide
</th>

</tr>

</thead>


<tbody>


<?php if (count($registeredTourists) > 0): ?>


<?php foreach ($registeredTourists as $tourist): ?>


<tr>

<td>
<?= clean(
    $tourist["name"]
) ?>
</td>


<td>
<?= clean(
    $tourist["email"]
) ?>
</td>


<td>
<?= clean(
    $tourist["visit_date"]
) ?>
</td>


<td>
<?= clean(
    $tourist["interests"]
) ?>
</td>


<td>

<?php if ($tourist["guide_name"]): ?>

🧭
<?= clean(
    $tourist["guide_name"]
) ?>

<?php else: ?>

Not selected

<?php endif; ?>

</td>

</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td colspan="5">

No tourists registered yet.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>

</div>

</section>


<!-- ==========================================================
     RECENT FEEDBACK
     ========================================================== -->

<section>

<div class="container">


<div class="section-title">

<h2>
💬 Traveller Feedback
</h2>

<p>

Reviews from people who travelled with the guides.

</p>

</div>


<?php if (count($feedback) > 0): ?>


<div class="cards">


<?php foreach ($feedback as $item): ?>


<div class="card feedback">


<h3>

🧭
<?= clean(
    $item["guide_name"]
) ?>

</h3>


<p class="rating">

<?= str_repeat(
    "⭐",
    intval($item["rating"])
) ?>

</p>


<p>

"<?= clean(
    $item["comment"]
) ?>"

</p>


<small>

— <?= clean(
    $item["tourist_name"]
) ?>

</small>


</div>


<?php endforeach; ?>


</div>


<?php else: ?>


<div class="card">

<p>

No feedback yet.
Be the first traveller to review a guide!

</p>

</div>


<?php endif; ?>


</div>

</section>


<!-- ==========================================================
     LOYALTY SYSTEM
     ========================================================== -->

<section>

<div class="container">


<div class="section-title">

<h2>
🏆 Guide Loyalty System
</h2>

<p>

Reward guides for quality service and
responsible tourism.

</p>

</div>


<div class="cards">


<div class="card">

<div class="icon">
📝
</div>

<h3>
Tourist Registration
</h3>

<p>

When a tourist chooses a guide during
registration, the guide receives
10 loyalty points.

</p>

</div>


<div class="card">

<div class="icon">
⭐
</div>

<h3>
Traveller Feedback
</h3>

<p>

Every submitted feedback gives the guide
20 loyalty points.

</p>

</div>


<div class="card">

<div class="icon">
🥾
</div>

<h3>
Completed Tour
</h3>

<p>

Every recorded tour gives the guide
50 loyalty points.

</p>

</div>


<div class="card">

<div class="icon">
🏅
</div>

<h3>
Guide Reputation
</h3>

<p>

Tourists can compare guides using
rating, experience and loyalty points.

</p>

</div>


</div>

</div>

</section>


<!-- ==========================================================
     FOOTER
     ========================================================== -->

<footer>

<div class="container">


<h2>
🌿 Nuwakot Explore
</h2>


<p>

Connecting tourists with Nuwakot's
people, culture and nature.

</p>


<p>

Sustainable Tourism • Local Guides •
Heritage • Community

</p>


<p>

© <?= date("Y") ?>
Nuwakot Eco Tourism

</p>


</div>

</footer>


<!-- ==========================================================
     JAVASCRIPT
     ========================================================== -->

<script>


// ============================================================
// NUWAKOT MAP
// ============================================================

const nuwakotLat =
    27.9147;

const nuwakotLng =
    85.1520;


const map =
    L.map("map").setView(
        [
            nuwakotLat,
            nuwakotLng
        ],
        11
    );


L.tileLayer(

    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",

    {

        maxZoom: 19,

        attribution:
            "&copy; OpenStreetMap contributors"

    }

).addTo(map);


// Nuwakot

L.marker([
    27.9147,
    85.1520
])

.addTo(map)

.bindPopup(`

    <b>📍 Nuwakot</b>

    <br>

    Heritage, culture and nature.

`);


// Nuwakot Durbar

L.marker([
    27.9140,
    85.1537
])

.addTo(map)

.bindPopup(`

    <b>🏛️ Nuwakot Durbar</b>

    <br>

    Historical heritage destination.

`);


// Kakani

L.marker([
    27.8047,
    85.2520
])

.addTo(map)

.bindPopup(`

    <b>🌲 Kakani</b>

    <br>

    Nature, hiking and mountain views.

`);


// Devighat

L.marker([
    27.9070,
    85.0840
])

.addTo(map)

.bindPopup(`

    <b>🌿 Devighat</b>

    <br>

    Cultural and riverside destination.

`);


// Bidur

L.marker([
    27.9167,
    85.1500
])

.addTo(map)

.bindPopup(`

    <b>🏘️ Bidur</b>

    <br>

    Main urban centre of Nuwakot.

`);


// ============================================================
// GUIDE SELECTION DURING REGISTRATION
// ============================================================

function showSelectedGuide() {

    const select =
        document.getElementById(
            "registrationGuide"
        );


    const option =
        select.options[
            select.selectedIndex
        ];


    const info =
        document.getElementById(
            "selectedGuideInfo"
        );


    const text =
        document.getElementById(
            "selectedGuideText"
        );


    if (
        !option ||
        !option.value
    ) {

        info.style.display =
            "none";

        return;
    }


    const name =
        option.textContent.trim();


    const location =
        option.dataset.location;


    const specialty =
        option.dataset.specialty;


    const rating =
        option.dataset.rating;


    const points =
        option.dataset.points;


    text.innerHTML = `

        <strong>${name}</strong>

        <br>

        📍 ${location}

        <br>

        🎯 ${specialty}

        <br>

        ⭐ ${rating}

        &nbsp;&nbsp;

        🏆 ${points} loyalty points

    `;


    info.style.display =
        "block";
}


// ============================================================
// FILTER GUIDES BY PLACE
// ============================================================

function filterGuidesByPlace() {

    const placeSelect = 
        document.getElementById("placeSelect");

    const guideSelect = 
        document.getElementById("registrationGuide");

    const selectedPlace = 
        placeSelect.value;

    const allOptions = 
        guideSelect.querySelectorAll(".guide-option");

    // Reset guide selection
    guideSelect.value = "";

    // Show/hide guide options based on place
    allOptions.forEach(option => {

        const guideLocation = 
            option.dataset.location;

        if (selectedPlace === "" || guideLocation === selectedPlace) {

            option.style.display = "block";

        } else {

            option.style.display = "none";
        }
    });

    // Hide selected guide info when place changes
    const info = 
        document.getElementById("selectedGuideInfo");

    if (info) {
        info.style.display = "none";
    }
}


// ============================================================
// SELECT GUIDE FROM GUIDE CARD
// ============================================================

function selectGuide(id) {

    const select =
        document.getElementById(
            "feedbackGuide"
        );


    if (select) {

        select.value =
            id;

        document
            .getElementById(
                "feedback"
            )
            .scrollIntoView({
                behavior: "smooth"
            });
    }
}


// ============================================================
// WEATHER DESCRIPTION
// ============================================================

function weatherDescription(code) {

    const descriptions = {

        0:
            "☀️ Clear sky",

        1:
            "🌤️ Mainly clear",

        2:
            "⛅ Partly cloudy",

        3:
            "☁️ Overcast",

        45:
            "🌫️ Fog",

        48:
            "🌫️ Rime fog",

        51:
            "🌦️ Light drizzle",

        53:
            "🌦️ Moderate drizzle",

        55:
            "🌧️ Dense drizzle",

        61:
            "🌦️ Slight rain",

        63:
            "🌧️ Moderate rain",

        65:
            "🌧️ Heavy rain",

        71:
            "🌨️ Light snow",

        73:
            "🌨️ Moderate snow",

        75:
            "❄️ Heavy snow",

        80:
            "🌦️ Rain showers",

        81:
            "🌧️ Moderate rain showers",

        82:
            "⛈️ Heavy rain showers",

        95:
            "⛈️ Thunderstorm",

        96:
            "⛈️ Thunderstorm with hail",

        99:
            "⛈️ Heavy thunderstorm"

    };


    return (
        descriptions[code]
        ||
        "Weather information"
    );
}


// ============================================================
// GET WEATHER
// ============================================================

async function getWeather() {

    const place =
        document.getElementById(
            "weatherPlace"
        ).value;

    const date =
        document.getElementById(
            "weatherDate"
        ).value;


    const time =
        document.getElementById(
            "weatherTime"
        ).value;


    if (!date || !time) {

        alert(
            "Please select date and time."
        );

        return;
    }

    // Coordinates for each place in Nuwakot
    const placeCoordinates = {
        "Bidur": { lat: 27.8547, lon: 85.0833 },
        "Kakani": { lat: 27.9333, lon: 85.4000 },
        "Nuwakot Durbar": { lat: 27.9147, lon: 85.1520 },
        "Tarkeshwar": { lat: 27.8667, lon: 85.2500 }
    };

    const coords = placeCoordinates[place] || placeCoordinates["Nuwakot Durbar"];

    const url =

        "https://api.open-meteo.com/v1/forecast" +

        "?latitude=" + coords.lat +

        "&longitude=" + coords.lon +

        "&hourly=" +

        "temperature_2m," +

        "relative_humidity_2m," +

        "precipitation," +

        "cloud_cover," +

        "wind_speed_10m," +

        "weather_code" +

        "&timezone=Asia%2FKathmandu";


    try {

        const response =
            await fetch(url);


        const data =
            await response.json();


        const target =
            date +
            "T" +
            time.substring(0, 2) +
            ":00";


        const hours =
            data.hourly.time;


        let index =
            hours.indexOf(target);


        // Find nearest hour
        if (index === -1) {

            index = 0;

            let smallest =
                Infinity;


            for (
                let i = 0;
                i < hours.length;
                i++
            ) {

                const difference =
                    Math.abs(
                        new Date(hours[i]) -
                        new Date(
                            date +
                            "T" +
                            time
                        )
                    );


                if (
                    difference <
                    smallest
                ) {

                    smallest =
                        difference;

                    index =
                        i;
                }
            }
        }


        const temperature =
            data.hourly
                .temperature_2m[index];


        const humidity =
            data.hourly
                .relative_humidity_2m[index];


        const rain =
            data.hourly
                .precipitation[index];


        const cloud =
            data.hourly
                .cloud_cover[index];


        const wind =
            data.hourly
                .wind_speed_10m[index];


        const code =
            data.hourly
                .weather_code[index];


        document.getElementById(
            "weatherResult"
        ).style.display =
            "block";


        document.getElementById(
            "temperature"
        ).innerText =
            temperature;


        document.getElementById(
            "humidity"
        ).innerText =
            humidity + "%";


        document.getElementById(
            "rain"
        ).innerText =
            rain + " mm";


        document.getElementById(
            "cloud"
        ).innerText =
            cloud + "%";


        document.getElementById(
            "wind"
        ).innerText =
            wind + " km/h";


        document.getElementById(
            "weatherDescription"
        ).innerText =
            weatherDescription(code);


        document.getElementById(
            "weatherTitle"
        ).innerText =
            "🌦️ " + place + " Weather — " +
            date +
            " " +
            time;


    }

    catch(error) {

        console.error(error);

        alert(
            "Could not load weather. Check your internet connection."
        );
    }
}


// ============================================================
// TOGGLE AUTH FORMS (LOGIN / SIGNUP)
// ============================================================

function toggleAuthForm(type) {
    const loginContainer = document.getElementById("login-form-container");
    const signupContainer = document.getElementById("signup-form-container");
    
    if (!loginContainer || !signupContainer) return;
    
    if (type === "login") {
        loginContainer.style.display = loginContainer.style.display === "none" ? "block" : "none";
        signupContainer.style.display = "none";
    } else if (type === "signup") {
        signupContainer.style.display = signupContainer.style.display === "none" ? "block" : "none";
        loginContainer.style.display = "none";
    }
}


// ============================================================
// DEFAULT WEATHER DATE
// ============================================================

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const toast = document.getElementById("pointsToast");

        if (toast && toast.textContent.trim() !== "") {
            toast.style.display = "block";
            toast.classList.add("show");

            setTimeout(() => {
                toast.classList.remove("show");
                toast.style.display = "none";
            }, 3000);
        }

        const dateInput =
            document.getElementById(
                "weatherDate"
            );


        const tomorrow =
            new Date();


        tomorrow.setDate(
            tomorrow.getDate() + 1
        );


        const year =
            tomorrow.getFullYear();


        const month =
            String(
                tomorrow.getMonth() + 1
            ).padStart(2, "0");


        const day =
            String(
                tomorrow.getDate()
            ).padStart(2, "0");


        dateInput.value =
            year +
            "-" +
            month +
            "-" +
            day;

    }
);

</script>


</body>

</html>
