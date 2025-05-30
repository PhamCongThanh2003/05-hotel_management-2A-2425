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
                    <img src="assets/2.jpg" alt="Hotel PMS Logo" style="height: 55px;">
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
<section class="hero position-relative overflow-hidden">
    <!-- Slider ảnh nền -->
    <div id="hero-slider" class="position-absolute top-0 start-0 w-100 h-100 z-0">
        <img src="assets/slide1.jpg" class="slider-bg active" alt="Slide 1">
        <img src="assets/slide2.jpg" class="slider-bg" alt="Slide 2">
        <img src="assets/slide3.jpg" class="slider-bg" alt="Slide 3">
    </div>

    <!-- Nội dung phía trên ảnh -->
    <div class="hero-content text-white">
        <h1 class="display-4">Giải pháp toàn diện quản lý khách sạn</h1>
        <p class="lead mb-4">Hệ thống quản lý khách sạn giúp bạn tối ưu hóa hoạt động, quản lý phòng và đặt phòng trực tiếp trên website.</p>
        <a href="#dashboard" class="btn btn-warning btn-lg">Xem Demo</a>
    </div>
</section>

<!-- Welcome Section (Thêm mới) -->
<section id="welcome-section" class="welcome-section py-5">
    <div class="container">
        <h1 class="welcome-text mb-3 text-center">Welcome to Sunrise Ha Noi Hotel</h1>
        <p class="lead mb-4 text-center">Sunrise Hotel offers a full range of amenities, fast check-in procedures, free public Wi-Fi coverage throughout the resort. The highlight of Sunrise Hotel is the restaurant that offers both Western and Oriental cuisine built and served on the top floor. You can enjoy a delicious dinner while watching the starry sky and sea at night. In addition, we can enjoy other services such as spa, gym in the resort basis.<br><br>Sunrise Hotel is confident to bring you the emotional excitement, the best experience, the best in your stay. Come to us, we guarantee that you will not regret.</p>
        <div class="text-center">
            <a href="#contact" class="btn btn-warning btn-lg">Contact Us</a>
        </div>
    </div>
</section>

<!-- Elegant Accommodation Section (Carousel) -->
    <section class="elegant-accommodation py-5">
        <div class="container text-center">
            <h1 class="display-4 text-uppercase text-dark fw-bold">Elegant Accommodation</h1>
            <div id="roomCarousel" class="carousel slide mt-4" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="row">
                            <div class="col-md-4">
                                <img src="images/deluxecity.jpg" alt="Room 1" class="img-fluid rounded">
                                <p class="mt-2">Balcony Family Suite with a comfortable King-sized bed and spacious balcony.</p>
                                <a href="#" class="btn btn-warning mt-2">View Detail</a>
                            </div>
                            <div class="col-md-4">
                                <img src="images/senior.jpg" alt="Room 2" class="img-fluid rounded">
                                <p class="mt-2">Deluxe Room with modern amenities and stunning city views.</p>
                                <a href="#" class="btn btn-warning mt-2">View Detail</a>
                            </div>
                            <div class="col-md-4">
                                <img src="images/triplesuite.jpg" alt="Room 3" class="img-fluid rounded">
                                <p class="mt-2">Executive Suite with luxurious decor and private terrace.</p>
                                <a href="#" class="btn btn-warning mt-2">View Detail</a>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row">
                            <div class="col-md-4">
                                <img src="images/familysuite.jpg" alt="Room 4" class="img-fluid rounded">
                                <p class="mt-2">Superior Room with a cozy atmosphere and city skyline view.</p>
                                <a href="#" class="btn btn-warning mt-2">View Detail</a>
                            </div>
                            <div class="col-md-4">
                                <img src="images/connecting.jpg" alt="Room 5" class="img-fluid rounded">
                                <p class="mt-2">Penthouse Suite with panoramic views and premium amenities.</p>
                                <a href="#" class="btn btn-warning mt-2">View Detail</a>
                            </div>
                            <div class="col-md-4">
                                <img src="images/junior.jpg" alt="Room 6" class="img-fluid rounded">
                                <p class="mt-2">Garden View Room with direct access to the garden area.</p>
                                <a href="#" class="btn btn-warning mt-2">View Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#roomCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
            <a href="#" class="btn btn-outline-secondary mt-4">View all rooms</a>
        </div>
    </section>

    <!-- Dashboard Section -->
    <section id="dashboard" class="dashboard">
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
    <section id="features" class="features">
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

    <!-- Pricing Section (Room Types & Rates) -->
    <section id="pricing" class="pricing py-5">
        <div class="container text-center">
            <h2 class="display-4 text-uppercase text-dark fw-bold">Room Types & Rates</h2>
            <div class="table-responsive mt-4">
                <table class="table table-bordered text-center mx-auto" style="max-width: 600px;">
                    <thead>
                        <tr>
                            <th>Room Types</th>
                            <th>Public Rate (US$)</th>
                            <th>Promotional Rate (US$) <br> (valid until Dec. 2023)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Deluxe City View</td>
                            <td>60++</td>
                            <td>50++</td>
                        </tr>
                        <tr>
                            <td>Senior Deluxe City View</td>
                            <td>80++</td>
                            <td>70++</td>
                        </tr>
                        <tr>
                            <td>Balcony Triple Suite</td>
                            <td>90++</td>
                            <td>80++</td>
                        </tr>
                        <tr>
                            <td>Balcony Family Suite</td>
                            <td>100++</td>
                            <td>95++</td>
                        </tr>
                        <tr>
                            <td>Connecting Room</td>
                            <td>95++</td>
                            <td>90++</td>
                        </tr>
                        <tr>
                            <td>Junior Suite</td>
                            <td>115++</td>
                            <td>100++</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-muted">*Extra bed is available@US$20++ including buffet breakfast (all room categories)</p>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Liên hệ</h5>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Đường Khách Sạn, TP Ha Noi, Việt Nam</p>
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
    <script>
        // Kiểm tra xem hero-content có hiển thị không
        const heroContent = document.querySelector('.hero-content');
        if (heroContent) {
            console.log('Hero content được tìm thấy trong DOM:', heroContent);
            console.log('Style của hero-content:', window.getComputedStyle(heroContent));
        } else {
            console.log('Không tìm thấy hero-content trong DOM.');
        }

        // Slider logic
        let current = 0;
        const slides = document.querySelectorAll("#hero-slider .slider-bg");

        if (slides.length > 0) {
            console.log("Tổng số slide:", slides.length);
            setInterval(() => {
                console.log("Slide hiện tại:", current);
                slides[current].style.opacity = '0';
                slides[current].classList.remove("active");
                requestAnimationFrame(() => {
                    current = (current + 1) % slides.length;
                    slides[current].classList.add("active");
                    slides[current].style.opacity = '1';
                });
            }, 2000);
        } else {
            console.log("Không tìm thấy slider images.");
        }
    </script>
</body>
</html>