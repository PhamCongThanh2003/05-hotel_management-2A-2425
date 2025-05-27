<?php
require_once 'config.php';
require_once 'controller.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

checkRole(['customer', 'staff']);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Xử lý thêm thanh toán (chỉ cho customer)
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_payment']) && $_SESSION['role'] == 'customer') {
    $booking_id = $_POST['booking_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];

    // Kiểm tra booking thuộc về customer và đã confirmed
    $sql_check = "SELECT b.id, b.room_id, r.price, b.status 
                  FROM bookings b 
                  JOIN rooms r ON b.room_id = r.id 
                  WHERE b.id = ? AND b.user_id = ? AND b.status = 'confirmed'";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $booking = $result_check->fetch_assoc();
        if ($amount >= $booking['price']) {
            $sql_insert = "INSERT INTO payments (booking_id, user_id, amount, payment_method, status) 
                           VALUES (?, ?, ?, ?, 'paid')";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iids", $booking_id, $_SESSION['user_id'], $amount, $payment_method);
            if ($stmt_insert->execute()) {
                $message = '<div class="alert alert-success">Thanh toán thành công!</div>';
            } else {
                $message = '<div class="alert alert-danger">Thanh toán thất bại. Vui lòng thử lại.</div>';
            }
            $stmt_insert->close();
        } else {
            $message = '<div class="alert alert-warning">Số tiền phải bằng hoặc lớn hơn giá phòng (' . number_format($booking['price'], 2) . ' USD)!</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Đặt phòng không hợp lệ hoặc chưa được xác nhận!</div>';
    }
    $stmt_check->close();
}

// Xử lý cập nhật trạng thái thanh toán (chỉ cho staff)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status']) && $_SESSION['role'] == 'staff') {
    $payment_id = $_POST['payment_id'];
    $status = $_POST['status'];
    $sql_update = "UPDATE payments SET status = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $status, $payment_id);
    if ($stmt_update->execute()) {
        $message = '<div class="alert alert-success">Cập nhật trạng thái thành công!</div>';
    } else {
        $message = '<div class="alert alert-danger">Cập nhật thất bại. Vui lòng thử lại.</div>';
    }
    $stmt_update->close();
}

// Lấy dữ liệu thanh toán (phân biệt theo role)
$user_id = $_SESSION['user_id'];
if ($_SESSION['role'] == 'staff') {
    $sql = "SELECT p.id, p.booking_id, u.full_name AS name, r.room_type, p.amount, p.payment_date, p.status, p.payment_method 
            FROM payments p 
            JOIN bookings b ON p.booking_id = b.id 
            JOIN users u ON p.user_id = u.id 
            JOIN rooms r ON b.room_id = r.id";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT p.id, p.booking_id, u.full_name AS name, r.room_type, p.amount, p.payment_date, p.status, p.payment_method 
            FROM payments p 
            JOIN bookings b ON p.booking_id = b.id 
            JOIN users u ON p.user_id = u.id 
            JOIN rooms r ON b.room_id = r.id 
            WHERE p.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

// Lấy danh sách booking confirmed để customer chọn thanh toán
$booking_sql = "SELECT b.id, r.room_type, r.price 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.id 
                WHERE b.user_id = ? AND b.status = 'confirmed' 
                AND b.id NOT IN (SELECT booking_id FROM payments WHERE status = 'paid')";
$stmt_booking = $conn->prepare($booking_sql);
$stmt_booking->bind_param("i", $user_id);
$stmt_booking->execute();
$booking_result = $stmt_booking->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thanh toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-responsive { margin-top: 20px; }
        .card { margin-bottom: 20px; }
        .modal-content { border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center mb-4">Quản lý thanh toán</h2>

        <?php echo isset($message) ? $message : ''; ?>

        <!-- Form thêm thanh toán (chỉ cho customer) -->
        <?php if ($_SESSION['role'] == 'customer' && $booking_result->num_rows > 0): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Thêm thanh toán</h5>
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="booking_id" class="form-label">Chọn đặt phòng</label>
                                <select class="form-select" id="booking_id" name="booking_id" required>
                                    <?php while ($booking = $booking_result->fetch_assoc()): ?>
                                        <option value="<?php echo $booking['id']; ?>">
                                            <?php echo htmlspecialchars($booking['room_type']) . ' - ' . number_format($booking['price'], 2) . ' USD'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="amount" class="form-label">Số tiền (USD)</label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required min="0">
                            </div>
                            <div class="col-md-4">
                                <label for="payment_method" class="form-label">Phương thức thanh toán</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="Cash">Tiền mặt</option>
                                    <option value="Credit Card">Thẻ tín dụng</option>
                                    <option value="Bank Transfer">Chuyển khoản</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_payment" class="btn btn-primary">Thanh toán</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($_SESSION['role'] == 'customer' && $booking_result->num_rows == 0): ?>
            <div class="alert alert-warning">Không có đặt phòng nào cần thanh toán!</div>
        <?php endif; ?>

        <!-- Tìm kiếm và lọc (chỉ cho staff) -->
        <?php if ($_SESSION['role'] == 'staff'): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm theo tên khách..." onkeyup="filterPayments()">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="filterStatus" onchange="filterPayments()">
                                <option value="all">Tất cả trạng thái</option>
                                <option value="pending">Chờ thanh toán</option>
                                <option value="paid">Đã thanh toán</option>
                                <option value="failed">Thất bại</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bảng quản lý thanh toán -->
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="paymentTable">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Tên khách hàng</th>
                        <th>Loại phòng</th>
                        <th>Số tiền</th>
                        <th>Ngày thanh toán</th>
                        <th>Trạng thái</th>
                        <th>Phương thức</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                            <td><?php echo number_format($row['amount'], 2); ?> USD</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo htmlspecialchars($row['payment_method'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($_SESSION['role'] == 'staff'): ?>
                                    <button class="btn btn-sm btn-warning btn-custom" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">Cập nhật</button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal cập nhật trạng thái (chỉ cho staff) -->
                        <?php if ($_SESSION['role'] == 'staff'): ?>
                            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?php echo $row['id']; ?>">Cập nhật trạng thái</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST">
                                                <input type="hidden" name="payment_id" value="<?php echo $row['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="status<?php echo $row['id']; ?>" class="form-label">Trạng thái</label>
                                                    <select class="form-select" id="status<?php echo $row['id']; ?>" name="status" required>
                                                        <option value="pending" <?php echo $row['status'] == 'pending' ? 'selected' : ''; ?>>Chờ thanh toán</option>
                                                        <option value="paid" <?php echo $row['status'] == 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                                                        <option value="failed" <?php echo $row['status'] == 'failed' ? 'selected' : ''; ?>>Thất bại</option>
                                                    </select>
                                                </div>
                                                <button type="submit" name="update_status" class="btn btn-primary">Lưu thay đổi</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <a href="index.php" class="btn btn-secondary mt-3">Quay lại</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterPayments() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const filterStatus = document.getElementById('filterStatus').value;
            const rows = document.getElementById('paymentTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const name = row.cells[1].textContent.toLowerCase();
                const status = row.cells[5].textContent;
                const show = (name.includes(search) || search === '') && 
                            (filterStatus === 'all' || status === filterStatus);
                row.style.display = show ? '' : 'none';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>