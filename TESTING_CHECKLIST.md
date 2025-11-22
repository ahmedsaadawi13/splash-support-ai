# SplashSupportAI - Testing Checklist

## 🧪 Complete Testing Checklist

Use this checklist to verify all functionality before deployment.

---

## 1. Authentication & User Management

### Registration
- [ ] New tenant registration works
- [ ] Company name validation
- [ ] Email format validation
- [ ] Password strength validation (min 8 chars, letter + number)
- [ ] Duplicate email rejected
- [ ] User auto-login after registration
- [ ] Default Starter plan assigned
- [ ] Tenant code generated correctly

### Login
- [ ] Valid credentials allow login
- [ ] Invalid credentials rejected
- [ ] Session created on successful login
- [ ] Failed login attempts tracked
- [ ] Account locked after 5 failed attempts (15 min lockout)
- [ ] Locked account shows appropriate message
- [ ] Session persists across page refreshes
- [ ] Last login timestamp updated

### Logout
- [ ] Logout destroys session
- [ ] Redirects to login page
- [ ] Cannot access protected pages after logout
- [ ] Activity logged

### Password Security
- [ ] Passwords hashed with bcrypt
- [ ] Plain passwords never stored
- [ ] Password hash verification works

---

## 2. Role-Based Access Control

### Platform Admin
- [ ] Can view all tenants
- [ ] Can switch between tenants
- [ ] Can access system-wide reports
- [ ] Cannot be created via registration (DB only)

### Tenant Admin
- [ ] Can manage users in their tenant
- [ ] Can view subscription & usage
- [ ] Can access settings
- [ ] Can create/edit channels
- [ ] Can manage SLA policies
- [ ] Cannot see other tenants' data

### Support Manager
- [ ] Can view all tickets in tenant
- [ ] Can create routing rules
- [ ] Can access reports
- [ ] Can manage teams
- [ ] Cannot manage billing/subscription

### Support Agent
- [ ] Can view assigned tickets
- [ ] Can reply to tickets
- [ ] Can update ticket status
- [ ] Cannot access admin settings
- [ ] Cannot view reports

### Read-Only
- [ ] Can view tickets
- [ ] Can view reports
- [ ] Cannot create/edit anything
- [ ] All write operations rejected

---

## 3. Tenant Isolation

### Data Separation
- [ ] Tenant A cannot see Tenant B's tickets
- [ ] Tenant A cannot see Tenant B's users
- [ ] Tenant A cannot see Tenant B's KB articles
- [ ] API keys scoped to tenant
- [ ] Direct URL access blocked (e.g., /tickets/view/TCK-XXXX from different tenant)

### Database Queries
- [ ] All ticket queries filter by `tenant_id`
- [ ] All user queries filter by `tenant_id`
- [ ] All KB queries filter by `tenant_id`
- [ ] No cross-tenant data leakage

---

## 4. Ticketing System

### Ticket Creation
- [ ] Create ticket via web UI
- [ ] Create ticket via API
- [ ] Customer auto-created if not exists
- [ ] Public ID generated (TCK-XXXXX format)
- [ ] Status defaults to 'new'
- [ ] Routing rules applied automatically
- [ ] SLA status created if policy exists
- [ ] Usage counter incremented
- [ ] Activity logged

### Ticket Fields
- [ ] Subject required (max 500 chars)
- [ ] Customer email required & validated
- [ ] Priority: low, normal, high, urgent
- [ ] Type: question, incident, problem, task
- [ ] Status: new, open, pending, on_hold, resolved, closed
- [ ] Channel association
- [ ] Assignment to user/team

### Ticket Viewing
- [ ] Ticket detail page loads
- [ ] All messages displayed
- [ ] Messages ordered by created_at ASC
- [ ] Customer messages shown
- [ ] Agent messages shown
- [ ] AI messages indicated
- [ ] Internal notes hidden from customer
- [ ] SLA status displayed

### Ticket Replies
- [ ] Agent can reply
- [ ] Reply saved to database
- [ ] Sender type = 'agent'
- [ ] Sender user ID captured
- [ ] First response timestamp updated
- [ ] Last agent reply timestamp updated
- [ ] Ticket status auto-changed from 'new' to 'open'
- [ ] Internal notes work (is_internal flag)

### Ticket Status Updates
- [ ] Status can be changed
- [ ] Resolved status sets `resolved_at`
- [ ] Closed status sets `resolved_at`
- [ ] Status change logged in activity

