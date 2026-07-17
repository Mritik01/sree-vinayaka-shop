<?php

namespace App\Console\Commands;

use App\Models\LegalDocumentVersion;
use Illuminate\Console\Command;

// One-time operational command — not a fresh-install seeder run automatically. Creates version 1
// of the Terms & Conditions and Privacy Policy if the site has none yet, so the admin panel and
// the new /terms, /privacy pages start out with real, business-specific content instead of an
// empty editor. Safe to re-run: skips any type that already has a version.
class SeedLegalDocuments extends Command
{
    protected $signature = 'legal:seed';

    protected $description = 'Create the initial version of the Terms & Conditions and Privacy Policy if none exist yet';

    public function handle(): int
    {
        if (!LegalDocumentVersion::current('terms')) {
            LegalDocumentVersion::create([
                'type' => 'terms',
                'version' => 1,
                'title' => 'Terms & Conditions',
                'content' => $this->terms(),
                'published_at' => now(),
                'created_by' => null,
            ]);
            $this->info('Terms & Conditions v1 created.');
        } else {
            $this->info('Terms & Conditions already exist — skipped.');
        }

        if (!LegalDocumentVersion::current('privacy')) {
            LegalDocumentVersion::create([
                'type' => 'privacy',
                'version' => 1,
                'title' => 'Privacy Policy',
                'content' => $this->privacy(),
                'published_at' => now(),
                'created_by' => null,
            ]);
            $this->info('Privacy Policy v1 created.');
        } else {
            $this->info('Privacy Policy already exist — skipped.');
        }

        return self::SUCCESS;
    }

