<?php
require_once '../includes/header.php';
?>

<div class="max-w-1000 mx-auto">
    <!-- Hero Section -->
    <div class="support-hero animate-in">
        <div class="support-hero-content">
            <span class="support-badge"><i class="fas fa-life-ring"></i> 24/7 Assistance</span>
            <h1>How can we help you today?</h1>
            <p>Search our knowledge base or get in touch with our experts.</p>
            
            <div class="support-search mt-25">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Type your question here (e.g. payout status, kyc process)...">
            </div>
        </div>
        <div class="support-hero-visual">
            <i class="fas fa-headset"></i>
        </div>
    </div>

    <!-- Quick Actions Grid -->
    <div class="row mb-30">
        <div class="col-md-6">
            <div class="support-action-card animate-in">
                <div class="action-content">
                    <h3>Technical Support</h3>
                    <p>Encountering issues with transactions or API? Create a ticket for quick resolution.</p>
                    <a href="tickets.php" class="btn btn-primary">
                        <i class="fas fa-ticket-alt"></i> Raise a Support Ticket
                    </a>
                </div>
                <div class="action-icon"><i class="fas fa-bug"></i></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="support-action-card animate-in" style="animation-delay: 0.1s; background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <div class="action-content">
                    <h3>Instant Chat</h3>
                    <p>Connect with our support team instantly on WhatsApp for real-time assistance.</p>
                    <a href="https://wa.me/918130485205" target="_blank" class="btn btn-light" style="color: #059669; font-weight: 700;">
                        <i class="fab fa-whatsapp"></i> Chat on WhatsApp
                    </a>
                </div>
                <div class="action-icon"><i class="fab fa-whatsapp" style="opacity: 0.15;"></i></div>
            </div>
        </div>
    </div>

    <!-- Contact Channels -->
    <div class="row">
        <div class="col-md-4">
            <div class="support-channel animate-in" style="animation-delay: 0.2s;">
                <div class="channel-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;"><i class="fas fa-envelope-open-text"></i></div>
                <h4>Email Us</h4>
                <p>Detailed queries & documentation</p>
                <a href="mailto:help@prifypay.com">help@prifypay.com</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="support-channel animate-in" style="animation-delay: 0.3s;">
                <div class="channel-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-phone-volume"></i></div>
                <h4>Call Support</h4>
                <p>Mon-Sat (10 AM - 7 PM)</p>
                <a href="tel:08130485205">+91 81304 85205</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="support-channel animate-in" style="animation-delay: 0.4s;">
                <div class="channel-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-map-location-dot"></i></div>
                <h4>Visit Us</h4>
                <p>Corporate Headquarters</p>
                <span class="small-address">Sector-2, Noida, UP - 201301</span>
            </div>
        </div>
    </div>

    <!-- Info Section -->
    <div class="card mt-40 animate-in" style="animation-delay: 0.5s;">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-shield-check"></i> PrifyPay Commitment</h2>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <p class="text-secondary lead mb-0">
                        We are committed to providing a <strong>Safe, Secure, and Seamless</strong> financial experience. Our support team is trained to handle complex financial reconciliations and technical integrations with priority.
                    </p>
                </div>
                <div class="col-lg-4 text-center">
                    <div style="font-size: 50px; color: var(--primary); opacity: 0.2;">
                        <i class="fas fa-building-columns"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .support-hero {
        background: var(--primary-gradient);
        border-radius: 30px;
        padding: 60px 50px;
        color: #ffffff !important;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(99, 102, 241, 0.25);
    }
    .support-hero::before {
        content: ""; position: absolute; top: -50%; left: -20%; width: 60%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%); transform: rotate(30deg);
    }
    .support-badge {
        display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); padding: 6px 16px; border-radius: 30px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; color: #ffffff;
    }
    .support-hero h1 { font-size: 36px; font-weight: 900; letter-spacing: -1px; margin-bottom: 10px; color: #ffffff; }
    .support-hero p { font-size: 18px; opacity: 0.95; font-weight: 500; color: #ffffff; }
    
    .support-search {
        position: relative; max-width: 500px; background: #ffffff !important; border-radius: 16px; padding: 5px; display: flex; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.15); margin-top: 25px;
    }
    .support-search i { position: absolute; left: 20px; color: var(--text-muted); font-size: 18px; }
    .support-search input {
        width: 100%; border: none; padding: 15px 15px 15px 55px; border-radius: 12px; font-size: 15px; font-weight: 500; color: var(--text-primary);
    }
    .support-search input:focus { outline: none; }
    
    .support-hero-visual { font-size: 120px; opacity: 0.15; transform: rotate(-15deg); }

    .support-action-card {
        background: var(--primary-gradient);
        border-radius: 24px;
        padding: 35px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 100%;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    .support-action-card:hover { transform: translateY(-5px); }
    .action-content { position: relative; z-index: 2; max-width: 75%; }
    .action-content h3 { font-size: 22px; font-weight: 800; margin-bottom: 12px; }
    .action-content p { font-size: 14px; opacity: 0.9; margin-bottom: 20px; line-height: 1.5; }
    .action-icon { position: absolute; right: -20px; bottom: -20px; font-size: 100px; opacity: 0.1; transform: rotate(-10deg); }

    .support-channel {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 24px;
        padding: 30px;
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
    }
    .support-channel:hover { border-color: var(--primary); transform: translateY(-5px); box-shadow: var(--shadow-lg); }
    .channel-icon {
        width: 64px; height: 64px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px;
    }
    .support-channel h4 { font-size: 18px; font-weight: 700; margin-bottom: 8px; color: var(--text-primary); }
    .support-channel p { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; }
    .support-channel a { font-size: 16px; font-weight: 800; color: var(--primary); transition: color 0.2s; }
    .support-channel a:hover { color: var(--primary-dark); }
    .small-address { font-size: 13px; font-weight: 600; color: var(--text-secondary); }

    .lead { font-size: 18px; line-height: 1.6; }
</style>

<?php require_once '../includes/footer.php'; ?>
