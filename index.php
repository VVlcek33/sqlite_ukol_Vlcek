<?php
session_start();
require_once 'init.php';

// Handle messages
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $_SESSION['message'] = 'Field cannot be empty.';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO interests (name) VALUES (?)");
                $stmt->execute([$name]);
                $_SESSION['message'] = 'Interest was added.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // UNIQUE constraint failed
                    $_SESSION['message'] = 'This interest already exists.';
                } else {
                    $_SESSION['message'] = 'An error occurred.';
                }
            }
        }
        header("Location: index.php");
        exit;
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $_SESSION['message'] = 'Field cannot be empty.';
        } else {
            try {
                $stmt = $db->prepare("UPDATE interests SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                $_SESSION['message'] = 'Interest was updated.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['message'] = 'This interest already exists.';
                } else {
                    $_SESSION['message'] = 'An error occurred.';
                }
            }
        }
        header("Location: index.php");
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $stmt = $db->prepare("DELETE FROM interests WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = 'Interest was deleted.';
        header("Location: index.php");
        exit;
    }
}

// Get interests
$stmt = $db->query("SELECT * FROM interests ORDER BY name");
$interests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if editing
$editId = $_GET['edit'] ?? null;
$editInterest = null;
if ($editId) {
    foreach ($interests as $interest) {
        if ($interest['id'] == $editId) {
            $editInterest = $interest;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Interests Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Manage Interests</h1>

    <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($editInterest): ?>
        <h2>Edit Interest</h2>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $editInterest['id']; ?>">
            <input type="text" name="name" value="<?php echo htmlspecialchars($editInterest['name']); ?>" required>
            <button type="submit">Update</button>
            <a href="index.php">Cancel</a>
        </form>
    <?php else: ?>
        <h2>Add New Interest</h2>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <input type="text" name="name" placeholder="Enter interest" required>
            <button type="submit">Add</button>
        </form>
    <?php endif; ?>

    <h2>Interests List</h2>
    <?php if (empty($interests)): ?>
        <p>No interests yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($interests as $interest): ?>
                <li>
                    <?php echo htmlspecialchars($interest['name']); ?>
                    <a href="?edit=<?php echo $interest['id']; ?>">Edit</a>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $interest['id']; ?>">
                        <button type="submit" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>