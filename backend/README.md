# Core Official Backend - PHP + MySQL

Complete production-ready backend system for Core Official frontend.

## 🚀 Quick Start

### 1. Database Setup

```bash
# Create database and import schema
mysql -u root -p < database.sql

# Or manually:
mysql -u root -p
> CREATE DATABASE core_official;
> USE core_official;
> SOURCE database.sql;
```

### 2. Configuration

```bash
# Copy environment file
cp .env.example .env

# Edit .env with your settings
nano .env
```

**Required settings:**
- `DB_HOST` - MySQL host (default: localhost)
- `DB_NAME` - Database name (default: core_official)
- `DB_USER` - Database username
- `DB_PASS` - Database password

### 3. Set Permissions

```bash
# Make uploads directory writable
chmod -R 755 uploads/
chown -R www-data:www-data uploads/

# Secure config files
chmod 600 .env
chmod 600 config/*.php
```

### 4. Web Server Configuration

#### Apache (.htaccess)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /backend/

    # Deny access to sensitive files
    <FilesMatch "\.(env|sql|log)$">
        Order allow,deny
        Deny from all
    </FilesMatch>
</IfModule>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html;
    index index.php;

    location /backend/ {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Deny access to sensitive files
    location ~* \.(env|sql|log)$ {
        deny all;
    }
}
```

### 5. Change Default Admin Password

```bash
# Create hash_password.php
cat > hash_password.php << 'EOF'
<?php
$password = 'YourNewSecurePassword123!';
echo password_hash($password, PASSWORD_DEFAULT) . "\n";
?>
EOF

# Generate hash
php hash_password.php

# Update database
mysql -u root -p core_official
> UPDATE users SET password = 'PASTE_HASH_HERE' WHERE email = 'admin@coreofficial.com';

# Remove temporary file
rm hash_password.php
```

## 📁 Directory Structure

```
backend/
├── config/
│   ├── config.php       # Main configuration
│   └── db.php          # Database connection class
├── auth/
│   ├── login.php       # Login page
│   ├── register.php    # Registration page
│   └── logout.php      # Logout handler
├── api/
│   ├── contact.php     # Contact form API
│   ├── career.php      # Career/job application API
│   ├── crud.php        # Generic CRUD API
│   └── upload.php      # File upload API
├── admin/
│   ├── index.php       # Dashboard
│   ├── ecosystem.php   # Ecosystem management
│   ├── products.php    # Products management
│   ├── news.php        # News/blog management
│   ├── contacts.php    # Contact messages
│   ├── careers.php     # Careers & applications
│   └── users.php       # User management
├── includes/
│   ├── header.php      # Admin panel header
│   ├── footer.php      # Admin panel footer
│   └── auth_check.php  # Authentication middleware
├── uploads/
│   ├── cv/            # CV/resume files
│   ├── news/          # News images
│   └── products/      # Product images
├── .env.example       # Environment variables template
├── database.sql       # Database schema & sample data
└── README.md         # This file
```

## 🔐 Security Features

- **Password Hashing**: bcrypt via `password_hash()`
- **CSRF Protection**: Token-based validation on all forms
- **SQL Injection Protection**: PDO prepared statements
- **XSS Prevention**: Input sanitization and output escaping
- **Session Security**: Secure cookies, timeout, regeneration
- **File Upload Validation**: Type, size, and MIME type checks
- **Rate Limiting**: IP-based throttling on public APIs
- **Activity Logging**: All admin actions tracked

## 🔌 API Endpoints

### Public APIs (No Auth Required)

#### Contact Form
```bash
POST /backend/api/contact.php
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "company": "Example Corp",
  "subject": "Inquiry",
  "message": "Hello..."
}
```

#### Career Listings
```bash
GET /backend/api/career.php
# Returns all open positions
```

#### Career Application
```bash
POST /backend/api/career.php
Content-Type: multipart/form-data

career_id: 1
full_name: John Doe
email: john@example.com
phone: +1234567890
cv: [file]
cover_letter: I am interested...
linkedin: https://linkedin.com/in/johndoe
```

### Admin APIs (Auth Required)

#### Generic CRUD
```bash
# List items
GET /backend/api/crud.php?table=products&limit=50&offset=0

