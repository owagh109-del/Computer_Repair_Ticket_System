<?php
/* ============================================================
   COMPUTER REPAIR TICKET SYSTEM
   PHP + MySQL + HTML + CSS
   ============================================================ */

$host = "localhost";
$user = "root";
$pass = "";
$db   = "computer_repair_db";

$conn = mysqli_connect($host, $user, $pass);

if (!$conn) {
    die("Database connection failed.");
}

mysqli_set_charset($conn, "utf8mb4");

$create_db = "CREATE DATABASE IF NOT EXISTS computer_repair_db";
if (!mysqli_query($conn, $create_db)) {
    die("Unable to create database.");
}

mysqli_select_db($conn, $db);

$create_table = "
CREATE TABLE IF NOT EXISTS repair_tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    device_type VARCHAR(50) NOT NULL,
    problem_description TEXT NOT NULL,
    priority VARCHAR(20) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $create_table)) {
    die("Unable to create table.");
}

/* Insert sample data only when table is empty */
$count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM repair_tickets");
$count_row = mysqli_fetch_assoc($count_result);

if ((int)$count_row["total"] === 0) {
    $sample_sql = "
    INSERT INTO repair_tickets
    (customer_name, email, device_type, problem_description, priority, status)
    VALUES
    ('Rahul Patil', 'rahul@example.com', 'Laptop',
     'Laptop is overheating frequently.', 'High', 'Pending'),
    ('Amit Sharma', 'amit@example.com', 'Desktop',
     'Computer does not start properly.', 'Critical', 'In Progress'),
    ('Priya Joshi', 'priya@example.com', 'Printer',
     'Printer is showing paper jam error.', 'Medium', 'Completed'),
    ('Sneha Deshmukh', 'sneha@example.com', 'Monitor',
     'Monitor display is flickering.', 'Low', 'Pending'),
    ('Vikas More', 'vikas@example.com', 'Laptop',
     'Laptop battery is not charging.', 'High', 'In Progress')
    ";
    mysqli_query($conn, $sample_sql);
}

$message = "";
$message_type = "";

/* ADD NEW TICKET */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_ticket"])) {
    $customer_name = trim($_POST["customer_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $device_type = trim($_POST["device_type"] ?? "");
    $problem_description = trim($_POST["problem_description"] ?? "");
    $priority = trim($_POST["priority"] ?? "");

    $allowed_devices = ["Laptop", "Desktop", "Printer", "Monitor", "Other"];
    $allowed_priorities = ["Low", "Medium", "High", "Critical"];

    if ($customer_name === "" || $email === "" || $device_type === "" ||
        $problem_description === "" || $priority === "") {
        $message = "All fields are required.";
        $message_type = "error";
    } elseif (!preg_match("/^[A-Za-z .'-]{2,100}$/", $customer_name)) {
        $message = "Please enter a valid customer name.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } elseif (!in_array($device_type, $allowed_devices, true)) {
        $message = "Invalid device type.";
        $message_type = "error";
    } elseif (!in_array($priority, $allowed_priorities, true)) {
        $message = "Invalid priority.";
        $message_type = "error";
    } elseif (strlen($problem_description) > 1000) {
        $message = "Problem description is too long.";
        $message_type = "error";
    } else {
        $sql = "INSERT INTO repair_tickets
                (customer_name, email, device_type, problem_description, priority, status)
                VALUES (?, ?, ?, ?, ?, 'Pending')";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt, "sssss",
                $customer_name, $email, $device_type,
                $problem_description, $priority
            );

            if (mysqli_stmt_execute($stmt)) {
                $ticket_id = mysqli_insert_id($conn);
                $message = "Ticket #" . $ticket_id . " submitted successfully.";
                $message_type = "success";
            } else {
                $message = "Unable to submit ticket.";
                $message_type = "error";
            }

            mysqli_stmt_close($stmt);
        } else {
            $message = "Unable to process request.";
            $message_type = "error";
        }
    }
}