### Ticket Assignment
- [ ] Assign to specific user
- [ ] Assign to team
- [ ] Unassigned tickets visible
- [ ] Assignment logged

### Ticket Filters
- [ ] Filter by status
- [ ] Filter by priority
- [ ] Filter by channel
- [ ] Filter "assigned to me"
- [ ] Multiple filters combined

### Ticket Pagination
- [ ] 25 tickets per page (default)
- [ ] Page navigation works
- [ ] Page parameter in URL

---

## 5. Multi-Channel Support

### Email Channel
- [ ] Email channel created
- [ ] Tickets can be created for email channel
- [ ] Email logging to /storage/logs/email_log.txt (simulated)

### Web Chat Channel
- [ ] Chat channel created
- [ ] Chat widget loads
- [ ] Chat session initiated
- [ ] Messages sent via chat
- [ ] Chat creates ticket
- [ ] Multiple chat sessions work

### WhatsApp Channel (Simulated)
- [ ] WhatsApp channel type supported
- [ ] Tickets can be created for WhatsApp channel

### API Channel
- [ ] API channel created
- [ ] API ticket creation works
- [ ] API replies work

---

## 6. SLA Management

### SLA Policies
- [ ] Create SLA policy
- [ ] Set first response time (minutes)
- [ ] Set resolution time (minutes)
- [ ] Enable/disable policies

### SLA Tracking
- [ ] SLA status created on ticket creation
- [ ] First response due time calculated
- [ ] Resolution due time calculated
- [ ] First response met when agent replies
- [ ] Resolution met when ticket resolved
- [ ] Breach flagged when overdue
- [ ] SLA status updates on ticket activity

### SLA Checks
- [ ] Manual SLA check works (test script)
- [ ] Breached tickets flagged
- [ ] SLA status visible on ticket detail

---

## 7. Routing Rules

### Rule Creation
- [ ] Create routing rule
- [ ] Set criteria (subject contains, channel equals, etc.)
- [ ] Set action (assign to user, assign to team)
- [ ] Priority order works
- [ ] Enable/disable rules

### Rule Execution
- [ ] Rules applied on ticket creation
- [ ] First matching rule wins
- [ ] Ticket assigned automatically
- [ ] Assignment logged

### Rule Criteria
- [ ] Subject contains "billing" → assign to team
- [ ] Channel = "email" → assign to user
- [ ] Priority = "urgent" → assign to manager

---

## 8. AI Features

### AI Quota Check
- [ ] Quota check returns allowed status
- [ ] Quota check respects plan limits
- [ ] Unlimited plans return 'unlimited'
- [ ] Exceeded plans return false

### AI Suggest Reply
- [ ] "AI Suggest" button works
- [ ] Suggestion fills textarea
- [ ] Multiple suggestions returned
- [ ] Confidence score provided
- [ ] KB articles included if relevant
- [ ] Token usage tracked

### AI Auto Reply
- [ ] Auto-reply triggers for simple tickets
- [ ] Reply saved as sender_type = 'ai'
- [ ] High confidence required (>0.85)
- [ ] Token usage tracked

### AI Chatbot
- [ ] Chatbot replies in chat widget
- [ ] Escalation to human works
- [ ] Token usage tracked

### AI Knowledge Base Search
- [ ] Search returns relevant articles
- [ ] Articles ranked by relevance (simulated)
- [ ] Empty query returns empty results

### AI Intent Classification
- [ ] Billing keywords → billing intent
- [ ] Login keywords → technical_support intent
- [ ] Generic → general_inquiry intent

### AI Token Tracking
- [ ] Tokens incremented in tenant_usage
- [ ] Monthly reset works
- [ ] Quota enforcement prevents usage when exceeded

---

## 9. Knowledge Base

### Categories
- [ ] Create category
- [ ] Nested categories (parent/child)
- [ ] Category listing

### Articles
- [ ] Create article
- [ ] Assign to category
- [ ] HTML content supported
- [ ] Text content stored for search
- [ ] Publish/unpublish
- [ ] Tags supported

### Public Help Center
- [ ] Access via /help/{tenant_code}
- [ ] Category listing displayed
- [ ] Recent articles shown
- [ ] Search works
- [ ] Article detail page
- [ ] View count incremented

### KB Search
- [ ] Full-text search works
- [ ] LIKE search fallback
- [ ] Results ordered by relevance
- [ ] Empty query handled

---

## 10. Chat Widget

### Widget Loading
- [ ] chat-widget.js loads
- [ ] Widget appears bottom-right
- [ ] Tenant code required
- [ ] Click to open/close

