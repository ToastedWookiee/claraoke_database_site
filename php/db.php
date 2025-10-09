<?php
// Database configuration template
// Setup this file to get your database connection information
// such as grabbing from Docker secrets or environment variables

// Enter values or how to get said values, ex: ENVVARs or Docker secrets
$db_host = 'INSERT YOUR DB HOST HERE';
$db_name = 'INSERT YOUR DB NAME HERE';
$db_user = 'INSERT YOUR DB USER HERE';
$db_pass = 'INSERT YOUR DB PASSWORD HERE';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_PERSISTENT => true, // Enable persistent connections
];

// Optional fallback reconnect logic
function get_pdo(): PDO
{
  static $pdo = null;
  global $dsn, $db_user, $db_pass, $options;

  if ($pdo instanceof PDO) {
    try {
      // Test existing connection
      $pdo->query('SELECT 1');
      return $pdo;
    } catch (PDOException $e) {
      // fall through to reconnect
    }
  }

  // Attempt connection (with fallback)
  try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
  } catch (PDOException $e) {
    error_log('Initial PDO connection failed, retrying: ' . $e->getMessage());
    usleep(100000); // wait 100ms
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
  }

  return $pdo;
}

// Optionally return PDO right away if you want `$config` to be a PDO
return get_pdo();

// Or return an array for flexibility
// return [
//     'dsn'     => $dsn,
//     'user'    => $db_user,
//     'pass'    => $db_pass,
//     'options' => $options,
//     'connect' => fn() => get_pdo(),
// ];

?>
