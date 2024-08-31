<?php
$db_file = './ssh.db';
if (!file_exists($db_file)) {
    touch($db_file);
}
chmod($db_file, 0666);
$db = new SQLite3($db_file);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $hostname = SQLite3::escapeString($_POST['hostname']);
    $username = SQLite3::escapeString($_POST['username']);
    $password = SQLite3::escapeString($_POST['password']);
    $port = intval($_POST['port']);
    $db->exec("INSERT INTO ssh_connections (hostname, username, password, port) VALUES ('$hostname', '$username', '$password', $port)");
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->exec("DELETE FROM ssh_connections WHERE id = $id");
}
$per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $per_page;
$total_results = $db->querySingle('SELECT COUNT(*) FROM ssh_connections');
$total_pages = ceil($total_results / $per_page);
$results = $db->query("SELECT * FROM ssh_connections ORDER BY added_date DESC LIMIT $per_page OFFSET $offset");
$logs = $db->query('SELECT * FROM log ORDER BY timestamp DESC LIMIT 10');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSH Connection Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h1, h2 {
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #2c3e50;
            color: #fff;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .success { color: #27ae60; }
        .failure { color: #c0392b; }
        form {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        input[type="text"], input[type="password"], input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #2c3e50;
            color: #fff;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
        }
        input[type="submit"]:hover {
            background-color: #34495e;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            color: #2c3e50;
            padding: 8px 16px;
            text-decoration: none;
            transition: background-color .3s;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        .pagination a.active {
            background-color: #2c3e50;
            color: white;
            border: 1px solid #2c3e50;
        }
        .pagination a:hover:not(.active) {background-color: #ddd;}
    </style>
</head>
<body>
    <h1>SSH Connection Manager</h1>
    
    <h2>Add New SSH Connection</h2>
    <form method="post">
        <input type="text" name="hostname" placeholder="Hostname" required>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="number" name="port" placeholder="Port" value="22" required>
        <input type="submit" name="add" value="Add Connection">
    </form>

    <h2>SSH Connections</h2>
    <table>
        <tr>
            <th>Hostname</th>
            <th>Username</th>
            <th>Port</th>
            <th>Added Date</th>
            <th>Last Success</th>
            <th>Last Failure</th>
            <th>Failure Count</th>
            <th>Days Alive</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)) : ?>
            <tr>
                <td><?= htmlspecialchars($row['hostname']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= $row['port'] ?></td>
                <td><?= $row['added_date'] ?></td>
                <td><?= $row['last_success'] ?></td>
                <td><?= $row['last_failure'] ?></td>
                <td><?= $row['failure_count'] ?></td>
                <td><?= round((time() - strtotime($row['added_date'])) / 86400) ?></td>
                <td class="<?= $row['last_success'] > $row['last_failure'] ? 'success' : 'failure' ?>">
                    <?= $row['last_success'] > $row['last_failure'] ? 'Success' : 'Failure' ?>
                </td>
                <td><a href="?delete=<?= $row['id'] ?>&page=<?= $page ?>" onclick="return confirm('Are you sure you want to delete this connection?')">Delete</a></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>" <?= $i == $page ? 'class="active"' : '' ?>><?= $i ?></a>
        <?php endfor; ?>
    </div>

    <h2>Recent Logs</h2>
    <table>
        <tr>
            <th>Timestamp</th>
            <th>Level</th>
            <th>Message</th>
        </tr>
        <?php while ($log = $logs->fetchArray(SQLITE3_ASSOC)) : ?>
            <tr>
                <td><?= $log['timestamp'] ?></td>
                <td><?= $log['level'] ?></td>
                <td><?= htmlspecialchars($log['message']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
