<?php
require_once 'config.php';
require_once 'controller.php';

// Không yêu cầu đăng nhập để xem chi tiết phòng
// Chỉ yêu cầu đăng nhập khi đặt phòng (sẽ kiểm tra trong phần form đặt phòng)

// Cập nhật trạng thái phòng nếu đặt phòng hết hạn
$sql_expired = "UPDATE rooms r
                LEFT JOIN bookings b ON r.id = b.room_id
                SET r.status = 'available'
                WHERE r.status = 'occupied'
                AND (b.check_out < CURDATE() OR b.status = 'cancelled')";
$conn->query($sql_expired);

// Lấy thông tin phòng
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$sql = "SELECT * FROM rooms WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room_result = $stmt->get_result();

if ($room_result->num_rows == 0) {
    die("Phòng không tồn tại.");
}

$room = $room_result->fetch_assoc();
$is_available = $room['status'] === 'available';
$additional_images = !empty($room['additional_images']) ? explode(',', $room['additional_images']) : [];
$amenities = !empty($room['amenities']) ? explode(',', $room['amenities']) : [];

// Xử lý đặt phòng
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_room']) && $is_available) {
    // Kiểm tra đăng nhập trước khi đặt phòng
    if (!isset($_SESSION['user_id'])) {
        $message = '<div class="alert alert-warning">Vui lòng đăng nhập để đặt phòng!</div>';
    } else {
        $check_in = $_POST['check_in_date'];
        $check_out = $_POST['check_out_date'];
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
                    header("Location: booking.php");
                    exit;
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
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết phòng - <?php echo htmlspecialchars($room['room_type']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .room-image { height: 400px; object-fit: cover; }
        .card { margin-bottom: 20px; }
        .carousel-inner img { max-height: 400px; object-fit: cover; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center mb-4">Chi tiết phòng - <?php echo htmlspecialchars($room['room_type']); ?></h2>

        <?php echo isset($message) ? $message : ''; ?>

        <div class="card">
            <div id="roomImageCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="<?php echo htmlspecialchars($room['image_url'] ?? 'images/no-image.jpg'); ?>" class="d-block w-100 room-image" alt="<?php echo htmlspecialchars($room['room_type']); ?>">
                    </div>
                    <?php foreach ($additional_images as $index => $image): ?>
                        <div class="carousel-item<?php echo $index === 0 ? ' active' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($image); ?>" class="d-block w-100 room-image" alt="<?php echo htmlspecialchars($room['room_type']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#roomImageCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#roomImageCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($room['room_type']); ?></h5>
                <p class="card-text">
                    <strong>Giá:</strong> <?php echo number_format($room['price'], 2); ?> USD<br>
                    <br><strong>Status:</strong> <?php echo $is_available ? 'Có sẵn' : 'Không khả dụng'; ?><br>
                    <strong>Đánh giá:</strong> <span class="text-warning">★★★★★</span> (4.5)<br>
                    <br>
                    <strong>Mô tả:</strong> <?php echo htmlspecialchars($room['description'] ?? 'N/A'); ?><br>
                    <strong>Diện tích:</strong> <?php echo htmlspecialchars($room['size'] ?? 'N/A'); ?><br>
                    <strong>Sức chứa:</strong> <?php echo htmlspecialchars($room['capacity'] ?? 'N/A'); ?>
                </p>
                <h5>Tiện ích</h5>
                <ul>
                    <?php foreach ($amenities as $amenity): ?>
                        <li><?php echo htmlspecialchars(trim($amenity)); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Đặt phòng</h5>
                <?php if ($is_available): ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="check_in_date" class="form-label">Ngày nhận</label>
                                    <input type="date" class="form-control" id="check_in_date" name="check_in_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="check_out_date" class="form-label">Ngày trả</label>
                                    <input type="date" class="form-control" id="check_out_date" name="check_out_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="submit" name="book_room" class="btn btn-primary">Đặt phòng</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">Vui lòng <a href="login.php">đăng nhập</a> để đặt phòng.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">Phòng hiện không khả dụng do đã được đặt hoặc đang bảo trì.</div>
                <?php endif; ?>
            </div>
        </div>

        <a href="index.php" class="btn btn-secondary mt-3">Quay lại</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>