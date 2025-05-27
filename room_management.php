<?php
require_once 'config.php';
require_once 'controller.php';

checkRole(['staff', 'manager']);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Xử lý tìm kiếm và lọc
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all';

// Xử lý cập nhật phòng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_room'])) {
    $room_id = $_POST['room_id'];
    $room_type = $_POST['room_type'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $sql = "UPDATE rooms SET room_type = ?, price = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsi", $room_type, $price, $status, $room_id);
    $stmt->execute();
    $stmt->close();
    header("Location: room_management.php");
    exit;
}

// Xử lý xóa phòng
if (isset($_GET['delete']) && $_GET['delete'] == 'true') {
    $room_id = $_GET['id'];
    $sql = "DELETE FROM rooms WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $stmt->close();
    header("Location: room_management.php");
    exit;
}

// Lấy dữ liệu phòng
$sql = "SELECT * FROM rooms";
if ($search) {
    $search = "%$search%";
    $sql .= " WHERE room_type LIKE ? OR status LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search, $search);
} elseif ($filter_status != 'all') {
    $sql .= " WHERE status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $filter_status);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();

// Thống kê công suất
$stats_sql = "SELECT status, COUNT(*) as count FROM rooms GROUP BY status";
$stats_result = $conn->query($stats_sql);
$stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phòng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .table-responsive {
            margin-top: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        .btn-custom {
            margin-right: 5px;
        }
        .modal-content {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center mb-4">Quản lý phòng</h2>

        <!-- Thống kê công suất -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Thống kê công suất phòng</h5>
                <div class="chart-container">
                    <canvas id="roomStatsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tìm kiếm và lọc -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm theo loại hoặc trạng thái..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="filterStatus" onchange="filterRooms()">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                            <option value="available" <?php echo $filter_status == 'available' ? 'selected' : ''; ?>>Trống</option>
                            <option value="occupied" <?php echo $filter_status == 'occupied' ? 'selected' : ''; ?>>Đã đặt</option>
                            <option value="maintenance" <?php echo $filter_status == 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary" onclick="filterRooms()">Lọc</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng quản lý phòng -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Loại phòng</th>
                        <th>Giá (USD)</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                            <td><?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-custom" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">Sửa</button>
                                <a href="?delete=true&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger btn-custom" onclick="return confirm('Bạn có chắc muốn xóa phòng này?')">Xóa</a>
                            </td>
                        </tr>

                        <!-- Modal sửa phòng -->
                        <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editModalLabel<?php echo $row['id']; ?>">Sửa thông tin phòng</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="room_id" value="<?php echo $row['id']; ?>">
                                            <div class="mb-3">
                                                <label for="room_type<?php echo $row['id']; ?>" class="form-label">Loại phòng</label>
                                                <input type="text" class="form-control" id="room_type<?php echo $row['id']; ?>" name="room_type" value="<?php echo htmlspecialchars($row['room_type']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="price<?php echo $row['id']; ?>" class="form-label">Giá (USD)</label>
                                                <input type="number" step="0.01" class="form-control" id="price<?php echo $row['id']; ?>" name="price" value="<?php echo $row['price']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="status<?php echo $row['id']; ?>" class="form-label">Trạng thái</label>
                                                <select class="form-select" id="status<?php echo $row['id']; ?>" name="status" required>
                                                    <option value="available" <?php echo $row['status'] == 'available' ? 'selected' : ''; ?>>Trống</option>
                                                    <option value="occupied" <?php echo $row['status'] == 'occupied' ? 'selected' : ''; ?>>Đã đặt</option>
                                                    <option value="maintenance" <?php echo $row['status'] == 'maintenance' ? 'selected' : ''; ?>>Bảo trì</option>
                                                </select>
                                            </div>
                                            <button type="submit" name="update_room" class="btn btn-primary">Lưu thay đổi</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <a href="index.php" class="btn btn-secondary mt-3">Quay lại</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Biểu đồ thống kê
        const ctx = document.getElementById('roomStatsChart').getContext('2d');
        const roomStatsChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Trống', 'Đã đặt', 'Bảo trì'],
                datasets: [{
                    data: [
                        <?php echo isset($stats['available']) ? $stats['available'] : 0; ?>,
                        <?php echo isset($stats['occupied']) ? $stats['occupied'] : 0; ?>,
                        <?php echo isset($stats['maintenance']) ? $stats['maintenance'] : 0; ?>
                    ],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Phân bố trạng thái phòng' }
                }
            }
        });

        // Tìm kiếm và lọc
        function filterRooms() {
            const search = document.getElementById('searchInput').value;
            const filterStatus = document.getElementById('filterStatus').value;
            window.location.href = `room_management.php?search=${encodeURIComponent(search)}&filter_status=${encodeURIComponent(filterStatus)}`;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>