# Get single item
GET /backend/api/crud.php?table=products&id=1

# Create item
POST /backend/api/crud.php?table=products
{
  "csrf_token": "...",
  "name": "Product Name",
  "description": "...",
  "status": "active"
}

# Update item
PUT /backend/api/crud.php?table=products&id=1
{
  "csrf_token": "...",
  "name": "Updated Name"
}

# Delete item
DELETE /backend/api/crud.php?table=products&id=1
```

**Supported tables**: `ecosystem`, `products`, `news`, `careers`, `users`

#### File Upload
```bash
POST /backend/api/upload.php
Content-Type: multipart/form-data

type: products
file: [file]

# Returns: { "filename": "...", "url": "...", "size": 12345 }
```

## 📊 Database Schema

### Users
- Email/password authentication
- Role-based access (user/admin)
- Status tracking (active/inactive/suspended)

### Ecosystem
- Company services/products ecosystem
- Icon, description, links
- Display ordering

### Products
- Product catalog
- Pricing, categories
- JSON fields for features/specs

### News
- Blog/news articles
- Categories, tags (JSON)
- Author tracking, view counts
- Draft/published/archived status

### Contacts
- Form submissions
- Status tracking (new/read/replied/archived)
- Rate limiting by IP

### Careers
- Job postings
- Department, location, type
- Requirements, salary range

### Career Applications
- Applicant information
- CV file storage
- Application status workflow
- Cover letter, portfolio links

### Activity Logs
- User action tracking
- IP and user agent logging
- Entity type and ID reference

## 🎨 Admin Panel

Access at: `/backend/auth/login.php`

**Default credentials:**
- Email: `admin@coreofficial.com`
- Password: `Admin@123` (CHANGE IMMEDIATELY)

### Features:
- Dashboard with statistics
- CRUD operations for all entities
- File upload management
- Contact message handling
- Career application review
- User management
- Activity logs
- Real-time status updates

## 🛠️ Development

### Enable Debug Mode

```php
// In config/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

**Disable in production!**

### Add New Entity

1. Create table in `database.sql`
2. Add table name to `$allowed_tables` in `api/crud.php`
3. Create admin page in `admin/entity_name.php`
4. Add menu item in `includes/header.php`

### Custom API Endpoint

```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');
require_auth(true); // Admin only

try {
    // Your logic here
    success_response('Success', ['data' => $result]);
} catch (Exception $e) {
    error_log('API error: ' . $e->getMessage());
    error_response('Error occurred', 500);
}
```

## 📝 Integration with Frontend

### Contact Form Example

```html
<form id="contactForm">
  <input name="name" required>
  <input name="email" type="email" required>
  <textarea name="message" required></textarea>
  <button type="submit">Send</button>
</form>

<script>
document.getElementById('contactForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target));

  try {
    const response = await fetch('/backend/api/contact.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const result = await response.json();
    if (result.success) {
      alert('Message sent successfully!');
    } else {
      alert('Error: ' + result.message);
    }
  } catch (error) {
    alert('Failed to send message');
  }
});
</script>
```

### Fetch News Articles

```javascript
fetch('/backend/api/crud.php?table=news&status=published')
  .then(r => r.json())
  .then(data => {
    data.data.items.forEach(article => {
      console.log(article.title, article.published_at);
    });
  });
```

## 🚀 Production Deployment

### Checklist:

- [ ] Change default admin password
- [ ] Disable error display (`display_errors = 0`)
- [ ] Set up HTTPS/SSL
- [ ] Configure firewall rules
- [ ] Set up automated backups
- [ ] Enable PHP OPcache
- [ ] Configure rate limiting
- [ ] Set up monitoring/logging
- [ ] Secure file permissions
- [ ] Review `.env` settings
- [ ] Test all API endpoints
- [ ] Enable CORS properly if needed

### Backup Script

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p core_official > backup_$DATE.sql
tar -czf uploads_$DATE.tar.gz uploads/
```

## 📞 Support

For issues or questions:
1. Check error logs: `tail -f /var/log/apache2/error.log`
2. Enable debug mode temporarily
3. Review activity logs in database
4. Check file permissions

## 📄 License

Proprietary - Core Official © 2025