### Chat Session
- [ ] Session initiated via /chat/init
- [ ] Session token stored in localStorage
- [ ] Session persists on page reload

### Chat Messages
- [ ] Send message
- [ ] Message saved to ticket
- [ ] AI reply returned (if enabled)
- [ ] Message history displayed
- [ ] Scrolling works

### Chat to Ticket Conversion
- [ ] Chat creates ticket automatically
- [ ] Ticket ID stored in session
- [ ] Multiple messages added to same ticket

---

## 11. Subscriptions & Usage

### Subscription Plans
- [ ] Plans seeded in database
- [ ] Starter, Professional, Enterprise
- [ ] Plan features stored as JSON
- [ ] Active plans visible

### Tenant Subscriptions
- [ ] New tenant gets default plan (Starter)
- [ ] Subscription status: trialing, active, past_due, canceled
- [ ] Renewal date set
- [ ] Subscription visible in settings

### Usage Tracking
- [ ] Ticket creation increments ticket count
- [ ] AI usage increments token count
- [ ] API calls incremented
- [ ] Monthly reset works

### Quota Enforcement
- [ ] Ticket creation blocked when quota exceeded
- [ ] AI usage blocked when quota exceeded
- [ ] Appropriate error messages shown
- [ ] Unlimited plans never blocked

---

## 12. Public REST API

### Authentication
- [ ] X-API-KEY required
- [ ] Invalid key rejected (401)
- [ ] Valid key grants access
- [ ] Last used timestamp updated

### Create Ticket Endpoint
- [ ] POST /api/tickets works
- [ ] Subject required
- [ ] Message required
- [ ] Customer email required
- [ ] Priority defaults to 'normal'
- [ ] Ticket created successfully
- [ ] Public ID returned
- [ ] Quota enforced

### Add Reply Endpoint
- [ ] POST /api/tickets/{public_id}/reply works
- [ ] Message required
- [ ] Reply added to ticket
- [ ] Sender type captured

### Get Ticket Endpoint
- [ ] GET /api/tickets/{public_id} works
- [ ] Ticket details returned
- [ ] Messages included
- [ ] 404 if not found

### List Tickets Endpoint
- [ ] GET /api/tickets works
- [ ] Status filter works
- [ ] Pagination works (page, limit)
- [ ] Limit max 100 enforced

### API Error Handling
- [ ] 400 for bad request
- [ ] 401 for invalid API key
- [ ] 404 for not found
- [ ] 429 for quota exceeded
- [ ] 500 for server error
- [ ] JSON error responses

---

## 13. Reports & Dashboards

### Dashboard KPIs
- [ ] Total tickets displayed
- [ ] Open tickets count
- [ ] Resolved this month
- [ ] Urgent tickets count

### Recent Activity
- [ ] Recent tickets list
- [ ] Time ago format
- [ ] Link to ticket detail

### Subscription Info
- [ ] Plan name shown
- [ ] Ticket usage shown
- [ ] AI token usage shown
- [ ] Status shown

### Reports Page
- [ ] Date range filter
- [ ] Tickets by status chart
- [ ] Tickets by channel chart
- [ ] Tickets by agent chart
- [ ] SLA compliance stats
- [ ] CSV export works

---

## 14. Notifications

### Notification Creation
- [ ] Notifications created on ticket assignment
- [ ] Notifications created on SLA warning
- [ ] Notifications created on new message

### Notification Display
- [ ] Unread count shown
- [ ] Notification list displayed
- [ ] Click to mark as read
- [ ] Mark all as read

---

## 15. File Uploads

### Upload Validation
- [ ] File size limit enforced (10MB default)
- [ ] MIME type validated
- [ ] Allowed types: images, PDF, DOCX, XLSX, TXT
- [ ] Disallowed types rejected

### Upload Storage
- [ ] Files stored in /storage/uploads
- [ ] Unique filename generated (UUID)
- [ ] Original filename preserved in DB
- [ ] File path stored correctly

### Upload Security
- [ ] Directory traversal prevented
- [ ] Files not executable
- [ ] Downloads require authentication

---

## 16. Activity Logging

### Events Logged
- [ ] User login
- [ ] User logout
- [ ] Ticket created
- [ ] Ticket updated
- [ ] Ticket status changed
- [ ] Ticket assigned
- [ ] AI reply sent

### Log Data
- [ ] Tenant ID captured
- [ ] User ID captured
- [ ] Entity type captured
- [ ] Entity ID captured
- [ ] Action captured
- [ ] IP address captured
- [ ] User agent captured
- [ ] Timestamp captured

