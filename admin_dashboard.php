
<?php
// admin_dashboard.php - Administrative Module and RBAC
declare(strict_types=1);

require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

// Check whether the user is authenticated.
if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true
) {
    log_security_event('UNAUTHORIZED_ADMIN_ACCESS', [
        'action' => 'Access without authentication'
    ]);

    header('Location: login.php');
    exit();
}

// Check whether the authenticated user is an administrator.
if (($_SESSION['role'] ?? '') !== 'admin') {

    log_security_event('ACCESS_DENIED', [
        'action' => 'Attempted to access admin dashboard',
        'reason' => 'Insufficient privileges'
    ]);

    http_response_code(403);
    exit('Access denied. Administrator privileges required.');
}

// Record successful admin dashboard access.
log_security_event('ADMIN_DASHBOARD_ACCESS', [
    'username' => $_SESSION['username'] ?? 'Unknown',
    'action' => 'Admin dashboard opened'
]);

// Retrieve users for administrative viewing.
$stmt = $pdo->query(
    "SELECT id, username, email, role, created_at
     FROM users
     ORDER BY id DESC"
);

$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #1e293b;
        }

        .welcome {
            color: #64748b;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }

        th {
            background: #2563eb;
            color: white;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        .role {
            font-weight: bold;
        }

        .logout {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .logout:hover {
            background: #b91c1c;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Administrative Dashboard</h1>

    <p class="welcome">
        Welcome, <?= htmlspecialchars(
            $_SESSION['username'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>.

        You are logged in as an administrator.
    </p>

    <h2>User Management</h2>

    <p>Registered users in the system:</p>

    <table>

        <thead>

            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Registration Date</th>
            </tr>

        </thead>

        <tbody>

            <?php foreach ($users as $user): ?>

                <tr>

                    <td><?= (int)$user['id'] ?></td>

                    <td><?= htmlspecialchars(
                        $user['username'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></td>

                    <td><?= htmlspecialchars(
                        $user['email'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></td>

                    <td class="role">
                        <?= htmlspecialchars(
                            $user['role'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>

                    <td><?= htmlspecialchars(
                        $user['created_at'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

    <a href="logout.php" class="logout">Logout</a>

</div>

</body>
</html>