    private function terms(): string
    {
        return <<<'HTML'
<h2>1. Introduction</h2>
<p>Welcome to Makhanbhog Sweets. These Terms &amp; Conditions ("Terms") govern your access to and use of our website and mobile-friendly ordering platform (the "Platform"), operated from Thuthibari, for browsing, ordering, and receiving delivery of sweets, namkeen, and related food products ("Products"). By creating an account, placing an order, or otherwise using the Platform, you agree to be bound by these Terms and by our Privacy Policy. If you do not agree, please do not use the Platform.</p>

<h2>2. Eligibility</h2>
<p>You must be at least 18 years old, or using the Platform under the supervision of a parent or guardian, to create an account and place orders. By registering, you confirm that the mobile number and information you provide are accurate and belong to you.</p>

<h2>3. Account Registration &amp; OTP Login</h2>
<p>Accounts are created and accessed using your mobile number verified by a One-Time Password (OTP) — we do not use passwords for customer accounts. You are responsible for keeping your registered mobile number secure and for all activity that occurs through your account. Notify us immediately if you suspect unauthorized access. Providing accurate, current registration details, including your name and delivery address, is your responsibility, and inaccurate information may delay or prevent delivery.</p>

<h2>4. Product Information &amp; Pricing</h2>
<p>We make reasonable efforts to display accurate product descriptions, weights/portions, and prices. Loose items are priced per portion (e.g. 250g, 500g, 750g, 1kg) as shown on the product page; piece-based items are priced per unit. Prices are inclusive of applicable taxes unless stated otherwise and may change without prior notice, though a price change will never affect an order already placed and confirmed. Product images are for illustration; actual appearance, shape, or garnish may vary slightly.</p>

<h2>5. Orders &amp; Order Acceptance</h2>
<p>Placing an order is an offer to purchase, which we may accept or decline. An order is confirmed only once we notify you of acceptance (shown as "Confirmed" on your order tracking page) — a "Placed" status alone does not guarantee fulfilment. We may decline or cancel an order for reasons including product unavailability, an inaccurate or undeliverable address, suspected fraud or abuse, or being outside our current delivery area. Minimum and/or maximum order values may apply and are shown at checkout when in effect.</p>

<h2>6. Payments</h2>
<p>We currently accept Cash on Delivery (COD) and online payment via Razorpay (cards, UPI, netbanking, and wallets as offered by Razorpay). Online payments are processed securely by Razorpay; we do not store your card, UPI, or bank credentials. If you have previously not accepted a COD order, we may require your next order(s) to be prepaid online before COD is offered to you again — this is shown clearly at checkout when it applies.</p>

<h2>7. Delivery &amp; Shipping</h2>
<p>We currently deliver within a defined radius of our Thuthibari location; if your delivery address falls outside this area, the Platform will inform you at checkout and the order cannot be placed. Estimated delivery times shown on your order are our best estimate, not a guaranteed delivery window, and may be affected by weather, traffic, order volume, or circumstances beyond our control. Please ensure someone is available to receive the order at the address and time provided.</p>

<h2>8. Cancellation, Return &amp; Refund Policy</h2>
<p>You may cancel an order yourself within the short cancellation window shown on your order tracking page, before we begin preparing it; once that window has passed or the order is out for delivery, please contact us directly and we will do our best to accommodate reasonable requests. Because our Products are freshly prepared, perishable food items, we generally cannot accept returns once an order has been delivered, except where the item received is materially different from what was ordered, damaged, or spoiled on arrival — please contact us with photos within a reasonable time of delivery so we can make it right. Where an order paid for online is cancelled or refused, the amount paid is refunded automatically to your original payment method; Cash on Delivery orders that are cancelled naturally involve no charge. Repeated refusal of Cash on Delivery orders may result in future orders requiring prepayment.</p>

<h2>9. Promotions &amp; Coupons</h2>
<p>From time to time we offer coupon codes, festival specials, and a loyalty rewards program. Coupons are subject to the specific terms shown at the time of use (such as minimum order value, expiry date, or single-use/one-per-customer limits) and may be withdrawn, modified, or restricted to specific customers at our discretion. Coupons and loyalty rewards have no cash value, cannot be exchanged for cash, and any attempt to abuse, duplicate, or fraudulently obtain a coupon or reward may result in the coupon being voided and the account being reviewed or suspended.</p>

<h2>10. User Responsibilities</h2>
<p>You agree to use the Platform only for lawful purposes and to provide accurate order and delivery information. You agree not to misuse the Platform, including attempting to interfere with its normal operation, submitting fraudulent orders or payment information, or abusing promotions, coupons, or the rewards program. Reviews and any content you submit (including photos) must be honest, must relate to your own genuine purchase experience, and must not be unlawful, defamatory, or infringe anyone else's rights.</p>

<h2>11. Privacy &amp; Data Protection</h2>
<p>Our collection and use of your personal information is described in full in our <a href="/privacy">Privacy Policy</a>, which forms part of these Terms. By using the Platform, you also agree to the Privacy Policy.</p>

<h2>12. Limitation of Liability</h2>
<p>To the fullest extent permitted by law, Makhanbhog Sweets shall not be liable for any indirect, incidental, or consequential loss arising from your use of the Platform or from delays, unavailability of products, or circumstances beyond our reasonable control. Nothing in these Terms limits any liability that cannot be excluded under applicable Indian law, including in relation to food safety.</p>

<h2>13. Account Suspension</h2>
<p>We may suspend or terminate an account, without prior notice, where we reasonably believe it has been used for fraud, abuse of coupons or the rewards program, repeated refusal of Cash on Delivery orders, or any other breach of these Terms. You may request that your account be closed at any time by contacting us using the details below.</p>

<h2>14. Governing Law</h2>
<p>These Terms are governed by the laws of India. Any dispute arising out of or in connection with these Terms shall be subject to the exclusive jurisdiction of the competent courts having jurisdiction over Thuthibari.</p>

<h2>15. Contact Information</h2>
<p>For any questions about these Terms, please reach us at:</p>
<ul>
<li>Phone: +91 89209 37331</li>
<li>WhatsApp: <a href="https://wa.me/918920937331">wa.me/918920937331</a></li>
<li>Address: Main Market Road, Thuthibari</li>
</ul>
HTML;
    }

