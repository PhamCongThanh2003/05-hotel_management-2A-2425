<?php
require_once 'config.php';
require_once 'controller.php';

checkRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = hash('sha256', $_POST['password']);
    $role = $_POST['role'];
    $full_name = $_POST['full_name'];
    
    $stmt = $conn->prepare("INSERT INTO users (email, password, role, full_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $password, $role, $full_name);
    $stmt->execute();
    $success = "Thêm người dùng thành công!";
}

$users = $conn->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu hình hệ thống</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Cấu hình hệ thống</h2>
        <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Vai trò</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="customer">Khách hàng</option>
                    <option value="staff">Nhân viên lễ tân</option>
                    <option value="manager">Quản lý</option>
                    <option value="admin">Quản trị</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="full_name" class="form-label">Họ tên</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Thêm người dùng</button>
        </form>
        <h3 class="mt-4">Danh sách người dùng</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Họ tên</th>
                    <th>Vai trò</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['full_name']; ?></td>
                        <td><?php echo $row['role']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script src="scripts.js"></script>
</body>
</html>