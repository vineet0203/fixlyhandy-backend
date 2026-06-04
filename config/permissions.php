<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PERMISSION MATRIX - Multi-Vendor Service Management Platform
    |--------------------------------------------------------------------------
    |
    | Roles:
    | 1. platform_admin - Full platform access
    | 2. vendor_owner - Service business owner
    | 3. employee - Vendor staff (technicians, dispatchers, admins)
    | 4. client - Service customers
    |
    */

    // ========== BASIC PERMISSIONS (ALL ROLES) ==========
    'basic_permissions' => [
        'update_own_password' => [
            'platform_admin',
            'vendor_owner', 
            'employee',
            'client'
        ],
        'update_own_profile' => [
            'platform_admin',
            'vendor_owner',
            'employee',
            'client'
        ],
        'view_own_profile' => [
            'platform_admin',
            'vendor_owner',
            'employee',
            'client'
        ],
    ],

    // ========== PLATFORM ADMIN PERMISSIONS ==========
    'platform_admin_permissions' => [
        // Vendor Management
        'view_all_vendors' => ['platform_admin'],
        'create_vendor' => ['platform_admin'],
        'edit_vendor' => ['platform_admin'],
        'suspend_vendor' => ['platform_admin'],
        'activate_vendor' => ['platform_admin'],
        'delete_vendor' => ['platform_admin'],
        
        // Client Management
        'view_all_clients' => ['platform_admin'],
        'create_client' => ['platform_admin'],
        'edit_client' => ['platform_admin'],
        'deactivate_client' => ['platform_admin'],
        
        // Platform Management
        'manage_platform_settings' => ['platform_admin'],
        'view_platform_reports' => ['platform_admin'],
        'manage_system_config' => ['platform_admin'],
        'manage_platform_users' => ['platform_admin'],
        
        // Financial Overview
        'view_platform_financials' => ['platform_admin'],
        'view_all_invoices' => ['platform_admin'],
        
        // Access Control
        'assign_roles' => ['platform_admin'],
        'manage_permissions' => ['platform_admin'],
    ],

    // ========== VENDOR OWNER PERMISSIONS ==========
    'vendor_owner_permissions' => [
        // Vendor Account Management
        'manage_vendor_profile' => ['vendor_owner'],
        'update_vendor_settings' => ['vendor_owner'],
        'delete_vendor_account' => ['vendor_owner'],
        
        // Employee Management
        'add_employees' => ['vendor_owner'],
        'view_employees' => ['vendor_owner'],
        'edit_employees' => ['vendor_owner'],
        'deactivate_employees' => ['vendor_owner'],
        'assign_employee_roles' => ['vendor_owner'],
        'set_employee_hourly_rates' => ['vendor_owner'],
        
        // Client Management
        'add_clients' => ['vendor_owner'],
        'view_clients' => ['vendor_owner'],
        'edit_clients' => ['vendor_owner'],
        'deactivate_clients' => ['vendor_owner'],
        'link_employees_to_clients' => ['vendor_owner'],
        
        // Job/Task Management
        'create_jobs' => ['vendor_owner'],
        'view_all_jobs' => ['vendor_owner'],
        'edit_jobs' => ['vendor_owner'],
        'assign_jobs' => ['vendor_owner'],
        'update_job_status' => ['vendor_owner'],
        'delete_jobs' => ['vendor_owner'],
        
        // Scheduling
        'create_schedules' => ['vendor_owner'],
        'view_schedules' => ['vendor_owner'],
        'edit_schedules' => ['vendor_owner'],
        'assign_schedules' => ['vendor_owner'],
        
        // Time Tracking
        'view_time_logs' => ['vendor_owner'],
        'approve_time_sheets' => ['vendor_owner'],
        'edit_time_entries' => ['vendor_owner'],
        
        // Billing & Invoicing
        'create_invoices' => ['vendor_owner'],
        'view_invoices' => ['vendor_owner'],
        'edit_invoices' => ['vendor_owner'],
        'send_invoices' => ['vendor_owner'],
        'record_payments' => ['vendor_owner'],
        'view_financial_reports' => ['vendor_owner'],
        
        // Reports
        'view_employee_reports' => ['vendor_owner'],
        'view_client_reports' => ['vendor_owner'],
        'view_job_reports' => ['vendor_owner'],
        'view_revenue_reports' => ['vendor_owner'],
    ],

    // ========== EMPLOYEE PERMISSIONS ==========
    'employee_permissions' => [
        // Job/Task Management
        'view_assigned_jobs' => ['employee'],
        'update_job_status' => ['employee'],
        'add_job_notes' => ['employee'],
        'upload_job_documents' => ['employee'],
        
        // Time Tracking
        'clock_in_out' => ['employee'],
        'view_own_time_logs' => ['employee'],
        'submit_timesheet' => ['employee'],
        'edit_own_time_entries' => ['employee'],
        
        // Schedule
        'view_own_schedule' => ['employee'],
        'request_schedule_change' => ['employee'],
        
        // Client Interaction
        'view_assigned_clients' => ['employee'],
        'communicate_with_clients' => ['employee'],
        
        // Profile & Settings
        'view_employee_directory' => ['employee'],
        'update_own_availability' => ['employee'],
        
        // Mobile App Features
        'use_mobile_app' => ['employee'],
        'update_location' => ['employee'],
        'receive_notifications' => ['employee'],
    ],

    // ========== CLIENT PERMISSIONS ==========
    'client_permissions' => [
        // Service Requests
        'request_service' => ['client'],
        'view_service_requests' => ['client'],
        'cancel_service_request' => ['client'],
        
        // Job Tracking
        'view_assigned_jobs' => ['client'],
        'track_job_progress' => ['client'],
        'approve_job_completion' => ['client'],
        'rate_service' => ['client'],
        
        // Communication
        'communicate_with_vendor' => ['client'],
        'upload_documents' => ['client'],
        
        // Billing
        'view_invoices' => ['client'],
        'pay_invoices' => ['client'],
        'view_payment_history' => ['client'],
        'download_receipts' => ['client'],
        
        // Profile Management
        'manage_client_profile' => ['client'],
        'update_service_preferences' => ['client'],
        'view_service_history' => ['client'],
        
        // Notifications
        'receive_service_updates' => ['client'],
        'receive_billing_notifications' => ['client'],
    ],

    // ========== SPECIAL RULES ==========
    'special_rules' => [
        // Vendor Owner automatically has all Employee permissions
        // Platform Admin can view but not perform vendor operations
        // Employees have role-based permissions (technician, dispatcher, admin subsets)
        // Clients can only interact with vendors they have relationships with
    ],

    // ========== PERMISSION SUBSETS FOR EMPLOYEE ROLES ==========
    'employee_role_subsets' => [
        'technician' => [
            'view_assigned_jobs',
            'update_job_status',
            'clock_in_out',
            'view_own_time_logs',
            'view_own_schedule',
            'use_mobile_app',
            'update_location',
            'upload_job_documents',
            'add_job_notes',
        ],
        
        'dispatcher' => [
            'view_all_jobs',
            'assign_jobs',
            'create_schedules',
            'view_schedules',
            'view_employees',
            'view_clients',
            'communicate_with_clients',
            'receive_notifications',
        ],
        
        'vendor_admin' => [
            'view_employees',
            'edit_employees',
            'view_clients',
            'edit_clients',
            'view_all_jobs',
            'edit_jobs',
            'view_time_logs',
            'view_invoices',
            'create_invoices',
        ],
    ],
];