    private function privacy(): string
    {
        return <<<'HTML'
<h2>1. Introduction</h2>
<p>This Privacy Policy explains how Makhanbhog Sweets ("we", "us") collects, uses, stores, and protects your personal information when you use our website and ordering platform (the "Platform"). It should be read together with our <a href="/terms">Terms &amp; Conditions</a>.</p>

<h2>2. Information We Collect</h2>
<p>We collect the information you provide directly, such as your name, mobile number, and delivery address(es) when you register or place an order. We also collect information generated by your use of the Platform, including order history, cart activity, product views, favourites, coupon and reward usage, and — where you contact us — support chat messages and any photos you choose to attach. To keep the Platform secure and to improve it, we also collect basic technical information such as IP address, device/browser type, and general usage activity, and, where you choose to allow it, precise location to confirm you're within our delivery area.</p>

<h2>3. How We Use Your Information</h2>
<p>We use your information to create and manage your account, verify your identity via OTP, process and deliver your orders, process payments and refunds, provide customer support, operate our loyalty rewards and coupon programs, and communicate with you about your orders (such as status updates) and, where relevant, promotions. We also use aggregated or anonymized usage data to understand how the Platform is used and to improve it.</p>

<h2>4. OTP &amp; Account Security</h2>
<p>Account access is via mobile number and OTP rather than a password. OTPs are single-use, time-limited, and sent only to the mobile number you provide; we never ask you to share an OTP with anyone, and our staff will never request it from you.</p>

<h2>5. Payment Information</h2>
<p>Online payments are processed by Razorpay, a licensed and regulated payment gateway. We do not collect or store your full card number, UPI PIN, or net-banking credentials — these are handled directly by Razorpay under its own security and privacy standards. We retain only the payment status, method, and transaction reference needed to confirm and, where necessary, refund your order.</p>

<h2>6. Sharing of Information</h2>
<p>We share your delivery details (name, phone number, and address) with our delivery riders solely to complete your delivery, and share payment details with Razorpay solely to process payments and refunds. We do not sell your personal information to third parties. We may disclose information where required by law, to protect our rights, or to investigate suspected fraud or abuse of the Platform.</p>

<h2>7. Data Retention</h2>
<p>We retain your account and order information for as long as your account is active and for a reasonable period afterward, as needed for legal, accounting, dispute-resolution, and fraud-prevention purposes. Detailed browsing/activity logs are retained only for a limited recent window and are not kept indefinitely.</p>

<h2>8. Your Rights &amp; Choices</h2>
<p>You can view and update your name, delivery addresses, and locale preferences from your account at any time. You may request a copy of the personal information we hold about you, request correction of inaccurate information, or request that your account be closed, by contacting us using the details below; we will respond within a reasonable time, subject to any information we are legally required to retain.</p>

<h2>9. Cookies &amp; Similar Technologies</h2>
<p>We use session cookies and similar local storage to keep you logged in, remember your cart, and understand how the Platform is used. These are necessary for the Platform to function correctly; disabling them may prevent core features such as staying logged in or checkout from working properly.</p>

<h2>10. Children's Privacy</h2>
<p>The Platform is not directed at children, and accounts should be created and used by, or under the supervision of, an adult.</p>

<h2>11. Data Security</h2>
<p>We take reasonable technical and organizational measures to protect your personal information against unauthorized access, alteration, or loss. No method of transmission or storage is completely secure, and while we work to protect your information, we cannot guarantee absolute security.</p>

<h2>12. Changes to This Policy</h2>
<p>We may update this Privacy Policy from time to time to reflect changes in our practices or for legal reasons. When we do, the "Last Updated" date at the top of this page will change, and if you have already created an account, you will be asked to review and accept the updated policy the next time you use the Platform.</p>

<h2>13. Contact Information</h2>
<p>For any questions about this Privacy Policy or to exercise your rights over your personal information, please reach us at:</p>
<ul>
<li>Phone: +91 89209 37331</li>
<li>WhatsApp: <a href="https://wa.me/918920937331">wa.me/918920937331</a></li>
<li>Address: Main Market Road, Thuthibari</li>
</ul>
HTML;
    }
}
