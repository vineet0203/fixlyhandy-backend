# When employee sends status = inactive then check also check user status and mark inactve and vice versa

#Priority
# Check notification type emailqueue getting failed when registered a company- fix that
#Needs Fix - Password case checking in RegisterRequest.php

#change name of JobPost Service to Job
#Should we add profile pic option in user's table

# For Logging we have 
app/
├── Services/
│   └── Logging/
│       └── **SystemLoggerService.php**      ← Main logging service
├── Models/
│   └── **SystemErrorLog.php**               ← Database model for logs
├── Facades/
│   └── **SystemLogger.php**                 ← Facade for easy access
├── Traits/
│   └── **Loggable.php**                     ← Trait for controllers/services
└── Jobs/
    └── **SendSystemAlertEmail.php**         ← Queue job for email alerts

database/migrations/
└── **2024_xx_xx_create_system_error_logs_table.php** ← Migration file

🎯 WHY EACH COMPONENT IS NEEDED
Component	Why Needed	Without It
SystemLoggerService	Centralized logic - avoid duplicate code	Every service would implement its own logging
Database Model	Persistent storage - track/resolve issues	Logs lost on server restart
Facade	Easy access - consistent API	Need to inject service everywhere
Trait	Reusability - reduce boilerplate	Repeat same logging code in each class
Queue Job	Performance - non-blocking emails	User waits for email to send
Migration	Data persistence - historical analysis	No way to query/search past issues

#Audit log Added with auto run dail - Console\Kernal
Uses Variable From .env