---

## 17. Settings & Configuration

### User Management
- [ ] Create new user
- [ ] Assign role
- [ ] List users
- [ ] Email uniqueness enforced

### Channel Management
- [ ] List channels
- [ ] Create channel
- [ ] Channel types: email, web_chat, whatsapp, api
- [ ] Enable/disable channel

### SLA Settings
- [ ] List SLA policies
- [ ] Create SLA policy
- [ ] Set response/resolution times

### Subscription Settings
- [ ] View current plan
- [ ] View usage
- [ ] View available plans
- [ ] (Upgrade not implemented - manual DB update)

---

## 18. Security Tests

### Input Validation
- [ ] SQL injection blocked (try: `' OR 1=1--`)
- [ ] XSS blocked (try: `<script>alert('XSS')</script>`)
- [ ] Path traversal blocked (try: `../../etc/passwd`)

### Authentication Bypass
- [ ] Cannot access /dashboard without login
- [ ] Cannot access /tickets without login
- [ ] Cannot access API without key

### CSRF Protection
- [ ] Form submission without token rejected
- [ ] Token mismatch rejected
- [ ] Valid token accepted

### Session Security
- [ ] Session regenerated on login
- [ ] Session destroyed on logout
- [ ] Session timeout works (if configured)

### Tenant Isolation
- [ ] Cannot access other tenant's data via URL manipulation
- [ ] Cannot access other tenant's data via API

---

## 19. Error Handling

### Application Errors
- [ ] 404 page for invalid routes
- [ ] 500 page for server errors
- [ ] Error messages user-friendly
- [ ] Stack traces hidden in production

### Database Errors
- [ ] Connection failure handled gracefully
- [ ] Query errors logged
- [ ] Transaction rollback on error

### API Errors
- [ ] Proper HTTP status codes
- [ ] JSON error responses
- [ ] Detailed error messages

---

## 20. Performance Tests

### Page Load Times
- [ ] Login page < 1s
- [ ] Dashboard < 2s
- [ ] Ticket list < 2s
- [ ] Ticket detail < 1.5s

### Database Queries
- [ ] No N+1 query problems
- [ ] Indexes used on queries
- [ ] Pagination reduces load

### Large Data
- [ ] 1,000 tickets loadable
- [ ] 10,000 tickets loadable (with pagination)
- [ ] Search works with large datasets

---

## 21. Browser Compatibility

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Chrome
- [ ] Mobile Safari

---

## 22. Deployment Tests

### Database Import
- [ ] database.sql imports without errors
- [ ] All tables created
- [ ] Seed data inserted
- [ ] Platform admin user created

### File Permissions
- [ ] /storage/uploads writable
- [ ] /storage/logs writable
- [ ] .env readable
- [ ] public directory accessible

### Web Server
- [ ] .htaccess working (Apache)
- [ ] URL rewriting works
- [ ] Static assets load
- [ ] Chat widget accessible

### CRON Jobs
- [ ] SLA check script executable
- [ ] Usage reset script executable

---

## 23. Integration Tests

### End-to-End Flows

**Customer Support Flow:**
1. [ ] Customer submits ticket via API
2. [ ] Routing rule assigns to agent
3. [ ] SLA status created
4. [ ] Agent receives notification
5. [ ] Agent views ticket
6. [ ] AI suggests reply
7. [ ] Agent sends reply
8. [ ] First response SLA met
9. [ ] Customer replies
10. [ ] Agent resolves ticket
11. [ ] Resolution SLA met
12. [ ] Activity logged

**Subscription Flow:**
1. [ ] New tenant registers
2. [ ] Default plan assigned
3. [ ] Usage initialized
4. [ ] Tenant creates tickets
5. [ ] Usage incremented
6. [ ] Quota reached
7. [ ] Ticket creation blocked

**Chat Widget Flow:**
1. [ ] Visitor opens chat
2. [ ] Session created
3. [ ] Visitor sends message
4. [ ] AI replies
5. [ ] Visitor requests human
6. [ ] Ticket created
7. [ ] Agent assigned
8. [ ] Agent replies

---

## ✅ Test Results Summary

**Total Tests:** ~250+
**Passing:** ___
**Failing:** ___
**Skipped:** ___

**Overall Status:** [ ] PASS / [ ] FAIL

---

## 📝 Notes

Record any issues, bugs, or observations here:

---

**Testing Completed By:** _______________
**Date:** _______________
**Version Tested:** 1.0
