<?php
require_once 'config.php';
require_once 'controller.php';

checkRole(['manager']);

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$error = '';

if (strtotime($start_date) > strtotime($end_date)) {
    $error = "Ngày bắt đầu không được lớn hơn ngày kết thúc!";
    $report = ['total' => 0, 'count' => 0];
    $invoices = null;
    $labels = [];
    $data = [];
    $room_report = null;
} else {
    // Tổng doanh thu và số lượng thanh toán
    $stmt = $conn->prepare("SELECT SUM(amount) as total, COUNT(id) as count FROM payments WHERE payment_date BETWEEN ? AND ? AND status = 'paid'");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();

    // Danh sách thanh toán chi tiết
    $stmt_detail = $conn->prepare("SELECT p.id, p.amount, p.payment_date, u.full_name 
                                   FROM payments p 
                                   JOIN bookings b ON p.booking_id = b.id 
                                   JOIN users u ON b.user_id = u.id 
                                   WHERE p.payment_date BETWEEN ? AND ? AND p.status = 'paid'");
    $stmt_detail->bind_param("ss", $start_date, $end_date);
    $stmt_detail->execute();
    $invoices = $stmt_detail->get_result();

    // Dữ liệu cho biểu đồ
    $stmt_chart = $conn->prepare("SELECT DATE(payment_date) as date, SUM(amount) as daily_total 
                                  FROM payments 
                                  WHERE payment_date BETWEEN ? AND ? AND status = 'paid' 
                                  GROUP BY DATE(payment_date)");
    $stmt_chart->bind_param("ss", $start_date, $end_date);
    $stmt_chart->execute();
    $chart_data = $stmt_chart->get_result();

    $labels = [];
    $data = [];
    while ($row = $chart_data->fetch_assoc()) {
        $labels[] = $row['date'];
        $data[] = $row['daily_total'];
    }

    // Doanh thu theo loại phòng
    $stmt_room = $conn->prepare("SELECT r.room_type, SUM(p.amount) as total 
                                 FROM payments p 
                                 JOIN bookings b ON p.booking_id = b.id 
                                 JOIN rooms r ON b.room_id = r.id 
                                 WHERE p.payment_date BETWEEN ? AND ? AND p.status = 'paid' 
                                 GROUP BY r.room_type");
    $stmt_room->bind_param("ss", $start_date, $end_date);
    $stmt_room->execute();
    $room_report = $stmt_room->get_result();

    // Xuất CSV
    if (isset($_GET['export']) && $_GET['export'] == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="revenue_report.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID Thanh toán', 'Khách hàng', 'Ngày tạo', 'Số tiền (USD)']);

        $stmt_export = $conn->prepare("SELECT p.id, u.full_name, p.payment_date, p.amount 
                                       FROM payments p 
                                       JOIN bookings b ON p.booking_id = b.id 
                                       JOIN users u ON b.user_id = u.id 
                                       WHERE p.payment_date BETWEEN ? AND ? AND p.status = 'paid'");
        $stmt_export->bind_param("ss", $start_date, $end_date);
        $stmt_export->execute();
        $export_data = $stmt_export->get_result();

        while ($row = $export_data->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['full_name'],
                date('d/m/Y', strtotime($row['payment_date'])),
                number_format($row['amount'], 2)
            ]);
        }
        fclose($output);
        exit;
    }
}
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
        <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
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
            <p>Số lượng thanh toán: <?php echo $report['count'] ?? 0; ?></p>
        </div>
        <div class="mt-4">
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=csv" class="btn btn-success">Xuất CSV</a>
        </div>
        <div class="mt-4">
            <h4>Xu hướng doanh thu</h4>
            <canvas id="revenueChart"></canvas>
        </div>
        <div class="mt-4">
            <h4>Doanh thu theo loại phòng</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Loại phòng</th>
                        <th>Doanh thu (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($room_report): ?>
                        <?php while ($row = $room_report->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                                <td><?php echo number_format($row['total'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <h4>Chi tiết thanh toán</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID Thanh toán</th>
                        <th>Khách hàng</th>
                        <th>Ngày tạo</th>
                        <th>Số tiền (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($invoices): ?>
                        <?php while ($invoice = $invoices->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $invoice['id']; ?></td>
                                <td><?php echo htmlspecialchars($invoice['full_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($invoice['payment_date'])); ?></td>
                                <td><?php echo number_format($invoice['amount'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="scripts.js"></script>
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Doanh thu (USD)',
                    data: <?php echo json_encode($data); ?>,
                    borderColor: '#dc3545',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>