<?php
require_once '../includes/header.php';
?>

<div class="max-w-900 mx-auto">
    <div class="support-hero animate-in">
        <div class="support-hero-content">
            <h1>How can we help you today?</h1>
            <p>Our dedicated support team is here to assist you with any queries or issues.</p>
            <div class="mt-20">
                <a href="tickets.php" class="btn btn-light" style="color: var(--primary); font-weight: 700;">
                    <i class="fas fa-ticket-alt"></i> Create Support Ticket
                </a>
            </div>
        </div>
        <div class="support-hero-icon">
            <i class="fas fa-headset"></i>
        </div>
    </div>

    <div class="row mt-30">
        <div class="col-md-4">
            <div class="contact-card animate-in">
                <div class="contact-icon email"><i class="fas fa-envelope"></i></div>
                <h3>Email Support</h3>
                <p>Send us an email for general inquiries.</p>
                <a href="mailto:help@prifypay.com" class="contact-link">help@prifypay.com</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="contact-card animate-in" style="animation-delay: 0.1s;">
                <div class="contact-icon phone"><i class="fas fa-phone-alt"></i></div>
                <h3>Phone Support</h3>
                <p>Call us during business hours.</p>
                <a href="tel:08130485205" class="contact-link">+91 81304 85205</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="contact-card animate-in" style="animation-delay: 0.2s;">
                <div class="contact-icon location"><i class="fas fa-map-marker-alt"></i></div>
                <h3>Head Office</h3>
                <p>Visit our corporate office.</p>
                <address class="contact-address">Basement CDR Building, C-22, Sector-2, Noida, UP - 201301</address>
            </div>
        </div>
    </div>

    <div class="card mt-30 animate-in" style="animation-delay: 0.3s;">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-info-circle"></i> About PrifyPay</h2>
        </div>
        <div class="card-body">
            <p class="mb-15" style="line-height: 1.6; color: var(--text-secondary);">
                PrifyPay is dedicated to empowering small towns and rural communities by providing streamlined access to essential financial and digital services. The platform bridges the gap between urban convenience and rural necessity, allowing individuals to perform critical transactions locally—saving time and increasing financial independence.
            </p>
            <p style="line-height: 1.6; color: var(--text-secondary);">
                Through a network of local service points, PrifyPay offers services such as money transfers, cash management, AEPS, and digital banking, ensuring that even remote areas are connected to modern financial systems.
            </p>
        </div>
    </div>
</div>

<style>
    .support-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        border-radius: 24px;
        padding: 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 20px 40px rgba(79, 70, 229, 0.15);
    }
    .support-hero-content h1 { font-size: 28px; font-weight: 800; margin-bottom: 10px; }
    .support-hero-content p { font-size: 16px; opacity: 0.9; }
    .support-hero-icon { font-size: 80px; opacity: 0.2; }

    .contact-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        height: 100%;
        transition: var(--transition);
    }
    .contact-card:hover { transform: translateY(-5px); border-color: var(--primary); }
    .contact-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin: 0 auto 20px;
    }
    .contact-icon.email { background: rgba(79, 70, 229, 0.1); color: #4f46e5; }
    .contact-icon.phone { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .contact-icon.location { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    
    .contact-card h3 { font-size: 18px; font-weight: 700; margin-bottom: 10px; }
    .contact-card p { font-size: 14px; color: var(--text-muted); margin-bottom: 15px; }
    .contact-link { font-weight: 700; color: var(--primary); text-decoration: none; font-size: 16px; }
    .contact-address { font-size: 14px; color: var(--text-primary); font-style: normal; line-height: 1.5; }
</style>

<?php require_once '../includes/footer.php'; ?>
