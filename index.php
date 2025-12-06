<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevbagStayz - Luxury Beachfront Accommodations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --primary: #1a73e8;
            --secondary: #0d47a1;
            --accent: #ff6d00;
            --light: #f5f7fa;
            --dark: #333;
            --sea-blue: #4fc3f7;
            --sea-deep: #0288d1;
            --sand: #ffecb3;
            --text: #333;
            --bg: #ffffff;
            --card-bg: #ffffff;
            --shadow: rgba(0, 0, 0, 0.1);
            --nav-bg: rgba(255, 255, 255, 0.95);
            --footer-bg: #1a1a2e;
        }

        .dark-theme {
            --primary: #64b5f6;
            --secondary: #90caf9;
            --accent: #ff8a65;
            --light: #121212;
            --dark: #e0e0e0;
            --sea-blue: #01579b;
            --sea-deep: #0277bd;
            --sand: #5d4037;
            --text: #e0e0e0;
            --bg: #121212;
            --card-bg: #1e1e1e;
            --shadow: rgba(0, 0, 0, 0.3);
            --nav-bg: rgba(30, 30, 30, 0.95);
            --footer-bg: #0f0f1a;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            transition: all 0.3s ease;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header Styles */
        header {
            background: var(--nav-bg);
            color: var(--text);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px var(--shadow);
            backdrop-filter: blur(10px);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 2rem;
            color: var(--primary);
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 1.5rem;
            position: relative;
        }

        nav ul li a {
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            padding: 5px 0;
        }

        nav ul li a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background: var(--accent);
            transition: width 0.3s;
        }

        nav ul li a:hover:after {
            width: 100%;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .auth-buttons {
            display: flex;
            gap: 10px;
        }

        .auth-btn {
            padding: 8px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .auth-login {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .auth-signup {
            background: linear-gradient(45deg, var(--primary), var(--accent));
            color: white;
            border: none;
        }

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.2rem;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .theme-toggle:hover {
            transform: rotate(30deg);
        }

        .mobile-menu {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text);
        }

        /* Auth Modal */
        .auth-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 2000;
            backdrop-filter: blur(5px);
        }

        .auth-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        .auth-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-radius: 15px;
            background: var(--light);
            padding: 5px;
        }

        .auth-tab {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            color: var(--text);
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .auth-tab.active {
            background: linear-gradient(45deg, var(--primary), var(--accent));
            color: white;
        }

        .close-auth {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text);
            cursor: pointer;
            transition: all 0.3s;
        }

        .close-auth:hover {
            color: var(--accent);
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: var(--bg);
            color: var(--text);
        }

        .dark-theme .form-control {
            border: 1px solid #444;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');
            background-size: cover;
            background-position: center;
            height: 90vh;
            display: flex;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        .hero h2 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            animation: fadeInUp 1s ease;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            animation: fadeInUp 1s ease 0.2s both;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(45deg, var(--accent), #ff8a00);
            color: white;
            padding: 14px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255, 109, 0, 0.3);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 1s ease 0.4s both;
        }

        .btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.5s;
        }

        .btn:hover:before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(255, 109, 0, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid white;
            box-shadow: none;
        }

        .btn-outline:hover {
            background: white;
            color: var(--accent);
        }

        /* Floating Shapes */
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 20s infinite linear;
        }

        .shape-1 {
            width: 100px;
            height: 100px;
            background: var(--accent);
            border-radius: 50%;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 150px;
            height: 150px;
            background: var(--primary);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            top: 60%;
            left: 80%;
            animation-delay: -5s;
        }

        .shape-3 {
            width: 80px;
            height: 80px;
            background: var(--sea-blue);
            border-radius: 20% 80% 80% 20% / 20% 20% 80% 80%;
            top: 20%;
            left: 80%;
            animation-delay: -10s;
        }

        .shape-4 {
            width: 120px;
            height: 120px;
            background: var(--sand);
            border-radius: 50%;
            top: 70%;
            left: 15%;
            animation-delay: -15s;
        }

        /* Section Styles */
        section {
            padding: 6rem 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
        }

        .section-title h2 {
            font-size: 2.8rem;
            color: var(--secondary);
            display: inline-block;
            padding-bottom: 15px;
            position: relative;
        }

        .section-title h2:after {
            content: '';
            position: absolute;
            width: 100px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .section-subtitle {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 3rem;
            font-size: 1.2rem;
            color: var(--text);
            opacity: 0.8;
        }

        /* Buildings Section */
        .buildings-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
        }

        .building-card {
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px var(--shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .building-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 15px 40px var(--shadow);
        }

        .building-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            z-index: 1;
        }

        .swiper {
            width: 100%;
            height: 250px;
        }

        .swiper-slide {
            background-size: cover;
            background-position: center;
        }

        .swiper-pagination-bullet {
            background: white;
            opacity: 0.7;
        }

        .swiper-pagination-bullet-active {
            background: var(--accent);
            opacity: 1;
        }

        .building-info {
            padding: 2rem;
        }

        .building-info h3 {
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .building-info h3 i {
            color: var(--accent);
        }

        .building-info p {
            margin-bottom: 1.5rem;
            color: var(--text);
            opacity: 0.8;
        }

        .room-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 10px;
        }

        .room-details span {
            background-color: var(--light);
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .availability {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 10px;
        }

        .available {
            background-color: rgba(46, 125, 50, 0.1);
            color: #2e7d32;
        }

        .booked {
            background-color: rgba(198, 40, 40, 0.1);
            color: #c62828;
        }

        /* Room Details Section */
        .room-details-section {
            background-color: var(--light);
        }

        .room-filters {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 25px;
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .filter-btn.active, .filter-btn:hover {
            background: var(--primary);
            color: white;
            box-shadow: 0 5px 15px rgba(26, 115, 232, 0.3);
        }

        .floor-container {
            margin-bottom: 4rem;
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px var(--shadow);
        }

        .floor-title {
            font-size: 1.8rem;
            margin-bottom: 2rem;
            color: var(--secondary);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--sea-blue);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .floor-title i {
            color: var(--accent);
        }

        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .room-card {
            background: var(--light);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px var(--shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .room-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--accent));
        }

        .room-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px var(--shadow);
        }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .room-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--secondary);
        }

        .room-type {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .room-type span {
            background-color: var(--card-bg);
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .room-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 1.5rem;
        }

        .room-price span {
            font-size: 1rem;
            color: var(--text);
            opacity: 0.7;
            font-weight: normal;
        }

        /* Services Sections */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 3rem;
        }

        .service-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            box-shadow: 0 10px 30px var(--shadow);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .service-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--accent));
        }

        .service-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 15px 40px var(--shadow);
        }

        .service-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 2rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .service-card h3 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: var(--secondary);
        }

        .service-card p {
            margin-bottom: 2rem;
            color: var(--text);
            opacity: 0.8;
        }

        .menu-items, .transport-options, .activity-items {
            text-align: left;
            margin-top: 2rem;
        }

        .menu-item, .transport-option, .activity-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px dashed rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .dark-theme .menu-item, 
        .dark-theme .transport-option, 
        .dark-theme .activity-item {
            border-bottom: 1px dashed rgba(255,255,255,0.1);
        }

        .menu-item:hover, .transport-option:hover, .activity-item:hover {
            background: rgba(0,0,0,0.02);
            padding-left: 10px;
            border-radius: 5px;
        }

        .dark-theme .menu-item:hover, 
        .dark-theme .transport-option:hover, 
        .dark-theme .activity-item:hover {
            background: rgba(255,255,255,0.05);
        }

        .menu-item:last-child, .transport-option:last-child, .activity-item:last-child {
            border-bottom: none;
        }

        .price-tag {
            background: var(--accent);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Contact Section */
        .contact-section {
            background: linear-gradient(135deg, var(--sea-blue), var(--sea-deep));
            color: white;
            position: relative;
            overflow: hidden;
        }

        .contact-section:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1559827260-dc66d52bef19?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
        }

        .contact-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 4rem;
            position: relative;
            z-index: 2;
        }

        .contact-info h3, .booking-promo h3 {
            font-size: 2.2rem;
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
        }

        .contact-info h3:after, .booking-promo h3:after {
            content: '';
            position: absolute;
            width: 60px;
            height: 4px;
            background: var(--accent);
            bottom: -10px;
            left: 0;
            border-radius: 2px;
        }

        .booking-promo h3:after {
            left: 0;
        }

        .contact-details {
            margin-bottom: 3rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            transition: transform 0.3s;
        }

        .contact-item:hover {
            transform: translateX(10px);
        }

        .contact-item i {
            font-size: 1.5rem;
            margin-right: 20px;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 3rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s;
            font-size: 1.2rem;
        }

        .social-links a:hover {
            background: var(--accent);
            transform: translateY(-5px);
        }

        .booking-promo {
            background: var(--card-bg);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .booking-promo h3 {
            color: var(--secondary);
        }

        .promo-content {
            margin-bottom: 2.5rem;
        }

        .promo-content p {
            margin-bottom: 1.5rem;
            color: var(--text);
            font-size: 1.1rem;
            line-height: 1.7;
        }

        .features-list {
            text-align: left;
            margin: 2rem 0;
        }

        .features-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .features-list i {
            color: var(--accent);
        }

        .promo-btn {
            font-size: 1.2rem;
            padding: 16px 45px;
        }
        /* Add this to your existing CSS - Booking Promotion Section */
.booking-promo {
    background: var(--card-bg);
    padding: 3rem;
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.booking-promo h3 {
    color: var(--secondary);
}

.promo-content {
    margin-bottom: 2.5rem;
}

.promo-content p {
    margin-bottom: 1.5rem;
    color: var(--text); /* This was missing */
    font-size: 1.1rem;
    line-height: 1.7;
}

.features-list {
    text-align: left;
    margin: 2rem 0;
    color: var(--text); /* Add this line */
}

.features-list li {
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text); /* Add this line */
}

.features-list i {
    color: var(--accent);
}

        /* Footer */
        footer {
            background-color: var(--footer-bg);
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-col h4 {
            font-size: 1.4rem;
            margin-bottom: 1.8rem;
            position: relative;
            padding-bottom: 15px;
        }

        .footer-col h4:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: var(--accent);
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 12px;
        }

        .footer-col ul li a {
            color: #bbb;
            text-decoration: none;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-col ul li a:hover {
            color: white;
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #bbb;
            font-size: 0.9rem;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            50% {
                transform: translate(20px, 20px) rotate(180deg);
            }
            100% {
                transform: translate(0, 0) rotate(360deg);
            }
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .hero h2 {
                font-size: 2.8rem;
            }
            
            .section-title h2 {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }

            nav ul {
                margin-top: 1.5rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            nav ul li {
                margin: 0.5rem 1rem;
            }

            .hero h2 {
                font-size: 2.2rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .mobile-menu {
                display: block;
                position: absolute;
                top: 1rem;
                right: 1rem;
            }

            nav {
                display: none;
                width: 100%;
                margin-top: 1.5rem;
            }

            nav.active {
                display: block;
            }

            nav ul {
                flex-direction: column;
            }

            nav ul li {
                margin: 0.5rem 0;
            }
            
            .header-actions {
                margin-top: 1rem;
            }
            
            .services-grid, .buildings-container {
                grid-template-columns: 1fr;
            }

            .auth-buttons {
                flex-direction: column;
                width: 100%;
            }

            .auth-btn {
                text-align: center;
            }
        }
    </style>
</head>
<body>
   <!-- Header -->
<header>
    <div class="container header-container">
        <div class="logo">
            <i class="fas fa-umbrella-beach"></i>
            <h1>DevbagStayz</h1>
        </div>
        <nav>
            <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#accommodation">Accommodation</a></li>
                <li><a href="#dining">Dining</a></li>
                <li><a href="#transport">Transport</a></li>
                <li><a href="#activities">Activities</a></li>
                <li><a href="#contact">Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?php echo isAdmin() ? 'admin.php' : 'user.php'; ?>">
                        <i class="fas fa-user"></i> Dashboard
                    </a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="header-actions">
            <?php
            $auth_status = checkAuthStatus();
            if ($auth_status['logged_in']): 
            ?>
                <div class="user-info" style="display: flex; align-items: center; gap: 15px;">
                    <span style="color: var(--text);">
                        <i class="fas fa-user"></i> 
                        <?php echo htmlspecialchars($auth_status['user_name']); ?>
                        (<?php echo ucfirst($auth_status['user_role']); ?>)
                    </span>
                    <a href="auth.php?action=logout" class="auth-btn auth-login">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="#" class="auth-btn auth-login" id="loginBtn">Login</a>
                    <a href="#" class="auth-btn auth-signup" id="signupBtn">Sign Up</a>
                </div>
            <?php endif; ?>
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-moon"></i>
            </button>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </div>
</header>

    <!-- Auth Modal -->
<div class="auth-modal" id="authModal">
    <div class="auth-content">
        <button class="close-auth" id="closeAuth">&times;</button>
        <div class="auth-tabs">
            <button class="auth-tab active" data-tab="login">Login</button>
            <button class="auth-tab" data-tab="signup">Sign Up</button>
        </div>
        
        <!-- Display error messages -->
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message" style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #c62828;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form id="loginForm" class="auth-form" method="POST" action="auth.php">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label for="loginEmail">Email Address</label>
                <input type="email" id="loginEmail" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;">Login to Your Account</button>
        </form>
        
        <!-- Signup Form -->
        <form id="signupForm" class="auth-form" method="POST" action="auth.php" style="display: none;">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label for="signupName">Full Name</label>
                <input type="text" id="signupName" name="name" class="form-control" placeholder="Enter your full name" required>
            </div>
            <div class="form-group">
                <label for="signupEmail">Email Address</label>
                <input type="email" id="signupEmail" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="signupPassword">Password</label>
                <input type="password" id="signupPassword" name="password" class="form-control" placeholder="Create a password " required>
            </div>
            <div class="form-group">
                <label for="signupConfirm">Confirm Password</label>
                <input type="password" id="signupConfirm" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;">Create Account</button>
        </form>
    </div>
</div>
    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
        </div>
        <div class="container hero-content">
            <h2>Experience Paradise at Devbag Beach</h2>
            <p>Luxury beachfront accommodations with breathtaking sea views and premium amenities for 2025</p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="#accommodation" class="btn">Book Your Stay</a>
                <a href="#contact" class="btn btn-outline">Contact Us</a>
            </div>
        </div>
    </section>

    <!-- Buildings Section -->
    <section id="accommodation">
        <div class="container">
            <div class="section-title">
                <h2>Our Accommodations</h2>
            </div>
            <p class="section-subtitle">Choose from our three premium blocks, each offering unique views and amenities for your perfect beach getaway in 2025.</p>
            <div class="buildings-container">
                <!-- Block A -->
                <div class="building-card block-a">
                    <div class="swiper swiper-block-a">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide" style="background-image:url('seaface1.jpg')"></div>
                            <div class="swiper-slide" style="background-image: url('seaface2.jpg')"></div>
                            <div class="swiper-slide" style="background-image: url('seaface3.jpg')"></div>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                    <div class="building-info">
                        <h3></i> Block A Sea Facing</h3>
                        <p>Wake up to stunning ocean views with direct beach access from these premium rooms. Perfect for romantic getaways and special occasions.</p>
                        <div class="room-details">
                            <span><i class="fas fa-bed"></i> 1-3 Beds</span>
                            <span><i class="fas fa-snowflake"></i> AC/Non-AC</span>
                            <span><i class="fas fa-wifi"></i>  WiFi</span>
                             <span><i class="fas fa-tv"></i> Smart TV</span>
                             <span><i class="fas fa-shower"></i> Hot Water</span>
                        </div>
                        <div class="room-price">₹500-₹2000/night</div>
                        <span class="availability available">Most Rooms Available</span>
                    </div>
                </div>

                <!-- Block B -->
                <div class="building-card block-b">
                    <div class="swiper swiper-block-b">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide" style="background-image: url('garden1.jpg')"></div>
                            <div class="swiper-slide" style="background-image: url('garden2.jpg')"></div>
                            <div class="swiper-slide" style="background-image: url('garden3.jpg')"></div>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                    <div class="building-info">
                        <h3></i> Block B - Garden View</h3>
                        <p>Comfortable rooms with beautiful garden views, perfect for a peaceful retreat. Enjoy the serene surroundings and lush greenery.</p>
                        <div class="room-details">
                            <span><i class="fas fa-bed"></i> 1-5 Beds</span>
                            <span><i class="fas fa-snowflake"></i> AC/Non-AC</span>
                            <span><i class="fas fa-wifi"></i> WiFi</span>
                             <span><i class="fas fa-tv"></i> Smart TV</span>
                             <span><i class="fas fa-shower"></i> Hot Water</span>
                        </div>
                        <div class="room-price">₹500-₹2000/night</div>
                        <span class="availability available">Limited Availability</span>
                    </div>
                </div>

                <!-- Block C -->
                <div class="building-card block-c">
                    <div class="swiper swiper-block-c">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide" style="background-image: url('non2.jpeg')"></div>
                            <div class="swiper-slide" style="background-image: url('non3.jpg')"></div>
                            <div class="swiper-slide" style="background-image: url('non1.jpeg')"></div>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                    <div class="building-info">
                        <h3></i> Block C - Non Sea Facing</h3>
                        <p>Budget-friendly options with all essential amenities for a comfortable stay. Ideal for families and groups traveling together.</p>
                        <div class="room-details">
                            <span><i class="fas fa-bed"></i> 1-6 Beds</span>
                            <span><i class="fas fa-snowflake"></i> AC/Non-AC</span>
                            <span><i class="fas fa-wifi"></i> Free WiFi</span>
                             <span><i class="fas fa-tv"></i> Smart TV</span>
                             <span><i class="fas fa-shower"></i> Hot Water</span>
                        </div>
                        <div class="room-price">₹500-₹2000/night</div>
                        <span class="availability available">Good Availability</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Room Details Section -->
    <section class="room-details-section">
        <div class="container">
            <div class="section-title">
                <h2>Room Availability</h2>
            </div>
            <p class="section-subtitle">Check real-time availability for our rooms across all blocks. Book your preferred room for 2025 now!</p>
            
            <div class="room-filters">
                <button class="filter-btn active" data-block="all">All Blocks</button>
                <button class="filter-btn" data-block="a">Block A</button>
                <button class="filter-btn" data-block="b">Block B</button>
                <button class="filter-btn" data-block="c">Block C</button>
            </div>

            <!-- Block A Floors -->
            <div class="floor-container" data-block="a">
                <h3 class="floor-title"><i class="fas fa-building"></i> Block A - Floor 1</h3>
                <div class="rooms-grid">
                    <?php
                    // Fetch rooms from database for Block A
                    $rooms_a = $conn->query("SELECT * FROM rooms WHERE block = 'A'");
                    while($row = $rooms_a->fetch_assoc()){
                        $statusClass = $row['available'] == 'Yes' ? 'available' : 'booked';
                        $statusText = $row['available'] == 'Yes' ? 'Available' : 'Booked';
                        
                        
                          // AC display logic - updated
            $acDisplay = (strtolower($row['ac']) === 'yes') ? 'AC Room' : 'Non-AC Room';
            $acIcon = (strtolower($row['ac']) === 'yes') ? 'fas fa-snowflake' : 'fas fa-wind';
                        // WiFi display logic - only show if available
                        $wifiDisplay = '';
                        if (strtolower($row['wifi']) === 'yes') {
                            $wifiDisplay = "<span><i class='fas fa-wifi'></i> Free WiFi</span>";
                        }
                        
                        echo "
                        <div class='room-card'>
                            <div class='room-header'>
                                <div class='room-number'>Room {$row['room_no']}</div>
                                <span class='availability {$statusClass}'>{$statusText}</span>
                            </div>
                            <div class='room-type'>
                                <span><i class='fas fa-bed'></i> {$row['beds']} Beds</span>
                                <span><i class='fas fa-snowflake'></i> {$acDisplay}</span>
                                {$wifiDisplay}
                            </div>
                            <div class='room-price'>₹{$row['price']} <span>per night</span></div>
                            <button class='btn' " . ($row['available'] == 'No' ? "disabled style='background-color: #ccc; cursor: not-allowed;'" : "") . ">" . ($row['available'] == 'Yes' ? 'Book Now' : 'Not Available') . "</button>
                        </div>
                        ";
                    }
                    ?>
                </div>
            </div>

            <!-- Block B Floors -->
            <div class="floor-container" data-block="b">
                <h3 class="floor-title"><i class="fas fa-building"></i> Block B - Floor 1</h3>
                <div class="rooms-grid">
                    <?php
                    // Fetch rooms from database for Block B
                    $rooms_b = $conn->query("SELECT * FROM rooms WHERE block = 'B'");
                    while($row = $rooms_b->fetch_assoc()){
                        $statusClass = $row['available'] == 'Yes' ? 'available' : 'booked';
                        $statusText = $row['available'] == 'Yes' ? 'Available' : 'Booked';
                        
                     
                          // AC display logic - updated
            $acDisplay = (strtolower($row['ac']) === 'yes') ? 'AC Room' : 'Non-AC Room';
            $acIcon = (strtolower($row['ac']) === 'yes') ? 'fas fa-snowflake' : 'fas fa-wind';
                        
                        // WiFi display logic - only show if available
                        $wifiDisplay = '';
                        if (strtolower($row['wifi']) === 'yes') {
                            $wifiDisplay = "<span><i class='fas fa-wifi'></i> Free WiFi</span>";
                        }
                        
                        echo "
                        <div class='room-card'>
                            <div class='room-header'>
                                <div class='room-number'>Room {$row['room_no']}</div>
                                <span class='availability {$statusClass}'>{$statusText}</span>
                            </div>
                            <div class='room-type'>
                                <span><i class='fas fa-bed'></i> {$row['beds']} Beds</span>
                                <span><i class='fas fa-snowflake'></i> {$acDisplay}</span>
                                {$wifiDisplay}
                            </div>
                            <div class='room-price'>₹{$row['price']} <span>per night</span></div>
                            <button class='btn' " . ($row['available'] == 'No' ? "disabled style='background-color: #ccc; cursor: not-allowed;'" : "") . ">" . ($row['available'] == 'Yes' ? 'Book Now' : 'Not Available') . "</button>
                        </div>
                        ";
                    }
                    ?>
                </div>
            </div>

            <!-- Block C Floors -->
            <div class="floor-container" data-block="c">
                <h3 class="floor-title"><i class="fas fa-building"></i> Block C - Floor 1</h3>
                <div class="rooms-grid">
                    <?php
                    // Fetch rooms from database for Block C
                    $rooms_c = $conn->query("SELECT * FROM rooms WHERE block = 'C'");
                    while($row = $rooms_c->fetch_assoc()){
                        $statusClass = $row['available'] == 'Yes' ? 'available' : 'booked';
                        $statusText = $row['available'] == 'Yes' ? 'Available' : 'Booked';
                        
                       
                          // AC display logic - updated
            $acDisplay = (strtolower($row['ac']) === 'yes') ? 'AC Room' : 'Non-AC Room';
            $acIcon = (strtolower($row['ac']) === 'yes') ? 'fas fa-snowflake' : 'fas fa-wind';
                        
                        // WiFi display logic - only show if available
                        $wifiDisplay = '';
                        if (strtolower($row['wifi']) === 'yes') {
                            $wifiDisplay = "<span><i class='fas fa-wifi'></i> Free WiFi</span>";
                        }
                        
                        echo "
                        <div class='room-card'>
                            <div class='room-header'>
                                <div class='room-number'>Room {$row['room_no']}</div>
                                <span class='availability {$statusClass}'>{$statusText}</span>
                            </div>
                            <div class='room-type'>
                                <span><i class='fas fa-bed'></i> {$row['beds']} Beds</span>
                                <span><i class='fas fa-snowflake'></i> {$acDisplay}</span>
                                {$wifiDisplay}
                            </div>
                            <div class='room-price'>₹{$row['price']} <span>per night</span></div>
                            <button class='btn' " . ($row['available'] == 'No' ? "disabled style='background-color: #ccc; cursor: not-allowed;'" : "") . ">" . ($row['available'] == 'Yes' ? 'Book Now' : 'Not Available') . "</button>
                        </div>
                        ";
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Dining Section -->
    <section id="dining">
        <div class="container">
            <div class="section-title">
                <h2>Gourmet Dining Experience</h2>
            </div>
            <p class="section-subtitle">Savor authentic coastal cuisine prepared with fresh local ingredients by our expert chefs.</p>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Coastal Cuisine</h3>
                    <p>Fresh, homemade meals prepared with local ingredients and traditional recipes</p>
                    <div class="menu-items">
                        <?php
                        // Fetch dining items from database
                        $dining = $conn->query("SELECT * FROM dining");
                        while($row = $dining->fetch_assoc()){
                            echo "
                            <div class='menu-item'>
                                <span>{$row['name']}</span>
                                <span class='price-tag'>₹{$row['price']}</span>
                            </div>
                            ";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Transport Section -->
    <section id="transport" class="transport-section">
        <div class="container">
            <div class="section-title">
                <h2>Transport Services</h2>
            </div>
            <p class="section-subtitle">We offer convenient transport options to explore Devbag and nearby attractions with ease.</p>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-taxi"></i>
                    </div>
                    <h3>Travel Made Easy</h3>
                    <p>From airport transfers to local sightseeing, we have all your transport needs covered</p>
                    <div class="transport-options">
                        <?php
                        // Fetch transport options from database
                        $transport = $conn->query("SELECT * FROM transport");
                        while($row = $transport->fetch_assoc()){
                            echo "
                            <div class='transport-option'>
                                <span>{$row['name']}</span>
                                <span class='price-tag'>₹{$row['price_per_person']} 
                                </span>
                            </div>
                            ";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Activities Section -->
    <section id="activities">
        <div class="container">
            <div class="section-title">
                <h2>Adventure Activities</h2>
            </div>
            <p class="section-subtitle">Experience thrilling water sports and coastal adventures during your stay at Devbag.</p>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-water"></i>
                    </div>
                    <h3>Sea Adventures</h3>
                    <p>Exciting water activities to make your stay memorable and adventurous</p>
                    <div class="activity-items">
                        <?php
                        // Fetch activities from database
                        $activities = $conn->query("SELECT * FROM activities");
                        while($row = $activities->fetch_assoc()){
                            echo "
                            <div class='activity-item'>
                                <span>{$row['name']}</span>
                                <span class='price-tag'>₹{$row['price_per_person']} </span>
                            </div>
                            ";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
        <div class="container">
            <div class="section-title">
                <h2>Contact & Booking</h2>
            </div>
            <p class="section-subtitle" style="color: white; text-align: center;">Get in touch with us to book your perfect beach getaway for 2025</p>
            <div class="contact-container">
                <div class="contact-info">
                    <h3>Get In Touch</h3>
                    <div class="contact-details">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+91 98765 43210</span>
                        </div>
                        <div class="contact-item">
                            <i class="fab fa-whatsapp"></i>
                            <span>+91 98765 43210</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>bookings@devbagstayz.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Devbag Beach, Sindhudurg, Maharashtra</span>
                        </div>
                    </div>
                    <p>We're available 24/7 to assist with your booking and answer any questions about your 2025 vacation.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-tripadvisor"></i></a>
                    </div>
                </div>
                <div class="booking-promo">
                    <h3>Ready to Book?</h3>
                    <div class="promo-content">
                        <p>Don't miss out on the perfect beach getaway! Our premium rooms are booking fast for 2025.</p>
                        <p>Secure your spot today and experience luxury at Devbag Beach with:</p>
                        <ul class="features-list">
                            <li><i class="fas fa-check"></i> Stunning ocean views</li>
                            <li><i class="fas fa-check"></i> Premium amenities</li>
                            <li><i class="fas fa-check"></i> Gourmet dining</li>
                            <li><i class="fas fa-check"></i> Adventure activities</li>
                            <li><i class="fas fa-check"></i> 24/7 customer support</li>
                        </ul>
                        <p>Limited availability for peak season. Book now to avoid disappointment!</p>
                    </div>
                    <a href="#" class="btn promo-btn">Book Your 2025 Stay Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-container">
                <div class="footer-col">
                    <h4>DevbagStayz</h4>
                    <p>Luxury beachfront accommodations at Devbag Beach. Experience paradise with us in 2025.</p>
                    <div class="social-links" style="margin-top: 20px;">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-tripadvisor"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="#accommodation"><i class="fas fa-chevron-right"></i> Accommodation</a></li>
                        <li><a href="#dining"><i class="fas fa-chevron-right"></i> Dining</a></li>
                        <li><a href="#activities"><i class="fas fa-chevron-right"></i> Activities</a></li>
                        <li><a href="#contact"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <ul>
                        <li><a href="tel:+919876543210"><i class="fas fa-phone"></i> +91 98765 43210</a></li>
                        <li><a href="https://wa.me/919876543210"><i class="fab fa-whatsapp"></i> +91 98765 43210</a></li>
                        <li><a href="mailto:bookings@devbagstayz.com"><i class="fas fa-envelope"></i> bookings@devbagstayz.com</a></li>
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i> Devbag Beach, Sindhudurg</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2023 DevbagStayz. All Rights Reserved. | Book your 2025 vacation now!</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<script>
    // Initialize Swiper for building image sliders
    document.addEventListener('DOMContentLoaded', function() {
        // Block A Swiper
        new Swiper('.swiper-block-a', {
            loop: true,
            pagination: {
                el: '.swiper-block-a .swiper-pagination',
                clickable: true,
            },
            autoplay: {
                delay: 4000,
            },
        });
        
        // Block B Swiper
        new Swiper('.swiper-block-b', {
            loop: true,
            pagination: {
                el: '.swiper-block-b .swiper-pagination',
                clickable: true,
            },
            autoplay: {
                delay: 4000,
            },
        });
        
        // Block C Swiper
        new Swiper('.swiper-block-c', {
            loop: true,
            pagination: {
                el: '.swiper-block-c .swiper-pagination',
                clickable: true,
            },
            autoplay: {
                delay: 4000,
            },
        });

        // Initialize page functionality
        initializePage();
    });

    // Mobile menu toggle
    document.querySelector('.mobile-menu').addEventListener('click', function() {
        document.querySelector('nav').classList.toggle('active');
    });

    // Theme toggle functionality
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle.querySelector('i');
    
    // Check for saved theme preference or default to light
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-theme');
        themeIcon.classList.remove('fa-moon');
        themeIcon.classList.add('fa-sun');
    }
    
    themeToggle.addEventListener('click', function() {
        document.body.classList.toggle('dark-theme');
        
        if (document.body.classList.contains('dark-theme')) {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
            localStorage.setItem('theme', 'dark');
        } else {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
            localStorage.setItem('theme', 'light');
        }
    });

    // Auth Modal functionality
    const authModal = document.getElementById('authModal');
    const loginBtn = document.getElementById('loginBtn');
    const signupBtn = document.getElementById('signupBtn');
    const closeAuth = document.getElementById('closeAuth');
    const authTabs = document.querySelectorAll('.auth-tab');
    const authForms = document.querySelectorAll('.auth-form');

    // Function to open auth modal
    function openAuthModal(tab = 'login') {
        authModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Switch to specified tab
        if (tab === 'signup') {
            authTabs[1].click();
        } else {
            authTabs[0].click();
        }
    }

    // Function to close auth modal
    function closeAuthModal() {
        authModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Only add event listeners if elements exist (for non-logged-in users)
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openAuthModal('login');
        });
    }

    if (signupBtn) {
        signupBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openAuthModal('signup');
        });
    }

    if (closeAuth) {
        closeAuth.addEventListener('click', function() {
            closeAuthModal();
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === authModal) {
            closeAuthModal();
        }
    });

    // Auth tab switching
    authTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Update active tab
            authTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show corresponding form
            authForms.forEach(form => {
                if (form.id === `${targetTab}Form`) {
                    form.style.display = 'block';
                } else {
                    form.style.display = 'none';
                }
            });
        });
    });

    // Remove form submission prevention - let forms submit naturally to auth.php
    // Only add basic validation if needed
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Basic validation - let form submit naturally
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            // Form will submit to auth.php naturally
        });
    }

    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            // Basic validation
            const name = document.getElementById('signupName').value;
            const email = document.getElementById('signupEmail').value;
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('signupConfirm').value;
            
            if (!name || !email || !password || !confirmPassword) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return;
            }
            // Form will submit to auth.php naturally
        });
    }

    // Filter rooms by block
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            const block = this.getAttribute('data-block');
            
            // Show/hide floor containers based on filter
            document.querySelectorAll('.floor-container').forEach(container => {
                if (block === 'all' || container.getAttribute('data-block') === block) {
                    container.style.display = 'block';
                } else {
                    container.style.display = 'none';
                }
            });
        });
    });

    // Book Now buttons in room cards
    document.querySelectorAll('.room-card .btn').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!this.disabled) {
                e.preventDefault();
                // Check if user is logged in
                <?php if (isLoggedIn()): ?>
                    // User is logged in, proceed with booking
                    const roomCard = this.closest('.room-card');
                    const roomNumber = roomCard.querySelector('.room-number').textContent;
                    const roomPrice = roomCard.querySelector('.room-price').textContent;
                    
                    // Show booking confirmation or redirect to booking page
                    if (confirm(`Book ${roomNumber} for ${roomPrice}?`)) {
                        // Here you can redirect to a booking page or process the booking
                        window.location.href = `booking.php?room=${encodeURIComponent(roomNumber)}`;
                    }
                <?php else: ?>
                    // User not logged in, open login modal
                    openAuthModal('login');
                <?php endif; ?>
            }
        });
    });

    // Initialize the page
    function initializePage() {
        // Smooth scrolling for navigation links
        document.querySelectorAll('nav a, .auth-buttons a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetSection = document.querySelector(targetId);
                    
                    if (targetSection) {
                        window.scrollTo({
                            top: targetSection.offsetTop - 80,
                            behavior: 'smooth'
                        });
                        
                        // Close mobile menu if open
                        document.querySelector('nav').classList.remove('active');
                    }
                }
            });
        });

        // Add click event to "Book Your 2025 Stay Now" button
        const promoBtn = document.querySelector('.promo-btn');
        if (promoBtn) {
            promoBtn.addEventListener('click', function(e) {
                e.preventDefault();
                <?php if (isLoggedIn()): ?>
                    // Redirect to accommodation section if logged in
                    window.location.href = '#accommodation';
                <?php else: ?>
                    // Open login modal if not logged in
                    openAuthModal('login');
                <?php endif; ?>
            });
        }

        // Add click event to "Book Your Stay" button in hero section
        const heroBookBtn = document.querySelector('.hero .btn');
        if (heroBookBtn && heroBookBtn.getAttribute('href') === '#accommodation') {
            heroBookBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const targetSection = document.querySelector('#accommodation');
                if (targetSection) {
                    window.scrollTo({
                        top: targetSection.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        }

        // Handle error messages in URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        if (error) {
            // Auto-open auth modal if there's an error
            openAuthModal();
            
            // Show error message (already handled in PHP in the modal)
            console.log('Authentication error:', error);
        }

        // Handle success messages
        const success = urlParams.get('success');
        if (success) {
            alert(success);
            // Remove success parameter from URL
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, '', url);
        }

        // Close mobile menu when clicking on a link
        document.querySelectorAll('nav a').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelector('nav').classList.remove('active');
            });
        });

        // Add loading states to forms
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    submitBtn.disabled = true;
                }
            });
        });

        // Enhance room card interactions
        document.querySelectorAll('.room-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Add parallax effect to hero section
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            if (hero) {
                const parallaxSpeed = 0.5;
                hero.style.transform = `translateY(${scrolled * parallaxSpeed}px)`;
            }
        });

        // Lazy loading for images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    // Keyboard accessibility
    document.addEventListener('keydown', function(e) {
        // Close auth modal on Escape key
        if (e.key === 'Escape' && authModal.style.display === 'block') {
            closeAuthModal();
        }
        
        // Close mobile menu on Escape key
        if (e.key === 'Escape' && document.querySelector('nav').classList.contains('active')) {
            document.querySelector('nav').classList.remove('active');
        }
    });

    // Touch device detection and enhancements
    function isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }

    if (isTouchDevice()) {
        document.body.classList.add('touch-device');
        
        // Add touch-specific enhancements
        document.querySelectorAll('.btn, .auth-btn').forEach(button => {
            button.style.minHeight = '44px';
            button.style.minWidth = '44px';
        });
    }

    // Service Worker registration for PWA (optional)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function(registration) {
                    console.log('ServiceWorker registration successful');
                })
                .catch(function(error) {
                    console.log('ServiceWorker registration failed: ', error);
                });
        });
    }

    // Performance monitoring
    window.addEventListener('load', function() {
        // Log page load time
        const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
        console.log(`Page loaded in ${loadTime}ms`);
        
        // Send to analytics (example)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'timing_complete', {
                'name': 'load',
                'value': loadTime,
                'event_category': 'Load Time'
            });
        }
    });

    // Error handling
    window.addEventListener('error', function(e) {
        console.error('JavaScript Error:', e.error);
        // You can send this to your error tracking service
    });

    // Online/Offline detection
    window.addEventListener('online', function() {
        document.body.classList.remove('offline');
        // Show online notification
        showNotification('You are back online', 'success');
    });

    window.addEventListener('offline', function() {
        document.body.classList.add('offline');
        // Show offline notification
        showNotification('You are currently offline', 'warning');
    });

    // Notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        `;
        
        // Add styles for notification
        if (!document.querySelector('#notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'notification-styles';
            styles.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 5px;
                    color: white;
                    z-index: 10000;
                    max-width: 300px;
                    animation: slideIn 0.3s ease;
                }
                .notification-info { background: #2196F3; }
                .notification-success { background: #4CAF50; }
                .notification-warning { background: #FF9800; }
                .notification-error { background: #f44336; }
                .notification button {
                    background: none;
                    border: none;
                    color: white;
                    margin-left: 10px;
                    cursor: pointer;
                }
                @keyframes slideIn {
                    from { transform: translateX(100%); }
                    to { transform: translateX(0); }
                }
            `;
            document.head.appendChild(styles);
        }
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
</script>
</body>
</html>