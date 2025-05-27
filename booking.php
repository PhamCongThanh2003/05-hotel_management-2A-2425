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
    if ($check_out_date <= $check_in_date) {
        $message = '<div class="alert alert-warning">Ngày trả phòng phải sau ngày nhận phòng!</div>';
    } else {
        $sql_check = "SELECT * FROM bookings WHERE room_id = ? AND status != 'cancelled' 
                      AND ((check_in <= ? AND check_out >= ?) OR (check_in <= ? AND check_out >= ?))";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("issss", $room_id, $check_in, $check_in, $check_out, $check_out);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows == 0) {
            $sql_book = "INSERT INTO bookings (user_id, room_id, check_in, check_out, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt_book = $conn->prepare($sql_book);
            $stmt_book->bind_param("iiss", $user_id, $room_id, $check_in, $check_out);
            if ($stmt_book->execute()) {
                $sql_update_room = "UPDATE rooms SET status = 'occupied' WHERE id = ? AND status = 'available'";
                $stmt_update = $conn->prepare($sql_update_room);
                $stmt_update->bind_param("i", $room_id);
                $stmt_update->execute();
                $stmt_update->close();
                $message = '<div class="alert alert-success">Đặt phòng thành công! Đang chờ xác nhận.</div>';
            } else {
                $message = '<div class="alert alert-danger">Đặt phòng thất bại. Vui lòng thử lại.</div>';
            }
            $stmt_book->close();
        } else {
            $message = '<div class="alert alert-warning">Phòng không còn trống trong khoảng thời gian này.</div>';
        }
        $stmt_check->close();
    }
}

// Lấy danh sách phòng để hiển thị trong form
$room_sql = "SELECT * FROM rooms WHERE status = 'available'";
$room_result = $conn->query($room_sql);

// Xử lý tìm kiếm và lọc (chỉ cho staff)
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all';

// Xử lý cập nhật trạng thái (chỉ cho staff)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status']) && $_SESSION['role'] == 'staff') {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];
    $sql = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $booking_id);
    $stmt->execute();
    $stmt->close();
    header("Location: booking.php");
    exit;
}

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

// Lấy dữ liệu đặt phòng (lọc theo role)
$user_id = $_SESSION['user_id'];
if ($_SESSION['role'] == 'staff') {
    // Staff: Xem tất cả đặt phòng
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
    // Customer: Chỉ xem đặt phòng của họ
    $sql = "SELECT b.id, b.user_id, c.full_name AS name, r.room_type, b.check_in, b.check_out, b.status 
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
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center mb-4">Quản lý đặt phòng</h2>

        <?php echo isset($message) ? $message : ''; ?>

        <!-- Form đặt phòng mới (chỉ cho customer) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'customer'): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Chọn phòng để đặt</h5>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php
                $room_result->data_seek(0);
                while ($room = $room_result->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($room['image_url'] ?? 'images/no-image.jpg'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($room['room_type']); ?>" style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($room['room_type']); ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">Giá: <?php echo number_format($room['price'], 2); ?> USD</small><br>
                                    <span class="text-warning">&#9733;&#9733;&#9733;&#9733;&#9733;</span> (4.5) <!-- Giả lập đánh giá sao -->
                                    <br><small class="text-muted">Mô tả: Phòng thoải mái với view đẹp.</small>
                                </p>
                                <a href="room_detail.php?room_id=<?php echo $room['id']; ?>" class="btn btn-primary">Xem chi tiết</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php if ($room_result->num_rows == 0): ?>
                <div class="alert alert-warning mt-3">Hiện tại không có phòng nào khả dụng để đặt.</div>
            <?php endif; ?>
        </div>
    </div>
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
                                <?php if ($_SESSION['role'] == 'staff'): ?>
                                    <button class="btn btn-sm btn-warning btn-custom" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">Cập nhật</button>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] == 'customer' && $row['user_id'] == $_SESSION['user_id'] && $row['status'] == 'pending'): ?>
                                    <a href="?cancel=true&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger btn-custom" onclick="return confirm('Bạn có chắc muốn hủy đặt phòng này?')">Hủy</a>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] == 'customer' && $row['user_id'] == $_SESSION['user_id'] && $row['status'] == 'confirmed'): ?>
                                    <?php
                                    $sql_check_payment = "SELECT COUNT(*) as paid_count FROM payments WHERE booking_id = ? AND status = 'paid'";
                                    $stmt_check_payment = $conn->prepare($sql_check_payment);
                                    $stmt_check_payment->bind_param("i", $row['id']);
                                    $stmt_check_payment->execute();
                                    $result_payment = $stmt_check_payment->get_result();
                                    $payment_data = $result_payment->fetch_assoc();
                                    $stmt_check_payment->close();

                                    if ($payment_data['paid_count'] == 0): ?>
                                        <a href="payment.php?booking_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary btn-custom">Thanh toán</a>
                                    <?php endif; ?>
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
                                                <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="status<?php echo $row['id']; ?>" class="form-label">Trạng thái</label>
                                                    <select class="form-select" id="status<?php echo $row['id']; ?>" name="status" required>
                                                        <option value="pending" <?php echo $row['status'] == 'pending' ? 'selected' : ''; ?>>Chờ</option>
                                                        <option value="confirmed" <?php echo $row['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                                        <option value="cancelled" <?php echo $row['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
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
        <?php if ($_SESSION['role'] == 'staff'): ?>
            const ctx = document.getElementById('bookingStatsChart').getContext('2d');
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
        <?php endif; ?>

        function filterBookings() {
            const search = document.getElementById('searchInput').value;
            const filterStatus = document.getElementById('filterStatus').value;
            window.location.href = `booking.php?search=${encodeURIComponent(search)}&filter_status=${encodeURIComponent(filterStatus)}`;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>