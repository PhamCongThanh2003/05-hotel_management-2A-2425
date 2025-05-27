<?php

require_once 'config.php';
require_once 'controller.php';

checkRole(['staff', 'manager']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $phone = $_POST['phone'];
    $points = $_POST['points'];
    
    $stmt = $conn->prepare("INSERT INTO customers (user_id, phone, loyalty_points) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE phone = ?, loyalty_points = ?");
    $stmt->bind_param("isisi", $user_id, $phone, $points, $phone, $points);
    $stmt->execute();
    $success = "Cập nhật thông tin khách hàng thành công!";
}

$customers = $conn->query("SELECT c.*, u.email, u.full_name FROM customers c JOIN users u ON c.user_id = u.id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Quản lý khách hàng</h2>
        <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="user_id" class="form-label">ID Người dùng</label>
                <input type="number" class="form-control" id="user_id" name="user_id" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input type="text" class="form-control" id="phone" name="phone" required>
            </div>
            <div class="mb-3">
                <label for="points" class="form-label">Điểm thân thiết</label>
                <input type="number" class="form-control" id="points" name="points" required>
            </div>
            <button type="submit" class="btn btn-secondary w-100">Cập nhật</button>
        </form>
        <h3 class="mt-4">Danh sách khách hàng</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Họ tên</th>
                    <th>Số điện thoại</th>
                    <th>Điểm thân thiết</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $customers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['full_name']; ?></td>
                        <td><?php echo $row['phone']; ?></td>
                        <td><?php echo $row['loyalty_points']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script src="scripts.js"></script>
</body>
</html>