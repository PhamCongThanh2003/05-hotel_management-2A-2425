<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống quản lý khách sạn</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <img src="assets/2.jpg" alt="Hotel PMS Logo" style="height: 55px ;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                        <li class="nav-item"><a class="nav-link" href="#features">Sản phẩm</a></li>
                        <li class="nav-item"><a class="nav-link" href="#pricing">Bảng giá</a></li>
                        <li class="nav-item"><a class="nav-link" href="#blog">Blog</a></li>
                        <li class="nav-item"><a class="nav-link" href="#contact">Liên hệ</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                            <li class="nav-item"><span class="nav-link">Xin chào, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</span></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link btn btn-warning text-dark" href="login.php">Đăng nhập</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container text-center py-5">
            <h1 class="display-4 text-white">Giải pháp toàn diện quản lý khách sạn</h1>
            <p class="lead text-white mb-4">Hệ thống quản lý khách sạn giúp bạn tối ưu hóa hoạt động, quản lý phòng và đặt phòng trực tiếp trên website.</p>
            <a href="#dashboard" class="btn btn-warning btn-lg">Xem Demo</a>
            <div class="hero-image mt-5">
                <img src="assets/1.png" alt="Dashboard" class="img-fluid rounded shadow">
            </div>
        </div>
    </section>

    <!-- Dashboard Section -->
    <section id="dashboard" class="dashboard py-5">
        <div class="container">
            <h2 class="text-center mb-5">Chào mừng đến với hệ thống</h2>
            <div class="row mt-4">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="col-md-4">
                        <a href="login.php" class="btn btn-primary w-100 mb-3">Đăng nhập</a>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['customer', 'staff'])): ?>
                    <div class="col-md-4">
                        <a href="booking.php" class="btn btn-success w-100 mb-3">Quản lý đặt phòng</a>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'staff'): ?>
                    <div class="col-md-4">
                        <a href="payment.php" class="btn btn-warning w-100 mb-3">Quản lý thanh toán</a>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['staff', 'manager'])): ?>
                    <div class="col-md-4">
                        <a href="room_management.php" class="btn btn-info w-100 mb-3">Quản lý phòng</a>
                    </div>
                    <div class="col-md-4">
                        <a href="customer_management.php" class="btn btn-secondary w-100 mb-3">Quản lý khách hàng</a>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'manager'): ?>
                    <div class="col-md-4">
                        <a href="report.php" class="btn btn-danger w-100 mb-3">Báo cáo doanh thu</a>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <div class="col-md-4">
                        <a href="system_config.php" class="btn btn-dark w-100 mb-3">Cấu hình hệ thống</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features py-5">
        <div class="container">
            <h2 class="text-center mb-5">Tính năng nổi bật</h2>
            <div class="row">
                <div class="col-md-6 text-center">
                    <a href="room_management.php" <?php echo (isset($_SESSION['role']) && in_array($_SESSION['role'], ['staff', 'manager'])) ? '' : 'onclick="alert(\'Bạn không có quyền truy cập!\'); return false;"'; ?>>
                        <i class="fas fa-bed fa-3x text-primary mb-3"></i>
                        <h4>Quản lý phòng</h4>
                        <p>Theo dõi trạng thái phòng và tối ưu hóa công suất.</p>
                    </a>
                </div>
                <div class="col-md-6 text-center">
                    <a href="booking.php" <?php echo (isset($_SESSION['role']) && in_array($_SESSION['role'], ['customer', 'staff'])) ? '' : 'onclick="alert(\'Bạn không có quyền truy cập!\'); return false;"'; ?>>
                        <i class="fas fa-shopping-cart fa-3x text-primary mb-3"></i>
                        <h4>Đặt phòng trực tiếp</h4>
                        <p>Tích hợp đặt phòng ngay trên website của bạn.</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section (Placeholder) -->
    <section id="pricing" class="pricing py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Bảng giá</h2>
            <p class="text-center">Liên hệ với chúng tôi để nhận báo giá chi tiết.</p>
            <div class="text-center mt-3">
                <a href="#contact" class="btn btn-primary">Liên hệ ngay</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Liên hệ</h5>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Đường Khách Sạn, TP.HCM, Việt Nam</p>
                    <p><i class="fas fa-phone"></i> (+84) 123 456 789</p>
                    <p><i class="fas fa-envelope"></i> info@hotel.com</p>
                </div>
                <div class="col-md-4">
                    <h5>Theo dõi chúng tôi</h5>
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                </div>
                <div class="col-md-4">
                    <h5>Bản đồ</h5>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.669618!2d106.679983!3d10.759917!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f38f8ed6e45%3A0x95041e559a2c2f76!2sHo%20Chi%20Minh%20City%2C%20Vietnam!5e0!3m2!1sen!2s!4v1698687600000!5m2!1sen!2s" width="100%" height="150" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
            <div class="text-center mt-3">
                <p>© 2025 Hệ thống quản lý khách sạn. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="scripts.js"></script>
</body>
</html>