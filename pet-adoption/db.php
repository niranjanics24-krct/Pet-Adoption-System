<?php
// Central Database Connection for Pet Adoption Management System
// MySQLi with utf8mb4 and safe error reporting

$host = "localhost";
$username = "root";
$password = "";
$database = "pet_adoption_db";

// Suppress default fatal mysqli exception to render a helpful diagnostic page if down
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    // If running in CLI mode, echo clear error
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Database connection failed: " . $conn->connect_error . PHP_EOL);
        fwrite(STDERR, "Please ensure XAMPP MySQL service is running on port 3306.\n");
        exit(1);
    }

    // Friendly diagnostic page for web requests
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Issue | Pet Haven</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                background-color: #FAF7F2;
                color: #2D3748;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
            }
            .card {
                background: #FFFFFF;
                max-width: 600px;
                border-radius: 16px;
                padding: 35px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.08);
                border-top: 6px solid #C53030;
            }
            h1 { color: #C53030; margin-top: 0; font-size: 24px; }
            p { line-height: 1.6; color: #4A5568; }
            .code-box {
                background: #EDF2F7;
                padding: 12px 16px;
                border-radius: 8px;
                font-family: monospace;
                font-size: 14px;
                color: #2D3748;
                margin: 15px 0;
            }
            ol { padding-left: 20px; line-height: 1.8; color: #4A5568; }
            .btn {
                display: inline-block;
                background: #2D5A43;
                color: white;
                text-decoration: none;
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 600;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>🐾 Database Connection Notice</h1>
            <p>The application could not connect to MySQL server at <code>localhost</code>.</p>
            <div class="code-box">Error: <?php echo htmlspecialchars($conn->connect_error); ?></div>
            <h3>Quick Fix Instructions:</h3>
            <ol>
                <li>Open the <strong>XAMPP Control Panel</strong>.</li>
                <li>Make sure the <strong>MySQL</strong> module is started (green indicator).</li>
                <li>Verify the database <code>pet_adoption_db</code> exists in <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a>.</li>
                <li>If you haven't run the initial setup yet, visit <a href="database/run_migration.php">Setup / Migration Runner</a>.</li>
            </ol>
            <a href="javascript:location.reload();" class="btn">↻ Retry Connection</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$conn->set_charset("utf8mb4");
