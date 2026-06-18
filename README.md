# 🗺️ Land Surveying Services Portal System

A complete, professional web-based portal system for land surveying services built with PHP (OOP), MySQL, HTML, CSS, and JavaScript (AJAX).

## ✨ Features

### 🎯 Core Functionality
- **Multi-User System**: Client, Engineer, and Admin roles with separate dashboards
- **Real-Time Booking**: Airbnb-style calendar with available/unavailable date indicators
- **Live Tracking**: Real-time appointment status updates
- **AI Chatbot**: 24/7 automated assistance for inquiries
- **Payment System**: TikTok-style smooth payment submission with proof upload
- **Messaging**: Real-time chat between clients and engineers with AI auto-reply
- **Email Notifications**: Gmail SMTP integration for appointment confirmations

### 👥 User Roles

#### Client Features
- Browse engineers with ratings and availability
- View company profiles and services
- Book appointments with calendar selection
- Track survey progress in real-time
- Submit payments with proof upload
- Send messages to engineers
- Leave feedback and ratings
- View personal profile and history

#### Engineer Features
- Manage availability schedule
- Accept/reject appointments
- Update project progress with photos
- Set hourly rates and services
- View assigned appointments
- Communicate with clients
- Update professional profile

#### Admin Features
- Dashboard with system overview
- Verify/reject payments
- Manage appointments
- Manage engineers and companies
- View feedback and ratings
- Manage schedules
- System-wide notifications

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for PHPMailer)

### Setup Steps

1. **Clone/Download the project**
   ```bash
   # Place files in your web server directory
   # Example: C:/xampp/htdocs/land-surveying-portal
   ```

2. **Create Database**
   ```bash
   # Import the SQL file
   mysql -u root -p < database/land_surveying.sql
   ```
   Or use phpMyAdmin to import `database/land_surveying.sql`

3. **Configure Database Connection**
   Edit `config/database.php`:
   ```php
   private $host = "localhost";
   private $db_name = "land_surveying_db";
   private $username = "root";
   private $password = ""; // Your MySQL password
   ```

4. **Configure Base URL**
   Edit `config/config.php`:
   ```php
   define('BASE_URL', 'http://localhost/land-surveying-portal');
   ```

5. **Set Up Email (Optional)**
   Edit `config/config.php` for Gmail SMTP:
   ```php
   define('SMTP_USERNAME', 'your-email@gmail.com');
   define('SMTP_PASSWORD', 'your-app-password');
   ```
   Note: Use Gmail App Password, not your regular password

6. **Create Upload Directories**
   The system will auto-create these, but you can manually create:
   ```
   uploads/profiles/
   uploads/payments/
   uploads/companies/
   uploads/progress/
   ```

7. **Access the System**
   Open your browser and navigate to:
   ```
   http://localhost/land-surveying-portal
   ```

## 🔑 Default Login Credentials

### Client Account
- **Email**: client@test.com
- **Password**: 123456

### Engineer Account
- **Email**: engineer@test.com
- **Password**: 123456

### Admin Account
- **Email**: admin@test.com
- **Password**: 123456

## 📁 Project Structure

```
land-surveying-portal/
├── api/                    # AJAX API endpoints
│   ├── appointments.php
│   ├── chatbot.php
│   ├── messages.php
│   └── notifications.php
├── assets/                 # Frontend assets
│   ├── css/
│   │   ├── main.css
│   │   ├── dashboard.css
│   │   ├── auth.css
│   │   ├── booking.css
│   │   └── ...
│   ├── js/
│   │   ├── chatbot.js
│   │   ├── booking.js
│   │   ├── dashboard.js
│   │   └── landing.js
│   └── images/
├── auth/                   # Authentication
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── client/                 # Client module
│   ├── dashboard.php
│   ├── engineers.php
│   ├── engineer-profile.php
│   ├── companies.php
│   ├── book-appointment.php
│   ├── track-status.php
│   ├── payment.php
│   ├── messages.php
│   ├── feedback.php
│   └── profile.php
├── engineer/               # Engineer module
│   ├── dashboard.php
│   ├── appointments.php
│   ├── schedule.php
│   ├── progress.php
│   ├── messages.php
│   └── profile.php
├── admin/                  # Admin module
│   ├── dashboard.php
│   ├── appointments.php
│   ├── payments.php
│   ├── engineers.php
│   ├── companies.php
│   ├── schedules.php
│   ├── feedback.php
│   └── users.php
├── config/                 # Configuration
│   ├── config.php
│   └── database.php
├── database/               # Database schema
│   └── land_surveying.sql
├── helpers/                # Helper classes
│   ├── AIHelper.php
│   └── EmailHelper.php
├── includes/               # Shared components
│   ├── header.php
│   ├── sidebar_client.php
│   ├── sidebar_engineer.php
│   ├── sidebar_admin.php
│   └── chatbot.php
├── models/                 # Data models (OOP)
│   ├── User.php
│   ├── Engineer.php
│   ├── Appointment.php
│   ├── Payment.php
│   ├── Message.php
│   ├── Company.php
│   ├── Notification.php
│   └── Schedule.php
├── uploads/                # User uploads
│   ├── profiles/
│   ├── payments/
│   ├── companies/
│   └── progress/
├── index.php               # Landing page
└── README.md
```

