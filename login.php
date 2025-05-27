<?php

require_once 'config.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = hash('sha256', $_POST['password']);
    
    $stmt = $conn->prepare("SELECT id, role, full_name FROM users WHERE email = ? AND password = ?");
    if ($stmt === false) {
        $error = "Lỗi chuẩn bị truy vấn: " . $conn->error;
    } else {
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            header("Location: index.php");
            exit; // Đảm bảo thoát sau khi chuyển hướng
        } else {
            $error = "Email hoặc mật khẩu không đúng!";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Đăng nhập</h2>
        <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST" onsubmit="return validateLoginForm()">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
        </form>
        <!-- Phần đăng kí -->
    <p class="text-center mt-3">Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
<?php
// Đóng kết nối
$conn->close();
?>