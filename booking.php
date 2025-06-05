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

// Xử lý đặt phòng mới
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_room']) && $_SESSION['role'] == 'customer') {
    $check_in = $_POST['check_in_date'];
    $check_out = $_POST['check_out_date'];
    $room_id = $_POST['room_id'];
    $user_id = $_SESSION['user_id'];

    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $current_date = new DateTime();

    if ($check_out_date <= $check_in_date) {
        $message = '<div class="alert alert-warning">Ngày trả phòng phải sau ngày nhận phòng!</div>';
    } elseif ($check_in_date < $current_date) {
        $message = '<div class="alert alert-warning">Ngày nhận phòng không được là ngày trong quá khứ!</div>';
    } else {
        // Kiểm tra phòng có sẵn trong khoảng thời gian, chỉ tính booking 'confirmed' chưa thanh toán
        $sql_check = "SELECT b.id, b.room_id, b.check_in, b.check_out 
                      FROM bookings b 
                      LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'paid'
                      WHERE b.room_id = ? AND b.status = 'confirmed' AND p.booking_id IS NULL
                      AND ((b.check_in <= ? AND b.check_out >= ?) OR (b.check_in <= ? AND b.check_out >= ?))
                      AND b.check_out > NOW()";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("issss", $room_id, $check_in, $check_in, $check_out, $check_out);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows == 0) {
            $sql_book = "INSERT INTO bookings (user_id, room_id, check_in, check_out, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt_book = $conn->prepare($sql_book);
            $stmt_book->bind_param("iiss", $user_id, $room_id, $check_in, $check_out);
            if ($stmt_book->execute()) {
                $message = '<div class="alert alert-success">Đặt phòng thành công! Vui lòng thanh toán để hoàn tất.</div>';
            } else {
                $message = '<div class="alert alert-danger">Đặt phòng thất bại. Vui lòng thử lại.</div>';
            }
            $stmt_book->close();
        } else {
            $message = '<div class="alert alert-warning">Phòng đã được đặt và đang chờ thanh toán trong khoảng thời gian này.</div>';
        }
        $stmt_check->close();
    }
}

// Xử lý tìm kiếm và lọc (chỉ cho staff)
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all';

// Xử lý hủy đặt phòng
if (isset($_GET['cancel']) && $_GET['cancel'] == 'true') {
    $booking_id = $_GET['id'];
    $sql = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param("ii", $booking_id, $user_id);
    if ($stmt->execute()) {
        $sql_update_room = "UPDATE rooms r JOIN bookings b ON r.id = b.room_id SET r.status = 'available' WHERE b.id = ? AND b.status = 'cancelled'";
        $stmt_update = $conn->prepare($sql_update_room);
        $stmt_update->bind_param("i", $booking_id);
        $stmt_update->execute();
        $stmt_update->close();
    }
    $stmt->close();
    header("Location: booking.php");
    exit;
}

// Xử lý thanh toán
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment']) && $_SESSION['role'] == 'customer') {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $message = '<div class="alert alert-danger">Lỗi: Không tìm thấy thông tin người dùng. Vui lòng đăng nhập lại.</div>';
    } else {
        $booking_id = $_POST['booking_id'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];
        $user_id = $_SESSION['user_id'];

        if ($amount <= 0) {
            $message = '<div class="alert alert-warning">Số tiền phải lớn hơn 0!</div>';
        } else {
            $sql_insert_payment = "INSERT INTO payments (booking_id, user_id, amount, payment_method, status, payment_date) VALUES (?, ?, ?, ?, 'paid', NOW())";
            $stmt_payment = $conn->prepare($sql_insert_payment);
            $stmt_payment->bind_param("iids", $booking_id, $user_id, $amount, $payment_method);
            if ($stmt_payment->execute()) {
                // Cập nhật trạng thái booking thành 'confirmed' và phòng thành 'occupied'
                $sql_update_booking = "UPDATE bookings SET status = 'confirmed' WHERE id = ?";
                $stmt_update_booking = $conn->prepare($sql_update_booking);
                $stmt_update_booking->bind_param("i", $booking_id);
                $stmt_update_booking->execute();
                $stmt_update_booking->close();

                $sql_update_room = "UPDATE rooms r JOIN bookings b ON r.id = b.room_id SET r.status = 'occupied' WHERE b.id = ? AND r.status = 'available'";
                $stmt_update_room = $conn->prepare($sql_update_room);
                $stmt_update_room->bind_param("i", $booking_id);
                $stmt_update_room->execute();
                $stmt_update_room->close();

                $message = '<div class="alert alert-success">Thanh toán thành công! Phòng đã được xác nhận.</div>';
            } else {
                $message = '<div class="alert alert-danger">Thanh toán thất bại. Vui lòng thử lại: ' . $conn->error . '</div>';
            }
            $stmt_payment->close();
        }
    }
}

