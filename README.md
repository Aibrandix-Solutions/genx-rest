# Genx Restaurant - Complete Restaurant Management System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2-blue?style=for-the-badge&logo=php" alt="PHP 8.2">
  <img src="https://img.shields.io/badge/Livewire-3.5-purple?style=for-the-badge" alt="Livewire 3.5">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="MIT License">
</p>

## 📋 Table of Contents

-   [About](#about)
-   [Key Features](#key-features)
-   [Tech Stack](#tech-stack)
-   [Modules](#modules)
-   [Installation](#installation)
-   [Configuration](#configuration)
-   [Payment Gateways](#payment-gateways)
-   [License](#license)

## 🍽️ About

**Genx Restaurant** is a comprehensive, enterprise-grade restaurant management system built on Laravel 12. It provides a complete solution for managing restaurants, from point of sale to inventory management, with support for multi-branch operations, table reservations, kitchen display systems, and customer-facing ordering portals.

### Perfect For:

-   🏢 Single & Multi-Branch Restaurants
-   ☁️ Cloud Kitchens
-   🍕 Quick Service Restaurants (QSR)
-   🍽️ Fine Dining Establishments
-   🚚 Delivery-Only Operations
-   🏨 Hotel F&B Operations

## ✨ Key Features

### 🎯 Core Operations

-   **Advanced POS System** - Feature-rich point of sale with split payments, discounts, taxes, and tips
-   **KOT Management** - Kitchen Order Ticket system with real-time status tracking
-   **Table Management** - Visual table layout, reservation system, and table session tracking
-   **Order Management** - Multi-channel orders (Dine-in, Takeaway, Delivery, Pickup)
-   **Customer Display** - Real-time customer-facing display with multi-display support
-   **QR Code Ordering** - Contactless table ordering via QR codes

### 👥 Customer Experience

-   **Online Ordering Portal** - Customer-facing website with cart and checkout
-   **Table Reservations** - Complete booking system with time slot management
-   **Customer Accounts** - Order history, addresses, and profile management
-   **Multiple Payment Options** - Cash, Card, UPI, Online gateways
-   **Real-time Order Tracking** - Live status updates for customers

### 🍳 Kitchen Operations

-   **Multi-Kitchen Support** - Separate KOTs for different kitchen sections (Grill, Fryer, Beverage, etc.)
-   **Kitchen Display System (KDS)** - Real-time order displays for kitchen staff
-   **Order Status Management** - Pending → Cooking → Ready → Served workflow
-   **Special Instructions** - Item-level notes and modifications
-   **Preparation Time Tracking** - Estimated and actual preparation times

### 📦 Inventory Management (Module)

-   **Stock Management** - Real-time inventory tracking with low-stock alerts
-   **Supplier Management** - Maintain supplier database and contact info
-   **Purchase Orders** - Create, track, and manage POs
-   **Recipe Management** - Link ingredients to menu items
-   **Stock Movements** - Track IN/OUT/WASTE/TRANSFER transactions
-   **Inventory Reports** - Usage, turnover, forecasting, and profit/loss reports
-   **Auto-Reordering** - Automatic PO generation when stock hits threshold
-   **Expiration Tracking** - Monitor and alert for expiring stock

### 📊 Reports & Analytics

-   **Sales Reports** - Detailed revenue analytics by date, time, and period
-   **Item Performance** - Best sellers, revenue by item/category
-   **Payment Analytics** - Breakdown by payment method
-   **Delivery Platform Reports** - Third-party delivery performance
-   **Tax Reports** - Comprehensive tax calculations and summaries
-   **Custom Date Ranges** - Flexible reporting periods with time filters
-   **Export Options** - PDF, Excel, CSV export capabilities

### 💼 Multi-Branch & SaaS Features

-   **Multi-Branch Management** - Centralized control of multiple locations
-   **Branch-Specific Settings** - Customizable menus, pricing, and settings per branch
-   **Subscription Management** - Built-in SaaS billing with Stripe, PayPal, Paddle
-   **License Management** - Package-based feature access control
-   **Restaurant Signup** - Self-service onboarding for new restaurants
-   **Super Admin Panel** - Platform management for SaaS operators

### 🎨 Customization

-   **Menu Management** - Multi-menu support with categories and items
-   **Item Variations** - Size, options, and pricing variations
-   **Modifier Groups** - Toppings, add-ons, and customizations
-   **Dynamic Pricing** - Delivery app-specific and order type pricing
-   **Taxes & Charges** - Flexible tax structures and service charges
-   **Receipt Customization** - Branded receipts with custom settings
-   **Theme Customization** - Custom color themes per restaurant

### 🔧 Technical Features

-   **Thermal Printer Support** - Direct printing to 80mm thermal printers
-   **Multi-Printer Setup** - Kitchen-specific printer routing
-   **Print Queue Management** - Reliable print job tracking
-   **Offline Payment Methods** - Bank transfer, custom payment options
-   **QR Payment Integration** - Display QR codes for payment apps
-   **Role-Based Permissions** - Granular access control with Spatie Permissions
-   **Multi-Language Support** - Translation management system
-   **Multi-Currency** - Support for different currencies per restaurant
-   **PWA Support** - Progressive Web App for mobile devices
-   **Database Backup** - Automated backup and restore functionality

## 🛠️ Tech Stack

### Backend

-   **Framework:** Laravel 12.x
-   **PHP:** 8.2+
-   **Database:** MySQL 8.0+
-   **Real-time:** Laravel Livewire 3.5, Pusher
-   **Authentication:** Laravel Jetstream, Sanctum, Fortify
-   **Permissions:** Spatie Laravel Permission
-   **Queue:** Laravel Queues (Database/Redis)

### Frontend

-   **UI Framework:** Tailwind CSS 3.4
-   **Components:** Flowbite, Preline
-   **Icons:** Font Awesome 6.7, Blade Heroicons
-   **Charts:** ApexCharts 3.49
-   **JavaScript:** Alpine.js (via Livewire)
-   **Build Tool:** Vite 5.0

### Libraries & Packages

-   **PDF Generation:** Laravel DomPDF 3.0
-   **Excel Export:** Maatwebsite Excel 3.1
-   **QR Codes:** Endroid QR Code 5.0
-   **Image Processing:** Intervention Image 2.5
-   **Translation:** Laravel Translation Manager, Spatie Translatable
-   **Notifications:** Livewire Alert, Laravel Vonage
-   **File Storage:** Laravel Flysystem (S3, Local)

### Payment Gateways

-   **Stripe** - Credit/debit cards, subscriptions
-   **Razorpay** - India-focused payments
-   **PayPal** - Global payments and subscriptions
-   **Flutterwave** - African payments
-   **Paystack** - African payments
-   **Payfast** - South African payments
-   **Xendit** - Southeast Asian payments
-   **Paddle** - SaaS subscriptions and billing

### Modular Architecture

-   **Kitchen Module** - Multi-kitchen KOT management
-   **Inventory Module** - Complete stock management
-   **Kiosk Module** - Self-service kiosk interface
-   **Cash Register Module** - Cash drawer management
-   **Backup Module** - Database backup & restore

## 📦 Modules

### 1. Kitchen Module

Advanced multi-kitchen management system for high-volume restaurants.

**Features:**

-   Kitchen-wise item routing and assignment
-   Separate KOT generation per kitchen section
-   Kitchen-specific printer configuration
-   Real-time kitchen display system
-   Status tracking: Pending → Cooking → Ready → Served
-   Admin order view with kitchen breakdown

### 2. Inventory Module

Comprehensive inventory and stock management system.

**Features:**

-   Inventory item management with categories
-   Real-time stock tracking with threshold alerts
-   Supplier database and management
-   Purchase order creation and tracking
-   Recipe management (ingredient linking)
-   Stock movements (IN/OUT/WASTE/TRANSFER)
-   Branch-to-branch stock transfers
-   Expiration date tracking
-   Auto-reorder when stock hits minimum
-   Advanced reporting:
    -   Usage reports
    -   Turnover analysis
    -   Profit & loss by item
    -   Forecasting reports
    -   Cost of goods sold (COGS)

### 3. Kiosk Module

Self-service ordering kiosk interface.

**Features:**

-   Touch-optimized interface
-   Customer-facing self-ordering
-   Payment integration
-   Order confirmation and tracking
-   Customizable branding

### 4. Cash Register Module

Cash drawer and till management.

**Features:**

-   Opening/closing cash counts
-   Cash in/out tracking
-   Shift reconciliation
-   Variance reporting

### 5. Backup & Restore

Database backup management system.

**Features:**

-   Automated backup scheduling
-   Manual backup creation
-   One-click restore
-   Backup file management
-   Export/download backups

## 🚀 Installation

### Prerequisites

```bash
- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Node.js >= 16.x
- NPM or Yarn
```

### Step 1: Clone Repository

```bash
git clone https://github.com/your-username/genx-rest.git
cd genx-rest
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### Step 3: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Database Setup

```bash
# Configure database in .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=genx_restaurant
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

### Step 5: Build Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### Step 6: Storage Link

```bash
php artisan storage:link
```

### Step 7: Start Server

```bash
# Development server
php artisan serve

# Visit: http://localhost:8000
```

## ⚙️ Configuration

### Basic Settings

-   **Restaurant Settings:** Name, logo, contact info, timezone
-   **Currency:** Default currency and formatting
-   **Tax Configuration:** Multiple tax types and rates
-   **Order Types:** Dine-in, Takeaway, Delivery, Pickup
-   **Service Charges:** Configurable charges and fees

### Module Configuration

-   **KOT Settings:** Number formatting, auto-confirm, status labels
-   **Receipt Settings:** Logo, footer text, tax display, currency prefix
-   **Printer Settings:** Thermal printer configuration per kitchen
-   **Payment Settings:** Enable/disable payment methods
-   **Reservation Settings:** Time slots, default status, booking rules

### Advanced Settings

-   **SMTP Configuration:** Email notifications setup
-   **Pusher Configuration:** Real-time updates
-   **Storage Settings:** Local, S3, or cloud storage
-   **SMS Notifications:** Vonage/Twilio integration
-   **Localization:** Multi-language support
-   **Custom Domains:** White-label support for SaaS

## 💳 Payment Gateways

### Supported Gateways

| Gateway         | Regions        | Types                 | Subscription Support |
| --------------- | -------------- | --------------------- | -------------------- |
| **Stripe**      | Global         | Cards, Wallets        | ✅ Yes               |
| **Razorpay**    | India          | Cards, UPI, Wallets   | ✅ Yes               |
| **PayPal**      | Global         | Cards, PayPal Balance | ✅ Yes               |
| **Flutterwave** | Africa         | Cards, Mobile Money   | ✅ Yes               |
| **Paystack**    | Africa         | Cards, Bank Transfer  | ✅ Yes               |
| **Payfast**     | South Africa   | Cards, EFT            | ✅ Yes               |
| **Xendit**      | Southeast Asia | Cards, E-wallets      | ✅ Yes               |
| **Paddle**      | Global         | SaaS Subscriptions    | ✅ Yes               |

### Configuration

Each gateway requires API credentials configured in Settings → Payment Settings.

**Webhook URLs** are provided in the payment settings for automated payment verification.

## 🔐 Security Features

-   CSRF Protection (Laravel)
-   SQL Injection Prevention (Eloquent ORM)
-   XSS Protection
-   Two-Factor Authentication (2FA)
-   Role-Based Access Control (RBAC)
-   Session Management
-   Password Encryption (bcrypt)
-   API Token Authentication (Sanctum)

## 📱 Mobile Support

-   Responsive design for all screen sizes
-   PWA (Progressive Web App) support
-   Touch-optimized interface
-   QR code scanning support
-   Mobile-friendly reports

## 🌐 Multi-Tenancy

-   SaaS-ready architecture
-   Subdomain or custom domain support
-   Per-restaurant databases (optional)
-   Isolated data per tenant
-   Central billing and management

## 📈 Performance

-   Query optimization with Eloquent
-   Database indexing
-   Caching (Redis/Memcached support)
-   Asset optimization (Vite)
-   Lazy loading
-   Background job processing

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter CustomerDisplayTest
```

## 📞 Support & Documentation

-   **Documentation:** Coming soon
-   **Issues:** GitHub Issues
-   **Email:** support@example.com

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

## 📄 License

This project is licensed under the [MIT License](LICENSE).

## 👨‍💻 Development Team

Developed by **Aibrandix Solutions**

---

<p align="center">Made with ❤️ for Restaurant Owners Worldwide</p>
