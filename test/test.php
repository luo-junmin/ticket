<?php
echo __DIR__ ;
echo "<br>";
echo $_SERVER['DOCUMENT_ROOT'] ;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Booking System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), url('https://images.unsplash.com/photo-1511795409834-ef04bbd61622?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2069&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 120px 0;
            text-align: center;
        }
        .event-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .event-image {
            height: 200px;
            object-fit: cover;
        }
        .card-body {
            padding: 20px;
        }
        .price-tag {
            background-color: #4e73df;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .btn-primary {
            background-color: #4e73df;
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            transform: scale(1.05);
        }
        .feature-section {
            padding: 80px 0;
            background-color: #fff;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #4e73df;
            margin-bottom: 20px;
        }
        .login-modal .modal-content {
            border-radius: 12px;
            overflow: hidden;
            border: none;
        }
        .login-modal .modal-header {
            background-color: #4e73df;
            color: white;
            border: none;
        }
        .social-login .btn {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            text-align: left;
            padding: 10px 20px;
        }
        .social-login .btn i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .divider {
            text-align: center;
            position: relative;
            margin: 20px 0;
        }
        .divider::before, .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background-color: #e0e0e0;
        }
        .divider::before {
            left: 0;
        }
        .divider::after {
            right: 0;
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .footer {
            background-color: #2e3a59;
            color: #fff;
            padding: 50px 0 20px;
        }
        .footer a {
            color: #a3b1d5;
            text-decoration: none;
            transition: color 0.3s;
        }
        .footer a:hover {
            color: #fff;
        }
        .copyright {
            border-top: 1px solid #3a4a6d;
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-ticket-alt me-2"></i>EventTickets
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Venues</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
            </ul>
            <div class="d-flex">
                <a href="#" class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="fas fa-sign-in-alt me-1"></i>Login
                </a>
                <a href="#" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i>Sign Up
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="display-3 fw-bold mb-4">Amazing Events Await You</h1>
        <p class="lead mb-5">Discover the best concerts, festivals, and performances in your city</p>
        <a href="#events" class="btn btn-primary btn-lg px-5 py-3">
            <i class="fas fa-calendar-alt me-2"></i>Explore Events
        </a>
    </div>
</section>

<!-- Events Section -->
<section id="events" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">Upcoming Events</h2>
            <p class="text-muted">Find events that match your interests</p>
        </div>

        <div class="row">
            <!-- Event Card 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="card event-card">
                    <img src="https://images.unsplash.com/photo-1470225620780-dba8ba36b745?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" class="card-img-top event-image" alt="Music Festival">
                    <div class="price-tag">$49.99</div>
                    <div class="card-body">
                        <h5 class="card-title">Summer Music Festival</h5>
                        <p class="card-text text-muted">
                            <i class="fas fa-calendar me-1"></i> July 15-17, 2023
                            <br>
                            <i class="fas fa-map-marker-alt me-1"></i> Central Park, New York
                        </p>
                        <p class="card-text">Join us for the biggest music festival of the year with top artists from around the world.</p>
                        <button class="btn btn-primary w-100 book-btn">
                            <i class="fas fa-shopping-cart me-1"></i>Book Now
                        </button>
                    </div>
                </div>
            </div>

            <!-- Event Card 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="card event-card">
                    <img src="https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1974&q=80" class="card-img-top event-image" alt="Theater">
                    <div class="price-tag">$35.00</div>
                    <div class="card-body">
                        <h5 class="card-title">Broadway Theater Night</h5>
                        <p class="card-text text-muted">
                            <i class="fas fa-calendar me-1"></i> August 5, 2023
                            <br>
                            <i class="fas fa-map-marker-alt me-1"></i> Majestic Theater, NYC
                        </p>
                        <p class="card-text">Experience the magic of Broadway with this special performance of a Tony Award-winning musical.</p>
                        <button class="btn btn-primary w-100 book-btn">
                            <i class="fas fa-shopping-cart me-1"></i>Book Now
                        </button>
                    </div>
                </div>
            </div>

            <!-- Event Card 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="card event-card">
                    <img src="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1974&q=80" class="card-img-top event-image" alt="Comedy">
                    <div class="price-tag">$25.00</div>
                    <div class="card-body">
                        <h5 class="card-title">Stand-up Comedy Night</h5>
                        <p class="card-text text-muted">
                            <i class="fas fa-calendar me-1"></i> July 22, 2023
                            <br>
                            <i class="fas fa-map-marker-alt me-1"></i> Comedy Cellar, Chicago
                        </p>
                        <p class="card-text">Laugh the night away with top comedians from Netflix specials and late-night TV.</p>
                        <button class="btn btn-primary w-100 book-btn">
                            <i class="fas fa-shopping-cart me-1"></i>Book Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="feature-section">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-5 mb-md-0">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4 class="fw-bold">Secure Booking</h4>
                <p class="text-muted">Your transactions are protected with bank-level security and encryption.</p>
            </div>
            <div class="col-md-4 mb-5 mb-md-0">
                <div class="feature-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h4 class="fw-bold">Instant E-Tickets</h4>
                <p class="text-muted">Receive your tickets immediately via email after booking.</p>
            </div>
            <div class="col-md-4">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h4 class="fw-bold">24/7 Support</h4>
                <p class="text-muted">Our customer service team is available anytime to assist you.</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <h5 class="mb-4"><i class="fas fa-ticket-alt me-2"></i>EventTickets</h5>
                <p>Your premier destination for event tickets and experiences. Find the best events in your city.</p>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <h5 class="mb-4">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#">Home</a></li>
                    <li class="mb-2"><a href="#">Events</a></li>
                    <li class="mb-2"><a href="#">Venues</a></li>
                    <li class="mb-2"><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <h5 class="mb-4">Categories</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#">Concerts</a></li>
                    <li class="mb-2"><a href="#">Sports</a></li>
                    <li class="mb-2"><a href="#">Theater</a></li>
                    <li class="mb-2"><a href="#">Festivals</a></li>
                    <li><a href="#">Family</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="mb-4">Subscribe</h5>
                <p>Get the latest event updates and special offers</p>
                <div class="input-group mb-3">
                    <input type="email" class="form-control" placeholder="Your Email">
                    <button class="btn btn-primary" type="button">Subscribe</button>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2023 EventTickets. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Login Modal -->
<div class="modal fade login-modal" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Login to Your Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="social-login mb-4">
                    <button class="btn btn-light mb-2">
                        <i class="fab fa-google text-danger"></i> Continue with Google
                    </button>
                    <button class="btn btn-light mb-2">
                        <i class="fab fa-facebook text-primary"></i> Continue with Facebook
                    </button>
                    <button class="btn btn-light">
                        <i class="fab fa-apple"></i> Continue with Apple
                    </button>
                </div>

                <div class="divider">
                    <span>or login with email</span>
                </div>

                <form id="loginForm" class="mt-4">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                        <a href="#" class="float-end">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
                </form>

                <div class="text-center mt-4">
                    <p>Don't have an account? <a href="#" class="fw-bold">Sign up</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all Book Now buttons
        const bookButtons = document.querySelectorAll('.book-btn');
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));

        // Check if user is logged in
        function isLoggedIn() {
            // In a real application, this would check cookies or localStorage
            return false; // Currently set to false to simulate logged out state
        }

        // Handle Book Now button clicks
        bookButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!isLoggedIn()) {
                    e.preventDefault();

                    // Show the login modal
                    loginModal.show();

                    // Highlight the clicked event
                    const eventCard = this.closest('.event-card');
                    eventCard.style.border = '2px solid #4e73df';
                    eventCard.style.boxShadow = '0 0 15px rgba(78, 115, 223, 0.5)';

                    // Remove highlight after modal is closed
                    loginModal._element.addEventListener('hidden.bs.modal', function() {
                        eventCard.style.border = '';
                        eventCard.style.boxShadow = '';
                    }, {once: true});

                    // Notification that login is required
                    const eventTitle = eventCard.querySelector('.card-title').textContent;
                    document.getElementById('loginModal').querySelector('.modal-header').innerHTML = `
                            <h5 class="modal-title">Login Required</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        `;
                    document.getElementById('loginModal').querySelector('.modal-body').insertAdjacentHTML('afterbegin', `
                            <div class="alert alert-info">
                                Please login to book tickets for <strong>${eventTitle}</strong>
                            </div>
                        `);
                } else {
                    // If logged in, proceed to booking
                    alert('Proceeding to booking page...');
                }
            });
        });

        // Login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // In a real application, this would send an AJAX request
            alert('Login functionality would be implemented here');

            // Close modal after successful login
            setTimeout(() => {
                loginModal.hide();
                alert('Login successful! You can now book tickets.');
            }, 1000);
        });
    });
</script>
</body>
</html>
