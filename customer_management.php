<?php
require_once 'config.php';
require_once 'controller.php';

checkRole(['staff', 'manager']);

$customer = null; // Khởi tạo biến $customer mặc định là null

// Xử lý cập nhật hoặc thêm mới
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $phone = $_POST['phone'];
    $points = $_POST['points'];

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Số điện thoại phải là 10 chữ số!";
    } elseif ($points < 0) {
        $error = "Điểm thân thiết không được âm!";
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (user_id, phone, loyalty_points) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE phone = ?, loyalty_points = ?");
        $stmt->bind_param("isisi", $user_id, $phone, $points, $phone, $points);
        $stmt->execute();
        $success = "Cập nhật thông tin khách hàng thành công!";
    }
}

// Xử lý chế độ chỉnh sửa
if (isset($_GET['edit']) && isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $stmt = $conn->prepare("SELECT c.*, u.email, u.full_name FROM customers c JOIN users u ON c.user_id = u.id WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc(); // Gán thông tin khách hàng vào $customer
    if (!$customer) {
        $error = "Khách hàng không tồn tại!";
    }
}

// Xử lý xóa khách hàng
if (isset($_GET['delete']) && isset($_GET['user_id']) && $_SESSION['role'] == 'manager') {
    $user_id = $_GET['user_id'];
    $stmt = $conn->prepare("DELETE FROM customers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: customer_management.php");
    exit;
}

// Lấy danh sách khách hàng
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT c.*, u.email, u.full_name FROM customers c JOIN users u ON c.user_id = u.id WHERE u.role = 'customer'";
if ($search) {
    $search = "%$search%";
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $customers = $stmt->get_result();
} else {
    $customers = $conn->query($sql);
}
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
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm theo tên hoặc email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary" onclick="searchCustomers()">Tìm kiếm</button>
                    </div>
                </div>
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo isset($customer) && $customer ? $customer['user_id'] : (isset($_POST['user_id']) ? $_POST['user_id'] : ''); ?>">
            <div class="mb-3">
                <label for="user_id" class="form-label">ID Người dùng</label>
                <input type="number" class="form-control" id="user_id" name="user_id" value="<?php echo isset($customer) && $customer ? $customer['user_id'] : (isset($_POST['user_id']) ? $_POST['user_id'] : ''); ?>" <?php echo isset($customer) || isset($_GET['edit']) ? 'readonly' : 'required'; ?>>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($customer) && $customer ? $customer['phone'] : (isset($_POST['phone']) ? $_POST['phone'] : ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="points" class="form-label">Điểm thân thiết</label>
                <input type="number" class="form-control" id="points" name="points" value="<?php echo isset($customer) && $customer ? $customer['loyalty_points'] : (isset($_POST['points']) ? $_POST['points'] : ''); ?>" required>
            </div>
            <button type="submit" class="btn btn-secondary w-100"><?php echo isset($customer) || isset($_GET['edit']) ? 'Cập nhật' : 'Cập nhật'; ?></button>
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
                    <th>Hành động</th>
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
                        <td>
                            <a href="?edit=true&user_id=<?php echo $row['user_id']; ?>" class="btn btn-warning btn-sm">Sửa</a>
                            <?php if ($_SESSION['role'] == 'manager'): ?>
                                <a href="?delete=true&user_id=<?php echo $row['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa khách hàng này?')">Xóa</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script src="scripts.js"></script>
    <script>
        function searchCustomers() {
            const search = document.getElementById('searchInput').value;
            window.location.href = `customer_management.php?search=${encodeURIComponent(search)}`;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>