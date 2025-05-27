<?php
require_once 'config.php';
require_once 'controller.php';

checkRole(['manager']);

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$stmt = $conn->prepare("SELECT SUM(total_amount) as total, COUNT(id) as count FROM invoices WHERE created_at BETWEEN ? AND ? AND payment_status = 'paid'");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo doanh thu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Báo cáo doanh thu</h2>
        <form method="GET">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="start_date" class="form-label">Từ ngày</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="end_date" class="form-label">Đến ngày</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-danger w-100">Xem báo cáo</button>
        </form>
        <div class="mt-4">
            <h4>Kết quả</h4>
            <p>Tổng doanh thu: <?php echo number_format($report['total'] ?? 0, 2); ?> USD</p>
            <p>Số lượng hóa đơn: <?php echo $report['count'] ?? 0; ?></p>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>
</html>