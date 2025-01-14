s<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Function to sanitize and validate input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Database credentials and connection
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "login_db"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Handle form submission for adding a comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'])) {
    $comment = sanitize_input($_POST['comment']);

    $stmt = $conn->prepare("INSERT INTO comments (user_id, comment) VALUES (?, ?)");
    if (!$stmt) {
        die("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("is", $user_id, $comment);

    if ($stmt->execute()) {
        $success = "Comment added successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Handle deleting a comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $comment_id = sanitize_input($_POST['comment_id']);

    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        die("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $comment_id, $user_id);

    if ($stmt->execute()) {
        $success = "Comment deleted successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Handle updating a comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $comment_id = sanitize_input($_POST['comment_id']);
    $comment = sanitize_input($_POST['comment']);

    $stmt = $conn->prepare("UPDATE comments SET comment = ? WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        die("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("sii", $comment, $comment_id, $user_id);

    if ($stmt->execute()) {
        $success = "Comment updated successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Retrieve all comments for the logged-in user
$stmt = $conn->prepare("SELECT id, comment, created_at FROM comments WHERE user_id = ? ORDER BY created_at DESC");
if (!$stmt) {
    die("Prepare statement failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$comments = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Comments</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="comment">Add a comment:</label>
            <textarea id="comment" name="comment" required></textarea>
            <br>
            <button type="submit">Submit</button>
        </form>
        <?php
        if (isset($success)) {
            echo "<p class='success'>$success</p>";
        }
        if (isset($error)) {
            echo "<p class='error'>$error</p>";
        }
        ?>
        <h3>Your Comments</h3>
        <?php foreach ($comments as $comment) : ?>
            <div class="comment">
                <p><?php echo htmlspecialchars($comment['comment']); ?></p>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display:inline;">
                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                    <textarea name="comment" required><?php echo htmlspecialchars($comment['comment']); ?></textarea>
                    <button type="submit" name="update">Update</button>
                </form>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display:inline;">
                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                    <button type="submit" name="delete">Delete</button>
                </form>
                <p class="timestamp"><?php echo $comment['created_at']; ?></p>
            </div>
        <?php endforeach; ?>
        <p><a href="blog.php">Back to Welcome</a></p>
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>