## 🎨 Design Features

- **Modern UI/UX**: Gradient colors, smooth animations, responsive design
- **Social Media Style**: Facebook/LinkedIn inspired interface
- **Professional Look**: Startup-level design quality
- **Mobile Responsive**: Works on all devices
- **Interactive Elements**: Hover effects, transitions, loading states

## 🔧 Technologies Used

- **Backend**: PHP 7.4+ (Object-Oriented Programming)
- **Database**: MySQL with PDO
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **AJAX**: Fetch API for asynchronous operations
- **Icons**: Font Awesome 6
- **Fonts**: Google Fonts (Inter)
- **Email**: PHP mail() / Gmail SMTP

## 📊 Database Schema

The system includes 13 tables:
- `users` - User accounts
- `engineers` - Engineer profiles
- `companies` - Surveying companies
- `appointments` - Booking records
- `schedules` - Engineer availability
- `payments` - Payment transactions
- `feedback` - Ratings and reviews
- `messages` - Chat system
- `notifications` - System notifications
- `progress_updates` - Survey progress
- `services` - Service catalog

## 🤖 AI Features

- **Chatbot**: Keyword-based responses for common inquiries
- **Auto-Reply**: Automated message responses
- **Slot Suggestions**: AI-recommended appointment times
- **Smart Matching**: Auto-assign best available engineer

## 📧 Email Notifications

Automated emails sent for:
- Appointment confirmations
- Payment verifications
- Status updates
- Engineer assignments

## 🔒 Security Features

- Password hashing (bcrypt)
- SQL injection prevention (PDO prepared statements)
- XSS protection (htmlspecialchars)
- Session management
- Role-based access control
- File upload validation

## 🐛 Troubleshooting

### Database Connection Error
- Check MySQL is running
- Verify credentials in `config/database.php`
- Ensure database exists

### Upload Directory Errors
- Check folder permissions (777 for uploads/)
- Verify paths in `config/config.php`

### Email Not Sending
- Use Gmail App Password
- Enable "Less secure app access" (if needed)
- Check SMTP settings

### Base URL Issues
- Update `BASE_URL` in `config/config.php`
- Match your actual server path

## 📝 Notes

- Default password for all demo accounts: **123456**
- Change passwords in production
- Configure email settings for notifications
- Seed data includes 5 companies and 6 engineers
- AI responses are keyword-based (can integrate real AI API)

## 🎯 System Flow

```
INPUT (Client Actions)
  ↓
PROCESS (System Logic + AI)
  ↓
OUTPUT (Confirmation + Notifications)
```

### Example: Booking Flow
1. Client selects engineer
2. System shows available slots
3. AI suggests best time
4. Client confirms booking
5. System auto-assigns engineer
6. Email confirmation sent
7. Engineer receives notification

## 🌟 Key Highlights

✅ Complete MVC-style architecture
✅ Object-Oriented PHP
✅ Real-time features
✅ Professional UI/UX
✅ Mobile responsive
✅ AI integration ready
✅ Email notifications
✅ Payment system
✅ Chat functionality
✅ Calendar booking
✅ Progress tracking
✅ Rating system

## 📞 Support

For issues or questions:
1. Check this README
2. Review code comments
3. Check browser console for errors
4. Verify database connection

## 📄 License

This project is for educational purposes.

---

**Built with ❤️ for professional land surveying services**
