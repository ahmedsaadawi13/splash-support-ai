# SplashSupportAI

**Multi-Tenant AI-Powered Customer Support & Ticketing SaaS Platform**

A complete, production-ready helpdesk and customer support platform built with PHP & MySQL, featuring integrated AI assistance, multi-channel support, SLA management, and comprehensive reporting.

---

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Architecture](#architecture)
- [API Documentation](#api-documentation)
- [AI Integration](#ai-integration)
- [Security](#security)
- [Testing](#testing)
- [Deployment](#deployment)
- [License](#license)

---

## ✨ Features

### Core Ticketing System
- **Multi-Channel Inbox**: Email, Web Chat, WhatsApp (simulated), and API
- **Smart Ticket Routing**: Rule-based automatic assignment to teams or agents
- **SLA Management**: Configurable policies with automatic tracking and breach detection
- **Priority & Status Management**: Customizable workflows
- **Internal Notes**: Agent-only communication on tickets
- **File Attachments**: Support for documents, images, and PDFs

### AI-Powered Features
- **AI Suggested Replies**: Get intelligent reply suggestions based on ticket context
- **AI Auto-Reply**: Automated responses for common questions
- **AI Knowledge Base Search**: Semantic search across help articles
- **AI Chatbot**: Conversational widget for website visitors
- **Intent Classification**: Automatic categorization of tickets

### Multi-Tenancy & Subscriptions
- **Isolated Tenants**: Complete data separation per company
- **Subscription Plans**: Starter, Professional, and Enterprise tiers
- **Usage Tracking**: Monitor tickets, AI tokens, API calls, and storage
- **Quota Enforcement**: Automatic limits based on subscription plan
- **Billing Management**: Invoicing and payment tracking

### Knowledge Base & Help Center
- **Public Help Center**: Self-service portal for customers
- **Categories & Articles**: Organized documentation
- **Full-Text Search**: Fast article lookup
- **View Tracking**: Monitor article popularity
- **Multilingual Support**: Ready for i18n

### Team Collaboration
- **Teams**: Organize agents into groups
- **Role-Based Access**: Platform Admin, Tenant Admin, Support Manager, Agent, Read-Only
- **Activity Logging**: Complete audit trail
- **Notifications**: Real-time alerts for ticket updates

### Reporting & Analytics
- **Dashboard KPIs**: Ticket volume, response times, SLA compliance
- **Custom Reports**: Filter by date, channel, agent, status, priority
- **CSV Export**: Download reports for external analysis
- **Agent Performance**: Track individual and team metrics

### Developer API
- **RESTful API**: Create tickets, add replies, retrieve ticket data
- **API Key Authentication**: Secure tenant-specific access
- **Rate Limiting**: Built-in usage tracking
- **Comprehensive Documentation**: Full endpoint reference

### Chat Widget
- **Embeddable JavaScript Widget**: Add to any website
- **AI-Powered Responses**: Automatic FAQ handling
- **Seamless Escalation**: Convert chats to tickets
- **Customizable Appearance**: Match your brand

---

## 🔧 Requirements

### Server Requirements
- **PHP**: 7.0+ (compatible with PHP 7.x and 8.x)
- **MySQL**: 5.7+ or MariaDB 10.2+
- **Web Server**: Apache 2.4+ or Nginx 1.18+

### PHP Extensions
- `pdo_mysql` - Database connectivity
- `mbstring` - Multibyte string support
- `openssl` - Encryption and security
- `json` - JSON handling
- `fileinfo` - File type detection
- `session` - Session management

### Optional
- `curl` - For AI API integrations
- `gd` or `imagick` - Image processing

---

## 📦 Installation

### Step 1: Clone or Download

```bash
git clone https://github.com/ahmedsaadawi13/SplashSupportAI.git
cd SplashSupportAI
```

### Step 2: Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
DB_HOST=localhost
DB_NAME=splash_support_ai
DB_USER=root
DB_PASS=your_password

APP_ENV=production
APP_DEBUG=false
BASE_URL=https://yourdomain.com
```

### Step 3: Import Database

```bash
mysql -u root -p < database.sql
```

This creates all tables, indexes, and seed data including:
- 3 subscription plans
- Platform admin user (admin@splashsupportai.com / password)
- 2 demo tenants with sample data

### Step 4: Set Permissions

```bash
chmod -R 755 storage/
chmod -R 755 public/
chown -R www-data:www-data storage/
```

### Step 5: Configure Web Server

#### Apache (.htaccess included)

Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

VirtualHost example:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/SplashSupportAI/public

    <Directory /path/to/SplashSupportAI/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/splash_error.log
    CustomLog ${APACHE_LOG_DIR}/splash_access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/SplashSupportAI/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?url=$uri&$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Step 6: Test Installation

```bash
# Test database connection
php tests/test_db_connection.php

# Test ticket creation
php tests/test_ticket_creation.php

# Test AI helper
php tests/test_ai_helper.php
```

---

## ⚙️ Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database host | localhost |
| `DB_NAME` | Database name | splash_support_ai |
| `DB_USER` | Database user | root |
| `DB_PASS` | Database password | |
| `APP_ENV` | Environment (development/production) | production |
| `APP_DEBUG` | Enable debug mode | false |
| `BASE_URL` | Application URL | http://localhost |
| `AI_PROVIDER` | AI provider (openai) | openai |
| `AI_PROVIDER_KEY` | AI API key | |
| `AI_MODEL` | AI model name | gpt-4 |
| `MAX_UPLOAD_SIZE` | Max file size in bytes | 10485760 |

### Database Configuration

All database settings are in `/config/config.php`. The system uses PDO with prepared statements for security.

### Subscription Plans

Edit plans in database or create via admin panel:

```sql
UPDATE plans SET price = 49.00 WHERE id = 1;
```

### CRON Jobs (Recommended)

Add to crontab for background tasks:

```cron
# Check SLA status every 5 minutes
*/5 * * * * php /path/to/SplashSupportAI/cron/check_sla.php

# Reset monthly usage on 1st of month
0 0 1 * * php /path/to/SplashSupportAI/cron/reset_usage.php
```

---

## 🚀 Usage

### Default Login Credentials

**Platform Admin:**
- Email: `admin@splashsupportai.com`
- Password: `password`

**Demo Tenant (Acme Corp):**
- Email: `jane@acme.com` (Tenant Admin)
- Password: `password`

### Creating Your First Tenant

1. Register at `/register`
2. Fill in company name, your name, email, password
3. System creates tenant and assigns Starter plan (30-day trial)
4. Auto-login to dashboard

### Creating Tickets

**Via Web UI:**
1. Navigate to `/tickets/create`
2. Fill in customer details and message
3. Select priority and channel
4. Click "Create Ticket"

**Via API:**

```bash
curl -X POST https://yourdomain.com/api/tickets \
  -H "X-API-KEY: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "Cannot access account",
    "message": "I forgot my password",
    "customer": {
      "name": "John Doe",
      "email": "john@example.com"
    },
    "priority": "normal"
  }'
```

### Embedding Chat Widget

Add to your website:

```html
<script
  src="https://yourdomain.com/chat-widget.js"
  data-tenant="your_tenant_code">
</script>
```

### Using AI Features

1. **AI Suggest Reply**: Click "AI Suggest" button on ticket detail page
2. **AI Auto-Reply**: Enabled automatically for simple tickets (configurable)
3. **AI Chatbot**: Activated in chat widget for instant responses

---

## 🏗️ Architecture

### MVC Structure

```
/app
  /core
    - Database.php      # PDO database wrapper
    - Router.php        # URL routing
    - Controller.php    # Base controller
    - Model.php         # Base model
  /controllers
    - AuthController.php
    - DashboardController.php
    - TicketsController.php
    - ApiController.php
    - ChatController.php
    - ReportsController.php
    - SettingsController.php
    - PublicHelpController.php
  /models
    - Tenant.php
    - User.php
    - Ticket.php
    - Customer.php
    - Channel.php
    - SLAPolicy.php
    - KBArticle.php
    - Notification.php
    - RoutingRule.php
  /views
    - layouts/
    - auth/
    - dashboard/
    - tickets/
    - public/
  /helpers
    - AIHelper.php          # AI integration abstraction
    - SecurityHelper.php    # Security utilities
    - ValidationHelper.php  # Input validation
    - FileHelper.php        # File handling
    - DateHelper.php        # Date/timezone utilities
```

### Database Schema

**Multi-Tenancy Tables:**
- `tenants` - Company accounts
- `users` - All users across tenants
- `plans` - Subscription plans
- `tenant_subscriptions` - Active subscriptions
- `tenant_usage` - Usage tracking

**Ticketing Tables:**
- `tickets` - Support tickets
- `ticket_messages` - Ticket conversations
- `ticket_attachments` - File uploads
- `ticket_sla_status` - SLA tracking

**Configuration Tables:**
- `channels` - Communication channels
- `sla_policies` - SLA rules
- `routing_rules` - Auto-assignment rules
- `teams` - Agent teams

**Knowledge Base:**
- `kb_categories` - Article categories
- `kb_articles` - Help articles

**Supporting Tables:**
- `customers` - End users
- `notifications` - User alerts
- `activity_logs` - Audit trail
- `tenant_api_keys` - API authentication

### Security Features

1. **Authentication**: Session-based with password hashing (bcrypt)
2. **Authorization**: Role-based access control (RBAC)
3. **CSRF Protection**: Token validation on all forms
4. **XSS Prevention**: Output escaping via SecurityHelper
5. **SQL Injection Prevention**: Prepared statements only
6. **Tenant Isolation**: Strict filtering on `tenant_id`
7. **Brute Force Protection**: Login attempt limiting
8. **API Security**: API key authentication with rate limiting

---

## 📡 API Documentation

### Authentication

All API requests require `X-API-KEY` header:

```bash
X-API-KEY: your_tenant_api_key_here
```

Get API key from Settings > API Keys in dashboard.

### Endpoints

#### Create Ticket

```
POST /api/tickets
```

**Request:**

```json
{
  "subject": "Need help with billing",
  "message": "I was charged twice",
  "customer": {
    "name": "Jane Smith",
    "email": "jane@example.com",
    "phone": "+1234567890"
  },
  "priority": "normal",
  "type": "question",
  "channel": "api"
}
```

**Response:**

```json
{
  "status": "success",
  "ticket_id": 123,
  "public_id": "TCK-10123",
  "message": "Ticket created successfully"
}
```

#### Add Reply to Ticket

```
POST /api/tickets/{public_id}/reply
```

**Request:**

```json
{
  "from": "customer",
  "message": "Thank you for your help!"
}
```

#### Get Ticket Details

```
GET /api/tickets/{public_id}
```

**Response:**

```json
{
  "status": "success",
  "ticket": {
    "public_id": "TCK-10123",
    "subject": "Need help with billing",
    "status": "open",
    "priority": "normal",
    "created_at": "2025-01-20 10:30:00"
  },
  "messages": [...]
}
```

#### List Tickets

```
GET /api/tickets?status=open&page=1&limit=25
```

**Query Parameters:**
- `status` - Filter by status
- `channel` - Filter by channel
- `from` - Start date (YYYY-MM-DD)
- `to` - End date (YYYY-MM-DD)
- `page` - Page number (default: 1)
- `limit` - Results per page (default: 25, max: 100)

### Error Responses

```json
{
  "error": "Error message here"
}
```

HTTP Status Codes:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `429` - Quota Exceeded
- `500` - Server Error

---

## 🤖 AI Integration

### Current Implementation

The AI system is currently **simulated** and returns placeholder responses. This allows the platform to work out-of-the-box without requiring API keys.

### Upgrading to Production AI

Replace methods in `/app/helpers/AIHelper.php` with real API calls:

#### Example: OpenAI Integration

```php
public static function suggestReply($tenantId, $ticket, $lastMessage, $kbArticles = array()) {
    $apiKey = AI_PROVIDER_KEY;

    $prompt = "Generate a professional customer support reply for this ticket:\n";
    $prompt .= "Subject: {$ticket['subject']}\n";
    $prompt .= "Last message: {$lastMessage['body_text']}\n";

    $response = file_get_contents('https://api.openai.com/v1/chat/completions', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                       "Authorization: Bearer $apiKey\r\n",
            'content' => json_encode([
                'model' => AI_MODEL,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful customer support agent.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => AI_MAX_TOKENS
            ])
        ]
    ]));

    $data = json_decode($response, true);
    $reply = $data['choices'][0]['message']['content'];
    $tokensUsed = $data['usage']['total_tokens'];

    self::trackUsage($tenantId, $tokensUsed);

    return [
        'suggestions' => [
            ['message' => $reply, 'confidence' => 0.9, 'source' => 'ai_generated']
        ],
        'tokens_used' => $tokensUsed
    ];
}
```

### Supported AI Providers

- **OpenAI** (GPT-3.5, GPT-4)
- **Anthropic** (Claude)
- **Google** (Gemini)
- **Azure OpenAI**
- **Local LLMs** (Ollama, LM Studio)

---

## 🔒 Security

### Best Practices Implemented

1. **Password Security**: bcrypt hashing with cost factor 10
2. **Session Security**: HTTP-only cookies, regeneration on login
3. **Input Validation**: Server-side validation on all inputs
4. **Output Encoding**: HTML escaping prevents XSS
5. **SQL Security**: PDO prepared statements prevent injection
6. **File Upload Security**: MIME type validation, size limits, unique filenames
7. **Directory Traversal Protection**: Path validation
8. **CSRF Protection**: Token validation on all state-changing requests
9. **Rate Limiting**: Login throttling, API usage limits
10. **Audit Logging**: Complete activity trail

### Security Checklist

- [ ] Change default admin password
- [ ] Set strong `APP_DEBUG=false` in production
- [ ] Use HTTPS (SSL/TLS certificate)
- [ ] Restrict database user permissions
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Keep PHP and MySQL updated
- [ ] Regular backups
- [ ] Monitor error logs
- [ ] Implement firewall rules
- [ ] Enable security headers (CSP, HSTS)

---

## 🧪 Testing

### Manual Testing

```bash
# Database connection
php tests/test_db_connection.php

# Ticket creation workflow
php tests/test_ticket_creation.php

# AI helper functions
php tests/test_ai_helper.php
```

### Testing Checklist

**Authentication & Authorization:**
- [ ] Login with valid credentials
- [ ] Login with invalid credentials (should fail)
- [ ] Password reset flow
- [ ] Session timeout
- [ ] Role-based access (try accessing admin pages as agent)

**Ticket Management:**
- [ ] Create ticket via web UI
- [ ] Create ticket via API
- [ ] Add reply to ticket
- [ ] Update ticket status
- [ ] Assign ticket to user/team
- [ ] Upload attachment
- [ ] AI suggest reply

**Multi-Tenancy:**
- [ ] Create new tenant via registration
- [ ] Verify data isolation (tenant A cannot see tenant B's tickets)
- [ ] Switch tenants (platform admin)

**Subscription & Limits:**
- [ ] Create tickets until quota reached
- [ ] Verify quota enforcement
- [ ] Use AI until token limit reached

**API:**
- [ ] Create ticket via API
- [ ] Add reply via API
- [ ] List tickets with filters
- [ ] Invalid API key (should return 401)

**Chat Widget:**
- [ ] Embed widget on test page
- [ ] Send message
- [ ] Receive AI response
- [ ] Escalate to human (creates ticket)

**Knowledge Base:**
- [ ] Access public help center
- [ ] Search articles
- [ ] View article (increments view count)

---

## 🚢 Deployment

### Production Deployment Steps

1. **Server Setup**
   - Ubuntu 20.04+ or CentOS 8+
   - Install LAMP/LEMP stack
   - Install SSL certificate (Let's Encrypt)

2. **Application Deployment**
   ```bash
   cd /var/www
   git clone <your-repo> SplashSupportAI
   cd SplashSupportAI
   cp .env.example .env
   # Edit .env with production settings
   ```

3. **Database Setup**
   ```bash
   mysql -u root -p
   CREATE DATABASE splash_support_ai;
   CREATE USER 'splash_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT ALL ON splash_support_ai.* TO 'splash_user'@'localhost';
   FLUSH PRIVILEGES;
   exit

   mysql -u splash_user -p splash_support_ai < database.sql
   ```

4. **Permissions**
   ```bash
   chown -R www-data:www-data storage/
   chmod -R 755 storage/
   ```

5. **Web Server**
   - Configure virtual host (see Installation section)
   - Enable SSL/TLS
   - Restart web server

6. **CRON Jobs**
   ```bash
   crontab -e
   ```
   Add:
   ```cron
   */5 * * * * php /var/www/SplashSupportAI/cron/check_sla.php >> /var/log/splash_cron.log 2>&1
   ```

7. **Monitoring**
   - Set up error log monitoring
   - Configure uptime monitoring
   - Database backup automation

### Environment-Specific Configuration

**Development:**
```env
APP_ENV=development
APP_DEBUG=true
```

**Staging:**
```env
APP_ENV=staging
APP_DEBUG=false
```

**Production:**
```env
APP_ENV=production
APP_DEBUG=false
```

---

## 📚 Additional Resources

### File Structure Overview

```
SplashSupportAI/
├── app/
│   ├── controllers/      # Request handlers
│   ├── models/          # Data layer
│   ├── views/           # Templates
│   ├── core/            # Framework core
│   └── helpers/         # Utility classes
├── config/              # Configuration
├── public/              # Web root
│   ├── assets/          # CSS, JS, images
│   ├── chat-widget.js   # Chat widget
│   └── index.php        # Entry point
├── storage/
│   ├── uploads/         # User uploads
│   └── logs/            # Application logs
├── tests/               # Test scripts
├── database.sql         # Database schema
├── .env.example         # Environment template
├── .htaccess           # Apache config
└── README.md           # This file
```

### Support

- **Documentation**: This README
- **Issues**: GitHub Issues
- **Email**: support@splashsupportai.com

### Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

### Roadmap

Future enhancements:
- [ ] Real-time WebSocket notifications
- [ ] Email template builder
- [ ] Custom field builder
- [ ] Workflow automation
- [ ] Mobile app (iOS/Android)
- [ ] WhatsApp Business API integration
- [ ] Slack integration
- [ ] Zendesk/Freshdesk migration tool

---

## 📄 License

Copyright © 2025 SplashSupportAI

This software is proprietary. Unauthorized copying, distribution, or modification is prohibited.

For licensing inquiries, contact: license@splashsupportai.com

---

## 🎉 Credits

Built with:
- PHP 7.0+
- MySQL 5.7+
- Vanilla JavaScript
- Custom MVC Framework
- PDO for database security
- bcrypt for password hashing

**Developed by:** SplashSupportAI Team

---

**Thank you for using SplashSupportAI!** 🚀

For questions or support, please open an issue or contact us.