/* UPDATE TICKET STATUS */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_ticket"])) {
    $ticket_id = (int)($_POST["ticket_id"] ?? 0);
    $status = trim($_POST["status"] ?? "");

    $allowed_statuses = ["Pending", "In Progress", "Completed", "Cancelled"];

    if ($ticket_id <= 0) {
        $message = "Invalid ticket ID.";
        $message_type = "error";
    } elseif (!in_array($status, $allowed_statuses, true)) {
        $message = "Invalid ticket status.";
        $message_type = "error";
    } else {
        $sql = "UPDATE repair_tickets SET status = ? WHERE ticket_id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $status, $ticket_id);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Ticket #" . $ticket_id . " updated successfully.";
                $message_type = "success";
            } else {
                $message = "Unable to update ticket.";
                $message_type = "error";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

/* DELETE TICKET */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_ticket"])) {
    $ticket_id = (int)($_POST["ticket_id"] ?? 0);

    if ($ticket_id <= 0) {
        $message = "Invalid ticket ID.";
        $message_type = "error";
    } else {
        $sql = "DELETE FROM repair_tickets WHERE ticket_id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $ticket_id);

            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $message = "Ticket #" . $ticket_id . " deleted successfully.";
                    $message_type = "success";
                } else {
                    $message = "Ticket not found.";
                    $message_type = "error";
                }
            } else {
                $message = "Unable to delete ticket.";
                $message_type = "error";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

/* SEARCH */
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $sql = "SELECT ticket_id, customer_name, email, device_type,
                   problem_description, priority, status, created_at
            FROM repair_tickets
            WHERE customer_name LIKE ?
               OR email LIKE ?
               OR device_type LIKE ?
               OR problem_description LIKE ?
               OR priority LIKE ?
               OR status LIKE ?
            ORDER BY ticket_id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    $search_value = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt, "ssssss",
        $search_value, $search_value, $search_value,
        $search_value, $search_value, $search_value
    );

    mysqli_stmt_execute($stmt);
    $tickets = mysqli_stmt_get_result($stmt);
} else {
    $sql = "SELECT ticket_id, customer_name, email, device_type,
                   problem_description, priority, status, created_at
            FROM repair_tickets
            ORDER BY ticket_id DESC";

    $tickets = mysqli_query($conn, $sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Repair Ticket System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <div class="container">
        <h1>🛠 Computer Repair Ticket System</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="#add-ticket">Add Ticket</a>
            <a href="#tickets">View Tickets</a>
        </nav>
    </div>
</header>

<main>
<div class="container">

    <?php if ($message !== ""): ?>
        <div class="message <?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <section class="hero">
        <h2>Computer Repair Management</h2>
        <p>Submit, search, update and manage computer repair tickets easily.</p>
    </section>

    <div class="grid">

        <section class="card" id="add-ticket">
            <h2>📝 New Repair Ticket</h2>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name"
                           placeholder="Enter customer name" required maxlength="100"
                           pattern="[A-Za-z .'-]{2,100}" title="Enter a valid name">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           placeholder="example@gmail.com" required maxlength="150">
                </div>

                <div class="form-group">
                    <label for="device_type">Device Type</label>
                    <select id="device_type" name="device_type" required>
                        <option value="">Select Device</option>
                        <option value="Laptop">Laptop</option>
                        <option value="Desktop">Desktop</option>
                        <option value="Printer">Printer</option>
                        <option value="Monitor">Monitor</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="problem_description">Problem Description</label>
                    <textarea id="problem_description" name="problem_description"
                              placeholder="Describe the problem..." required
                              maxlength="1000"></textarea>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" required>
                        <option value="">Select Priority</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>

                <button type="submit" name="add_ticket" class="btn btn-green">
                    Submit Ticket
                </button>
            </form>
        </section>

        <section class="card" id="tickets">
            <h2>🔎 Search Repair Tickets</h2>

            <form method="GET" action="">
                <div class="search-container">
                    <input type="text" name="search"
                           placeholder="Search customer, device, priority, status..."
                           value="<?php echo htmlspecialchars($search); ?>"
                           maxlength="100">

                    <button type="submit" class="btn">Search</button>

                    <?php if ($search !== ""): ?>
                        <a href="index.php" class="btn">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Device</th>
                        <th>Problem</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if ($tickets && mysqli_num_rows($tickets) > 0): ?>

                        <?php while ($row = mysqli_fetch_assoc($tickets)): ?>
                            <tr>
                                <td><strong><?php echo (int)$row["ticket_id"]; ?></strong></td>

                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($row["customer_name"]); ?>
                                    </strong>
                                    <br>
                                    <small>
                                        <?php echo htmlspecialchars($row["email"]); ?>
                                    </small>
                                </td>

                                <td><?php echo htmlspecialchars($row["device_type"]); ?></td>

                                <td class="problem">
                                    <?php echo htmlspecialchars($row["problem_description"]); ?>
                                </td>

                                <td>
                                    <span class="badge <?php echo strtolower($row["priority"]); ?>">
                                        <?php echo htmlspecialchars($row["priority"]); ?>
                                    </span>
                                </td>

                                <td>
                                    <strong class="<?php echo strtolower(str_replace(' ', '-', $row["status"])); ?>">
                                        <?php echo htmlspecialchars($row["status"]); ?>
                                    </strong>
                                </td>

                                <td><?php echo htmlspecialchars($row["created_at"]); ?></td>

                                <td>
                                    <div class="actions">

                                        <form method="POST" action="">
                                            <input type="hidden" name="ticket_id"
                                                   value="<?php echo (int)$row["ticket_id"]; ?>">

                                            <select name="status" required>
                                                <?php
                                                $statuses = ["Pending", "In Progress", "Completed", "Cancelled"];
                                                foreach ($statuses as $option):
                                                ?>
                                                    <option value="<?php echo $option; ?>"
                                                        <?php echo ($row["status"] === $option) ? "selected" : ""; ?>>
                                                        <?php echo $option; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button type="submit" name="update_ticket"
                                                    class="small-btn update-btn">
                                                Update
                                            </button>
                                        </form>

                                        <form method="POST" action=""
                                              onsubmit="return confirm('Are you sure you want to delete this ticket?');">
                                            <input type="hidden" name="ticket_id"
                                                   value="<?php echo (int)$row["ticket_id"]; ?>">

                                            <button type="submit" name="delete_ticket"
                                                    class="small-btn delete-btn">
                                                Delete
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="empty">
                                No repair tickets found.
                            </td>
                        </tr>

                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
</main>

<footer>
    <p>Computer Repair Ticket System | DBMS Unit 4 Project</p>
    <p>PHP + MySQL + HTML + CSS</p>
</footer>

</body>
</html>

<?php mysqli_close($conn); ?>