// Kiểm tra và cập nhật trạng thái phòng hết hạn
$sql_expired = "SELECT b.id, b.room_id, b.check_out 
                FROM bookings b 
                WHERE b.status = 'confirmed' 
                AND b.check_out < NOW() 
                AND EXISTS (SELECT 1 FROM rooms r WHERE r.id = b.room_id AND r.status = 'occupied')";
$stmt_expired = $conn->prepare($sql_expired);
$stmt_expired->execute();
$expired_result = $stmt_expired->get_result();
while ($expired = $expired_result->fetch_assoc()) {
    $sql_update_room = "UPDATE rooms SET status = 'available' WHERE id = ? AND status = 'occupied'";
    $stmt_update = $conn->prepare($sql_update_room);
    $stmt_update->bind_param("i", $expired['room_id']);
    $stmt_update->execute();
    $stmt_update->close();
}
$stmt_expired->close();

// Lấy dữ liệu đặt phòng (lọc theo role)
$user_id = $_SESSION['user_id'];
if ($_SESSION['role'] == 'staff') {
    $sql = "SELECT b.id, b.user_id, c.full_name AS name, r.room_type, b.check_in, b.check_out, b.status 
            FROM bookings b 
            JOIN users c ON b.user_id = c.id 
            JOIN rooms r ON b.room_id = r.id";
    if ($search) {
        $search = "%$search%";
        $sql .= " WHERE c.full_name LIKE ? OR r.room_type LIKE ? OR b.status LIKE ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $search, $search, $search);
    } elseif ($filter_status != 'all') {
        $sql .= " WHERE b.status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $filter_status);
    } else {
        $stmt = $conn->prepare($sql);
    }
} else {
    $sql = "SELECT b.id, b.user_id, c.full_name AS name, r.room_type, b.check_in, b.check_out, b.status, r.price 
            FROM bookings b 
            JOIN users c ON b.user_id = c.id 
            JOIN rooms r ON b.room_id = r.id 
            WHERE b.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

// Thống kê đặt phòng (chỉ cho staff)
if ($_SESSION['role'] == 'staff') {
    $stats_sql = "SELECT status, COUNT(*) as count FROM bookings GROUP BY status";
    $stats_result = $conn->query($stats_sql);
    $stats = [];
    while ($row = $stats_result->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đặt phòng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .table-responsive { margin-top: 20px; }
        .card { margin-bottom: 20px; }
        .chart-container { position: relative; height: 300px; margin-top: 20px; }
        .btn-custom { margin-right: 5px; }
        .modal-content { border-radius: 10px; }
        .payment-form { max-width: 500px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center mb-4">Quản lý đặt phòng</h2>

        <?php echo isset($message) ? $message : ''; ?>

        <!-- Form xác nhận thanh toán (chỉ cho customer với booking chưa thanh toán) -->
        <?php if ($_SESSION['role'] == 'customer'): ?>
            <?php
            $sql_unpaid = "SELECT b.id, b.user_id, r.room_type, r.price, b.check_in, b.check_out, b.status 
                           FROM bookings b 
                           JOIN rooms r ON b.room_id = r.id 
                           WHERE b.user_id = ? 
                           AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.booking_id = b.id AND p.status = 'paid')";
            $stmt_unpaid = $conn->prepare($sql_unpaid);
            $stmt_unpaid->bind_param("i", $user_id);
            $stmt_unpaid->execute();
            $unpaid_result = $stmt_unpaid->get_result();

            if ($unpaid_result->num_rows > 0): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Xác nhận thanh toán</h5>
                        <?php while ($unpaid = $unpaid_result->fetch_assoc()): ?>
                            <div class="payment-form mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Phòng: <?php echo htmlspecialchars($unpaid['room_type']); ?></label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ngày nhận: <?php echo date('d/m/Y', strtotime($unpaid['check_in'])); ?></label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ngày trả: <?php echo date('d/m/Y', strtotime($unpaid['check_out'])); ?></label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái: <?php echo htmlspecialchars($unpaid['status']); ?></label>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="booking_id" value="<?php echo $unpaid['id']; ?>">
                                    <div class="mb-3">
                                        <label for="amount<?php echo $unpaid['id']; ?>" class="form-label">Số tiền (USD)</label>
                                        <input type="number" class="form-control" id="amount<?php echo $unpaid['id']; ?>" name="amount" step="0.01" value="<?php echo number_format($unpaid['price'], 2); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_method<?php echo $unpaid['id']; ?>" class="form-label">Phương thức thanh toán</label>
                                        <select class="form-select" id="payment_method<?php echo $unpaid['id']; ?>" name="payment_method" required>
                                            <option value="credit_card">Thẻ tín dụng</option>
                                            <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                                            <option value="cash">Tiền mặt</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="confirm_payment" class="btn btn-success">Xác nhận thanh toán</button>
                                </form>
                            </div>
                            <hr>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-3">Không có đặt phòng nào cần thanh toán.</div>
            <?php endif;
            $stmt_unpaid->close();
            ?>
        <?php endif; ?>

        <!-- Thống kê đặt phòng (chỉ cho staff) -->
        <?php if ($_SESSION['role'] == 'staff'): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Thống kê đặt phòng</h5>
                    <div class="chart-container">
                        <canvas id="bookingStatsChart"></canvas>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tìm kiếm và lọc (chỉ cho staff) -->
        <?php if ($_SESSION['role'] == 'staff'): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm theo tên hoặc loại phòng..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="filterStatus" onchange="filterBookings()">
                                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Chờ</option>
                                <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary" onclick="filterBookings()">Lọc</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bảng quản lý đặt phòng -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Tên khách hàng</th>
                        <th>Loại phòng</th>
                        <th>Ngày nhận</th>
                        <th>Ngày trả</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['check_in'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['check_out'])); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                                <?php if ($_SESSION['role'] == 'customer' && $row['user_id'] == $_SESSION['user_id'] && $row['status'] == 'pending'): ?>
                                    <a href="?cancel=true&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger btn-custom" onclick="return confirm('Bạn có chắc muốn hủy đặt phòng này?')">Hủy</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <a href="index.php" class="btn btn-secondary mt-3">Quay lại</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($_SESSION['role'] == 'staff'): ?>
                const ctx = document.getElementById('bookingStatsChart').getContext('2d');
                if (ctx) {
                    const bookingStatsChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Chờ', 'Đã xác nhận', 'Đã hủy'],
                            datasets: [{
                                label: 'Số lượng',
                                data: [
                                    <?php echo isset($stats['pending']) ? $stats['pending'] : 0; ?>,
                                    <?php echo isset($stats['confirmed']) ? $stats['confirmed'] : 0; ?>,
                                    <?php echo isset($stats['cancelled']) ? $stats['cancelled'] : 0; ?>
                                ],
                                backgroundColor: ['#ffc107', '#28a745', '#dc3545']
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { position: 'top' },
                                title: { display: true, text: 'Phân bố trạng thái đặt phòng' }
                            },
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                }
            <?php endif; ?>

            function filterBookings() {
                const search = document.getElementById('searchInput').value;
                const filterStatus = document.getElementById('filterStatus').value;
                window.location.href = `booking.php?search=${encodeURIComponent(search)}&filter_status=${encodeURIComponent(filterStatus)}`;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>