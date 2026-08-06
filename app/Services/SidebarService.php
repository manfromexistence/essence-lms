<?php

namespace App\Services;

use App\Models\User;

class SidebarService
{
    /**
     * Role constants for menu access control.
     */
    public const ROLE_SUPER_ADMIN = 'super-admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_STUDENT = 'student';
    public const ROLE_PARENT = 'parent';

    /**
     * Get all available roles.
     *
     * @return array<string>
     */
    public static function getAllRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_TEACHER,
            self::ROLE_STUDENT,
            self::ROLE_PARENT,
        ];
    }

    /**
     * Get menu items filtered by user's role.
     *
     * @param User $user
     * @return array<int, array<string, mixed>>
     */
    public function getMenuItems(User $user): array
    {
        $menuItems = $this->getFullMenuStructure();
        
        // Super Admin sees all menu items
        if ($user->isSuperAdmin()) {
            return $menuItems;
        }

        // Get user's role slugs
        $userRoles = $user->roles->pluck('slug')->toArray();

        return $this->filterByRole($menuItems, $userRoles);
    }

    /**
     * Filter menu items based on user roles.
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<string> $userRoles
     * @return array<int, array<string, mixed>>
     */
    public function filterByRole(array $items, array $userRoles): array
    {
        $filteredItems = [];

        foreach ($items as $item) {
            // Check if user has any of the required roles for this item
            if (!$this->userCanAccessItem($item, $userRoles)) {
                continue;
            }

            // If item has children, filter them recursively
            if (isset($item['children']) && is_array($item['children'])) {
                $filteredChildren = $this->filterByRole($item['children'], $userRoles);
                
                // Only include parent if it has accessible children
                if (!empty($filteredChildren)) {
                    $item['children'] = $filteredChildren;
                    $filteredItems[] = $item;
                }
            } else {
                $filteredItems[] = $item;
            }
        }

        return $filteredItems;
    }

    /**
     * Check if user can access a menu item based on their roles.
     *
     * @param array<string, mixed> $item
     * @param array<string> $userRoles
     * @return bool
     */
    protected function userCanAccessItem(array $item, array $userRoles): bool
    {
        // If no roles specified, item is accessible to all
        if (!isset($item['roles']) || empty($item['roles'])) {
            return true;
        }

        // Check if user has any of the required roles
        return !empty(array_intersect($item['roles'], $userRoles));
    }

    /**
     * Get the full menu structure with role requirements.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getFullMenuStructure(): array
    {
        // The legacy school/academic menu remains below for reference, but this
        // installation intentionally exposes only course-business workflows.
        return $this->getCourseSellingMenuStructure();

        /** @noinspection PhpUnreachableStatementInspection */
        return [
            // Dashboard - accessible to all authenticated users
            [
                'title' => 'Dashboard',
                'icon' => 'dashboard',
                'route' => 'dashboard',
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT, self::ROLE_PARENT],
            ],

            // Students Management
            [
                'title' => 'Students',
                'icon' => 'students',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
                'children' => [
                    [
                        'title' => 'All Students',
                        'icon' => 'user',
                        'route' => 'dashboard.students.index',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
                    ],
                    [
                        'title' => 'Add New Student',
                        'icon' => 'user',
                        'route' => 'dashboard.students.create',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
                    ],
                    [
                        'title' => 'Digital Admission Form',
                        'icon' => 'document',
                        'route' => 'dashboard.students.admission-form',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
                    ],
                    [
                        'title' => 'Batch & Class Assignment',
                        'icon' => 'users',
                        'route' => 'dashboard.students.batch-assignment',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
                    ],
                    [
                        'title' => 'Attendance Tracking',
                        'icon' => 'clipboard-check',
                        'route' => 'dashboard.students.attendance',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
                    ],
                    [
                        'title' => 'SMS Notification',
                        'icon' => 'chat',
                        'route' => 'dashboard.students.sms',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN],
                    ],
                    [
                        'title' => 'Exam Routine',
                        'icon' => 'calendar',
                        'route' => 'dashboard.students.routine',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT, self::ROLE_PARENT],
                    ],
                    [
                        'title' => 'Result & Mark Sheets',
                        'icon' => 'document-text',
                        'route' => 'dashboard.students.results',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT, self::ROLE_PARENT],
                    ],
                ],
            ],

            // Teachers Management
            [
                'title' => 'Teachers',
                'icon' => 'teachers',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN],
                'children' => [
                    [
                        'title' => 'Add/Edit Teacher Info',
                        'icon' => 'user',
                        'route' => 'dashboard.teachers.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Assign to Subject/Class',
                        'icon' => 'book-open',
                        'route' => 'dashboard.teachers.assignments',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Department Categorization',
                        'icon' => 'office-building',
                        'route' => 'dashboard.teachers.categories',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Salary/Payment Records',
                        'icon' => 'currency',
                        'route' => 'dashboard.salaries.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                ],
            ],

            // Course & Class Management
            [
                'title' => 'Courses',
                'icon' => 'courses',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                'children' => [
                    [
                        'title' => 'Course & Module Creation',
                        'icon' => 'plus',
                        'route' => 'dashboard.courses.index',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class & Exam Routine',
                        'icon' => 'calendar',
                        'route' => 'dashboard.courses.routine',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Material Upload (PDF/Video)',
                        'icon' => 'document',
                        'route' => 'dashboard.courses.materials',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Attendance Per Class',
                        'icon' => 'clipboard-check',
                        'route' => 'dashboard.courses.attendance',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Telegram/FB Group Links',
                        'icon' => 'link',
                        'route' => 'dashboard.courses.groups',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class Schedule',
                        'icon' => 'calendar',
                        'route' => 'dashboard.schedules.index',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                ],
            ],

            // Batches Management
            [
                'title' => 'Batches',
                'icon' => 'batches',
                'route' => 'dashboard.batches.index',
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
            ],

            // Classes Management
            [
                'title' => 'Classes',
                'icon' => 'academic-cap',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                'children' => [
                    [
                        'title' => 'All Classes',
                        'icon' => 'collection',
                        'route' => 'dashboard.classes.index',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 1',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 1],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 2',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 2],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 3',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 3],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 4',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 4],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 5',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 5],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 6',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 6],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 7',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 7],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 8',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 8],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 9',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 9],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 10',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 10],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 11',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 11],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Class 12',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.classes.show',
                        'route_params' => ['class' => 12],
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                ],
            ],

            // Online Exam System
            [
                'title' => 'Exams',
                'icon' => 'exams',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT],
                'children' => [
                    [
                        'title' => 'MCQ Exam',
                        'icon' => 'clipboard',
                        'route' => 'dashboard.exams.mcq',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT],
                    ],
                    [
                        'title' => 'CQ Exam',
                        'icon' => 'pencil',
                        'route' => 'dashboard.exams.cq',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT],
                    ],
                    [
                        'title' => 'Live Exam',
                        'icon' => 'clock',
                        'route' => 'dashboard.exams.live',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT],
                    ],
                    [
                        'title' => 'Result & Mark Upload',
                        'icon' => 'document-text',
                        'route' => 'dashboard.exams.results',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Exam Leaderboard',
                        'icon' => 'chart-bar',
                        'route' => 'dashboard.exams.leaderboard',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT],
                    ],
                ],
            ],

            // Payment & Receipt
            [
                'title' => 'Payments',
                'icon' => 'payments',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_STUDENT, self::ROLE_PARENT],
                'children' => [
                    [
                        'title' => 'Dashboard',
                        'icon' => 'chart-bar',
                        'route' => 'dashboard.payments.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Record Payment',
                        'icon' => 'cash',
                        'route' => 'dashboard.payments.create',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Payment History',
                        'icon' => 'clock',
                        'route' => 'dashboard.payments.receipts',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Auto Invoice',
                        'icon' => 'clipboard',
                        'route' => 'dashboard.payments.invoices',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'SMS/Email Confirmation',
                        'icon' => 'mail',
                        'route' => 'dashboard.payments.notifications',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Due/Advance Tracking',
                        'icon' => 'exclamation-circle',
                        'route' => 'dashboard.payments.tracking',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_STUDENT, self::ROLE_PARENT],
                    ],
                ],
            ],

            // Accounts Management
            [
                'title' => 'Accounts',
                'icon' => 'currency',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN],
                'children' => [
                    [
                        'title' => 'Overview',
                        'icon' => 'chart-pie',
                        'route' => 'dashboard.accounts.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Income Management',
                        'icon' => 'trending-up',
                        'route' => 'dashboard.accounts.income',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Expense Tracking',
                        'icon' => 'trending-down',
                        'route' => 'dashboard.accounts.expenses',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Financial Reports',
                        'icon' => 'document-text',
                        'route' => 'dashboard.accounts.reports',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                ],
            ],

            // Inventory Management
            [
                'title' => 'Inventory',
                'icon' => 'archive',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN],
                'children' => [
                    [
                        'title' => 'Items List',
                        'icon' => 'list',
                        'route' => 'dashboard.inventory.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Inventory Report',
                        'icon' => 'chart-bar',
                        'route' => 'dashboard.inventory.report',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                ],
            ],


            // Reports
            [
                'title' => 'Reports',
                'icon' => 'reports',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                'children' => [
                    [
                        'title' => 'Attendance Report',
                        'icon' => 'clipboard-check',
                        'route' => 'dashboard.reports.attendance',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Payment Summary',
                        'icon' => 'currency',
                        'route' => 'dashboard.reports.payment-summary',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Performance Charts',
                        'icon' => 'chart-pie',
                        'route' => 'dashboard.reports.performance',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'Student Report',
                        'icon' => 'users',
                        'route' => 'dashboard.reports.student',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                ],
            ],

            // Communication
            [
                'title' => 'Communication',
                'icon' => 'communication',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                'children' => [
                    [
                        'title' => 'Send SMS',
                        'icon' => 'chat',
                        'route' => 'dashboard.communication.index',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'SMS Templates',
                        'icon' => 'document',
                        'route' => 'dashboard.communication.templates',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                    [
                        'title' => 'SMS Logs',
                        'icon' => 'clipboard-list',
                        'route' => 'dashboard.communication.logs',
                        'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
                    ],
                ],
            ],

            // Announcements
            [
                'title' => 'Announcements',
                'icon' => 'bell',
                'route' => 'dashboard.announcements.index',
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_TEACHER],
            ],

            // System Administration
            [
                'title' => 'System Admin',
                'icon' => 'chip',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN],
                'children' => [
                     [
                        'title' => 'Database Backup',
                        'icon' => 'database',
                        'route' => 'dashboard.backups.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Activity Logs',
                        'icon' => 'clipboard-list',
                        'route' => 'dashboard.activity-logs.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                     [
                        'title' => 'Bulk Import',
                        'icon' => 'upload',
                        'route' => 'dashboard.import.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                ],
            ],

            // CMS Pages - Super Admin only
            [
                'title' => 'CMS Pages',
                'icon' => 'document-text',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN],
                'children' => [
                    [
                        'title' => 'All Pages',
                        'icon' => 'collection',
                        'route' => 'dashboard.cms.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Home Page',
                        'icon' => 'home',
                        'route' => 'dashboard.cms.home',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'About Page',
                        'icon' => 'information-circle',
                        'route' => 'dashboard.cms.about',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Contact Page',
                        'icon' => 'phone',
                        'route' => 'dashboard.cms.contact',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Courses Page',
                        'icon' => 'book-open',
                        'route' => 'dashboard.cms.courses',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Teachers Page',
                        'icon' => 'users',
                        'route' => 'dashboard.cms.teachers',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Students Page',
                        'icon' => 'academic-cap',
                        'route' => 'dashboard.cms.students',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Results Page',
                        'icon' => 'clipboard-check',
                        'route' => 'dashboard.cms.results',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                ],
            ],

            // Settings - Super Admin only
            [
                'title' => 'Settings',
                'icon' => 'settings',
                'route' => 'dashboard.settings.index',
                'roles' => [self::ROLE_SUPER_ADMIN],
            ],

            // Users & Roles - Super Admin only
            [
                'title' => 'Users & Roles',
                'icon' => 'users-roles',
                'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN],
                'children' => [
                    [
                        'title' => 'User Management',
                        'icon' => 'users',
                        'route' => 'dashboard.users.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                    [
                        'title' => 'Role Management',
                        'icon' => 'shield-check',
                        'route' => 'dashboard.roles.index',
                        'roles' => [self::ROLE_SUPER_ADMIN],
                    ],
                ],
            ],

            // Student Dashboard Items (for students viewing their own data)
            [
                'title' => 'My Courses',
                'icon' => 'book-open',
                'route' => 'student.materials',
                'roles' => [self::ROLE_STUDENT],
            ],
            [
                'title' => 'My Results',
                'icon' => 'academic-cap',
                'route' => 'student.results',
                'roles' => [self::ROLE_STUDENT],
            ],
            [
                'title' => 'Class Schedule',
                'icon' => 'calendar',
                'route' => 'student.schedule',
                'roles' => [self::ROLE_STUDENT],
            ],
            [
                'title' => 'My Fees',
                'icon' => 'currency',
                'route' => 'student.payments',
                'roles' => [self::ROLE_STUDENT],
            ],

            // Parent Dashboard Items (read-only access to children's data)
            // Note: Children Overview is the parent dashboard, so it's accessed via the main Dashboard menu item
            [
                'title' => 'Academic Progress',
                'icon' => 'trending-up',
                'route' => 'dashboard.children.progress',
                'roles' => [self::ROLE_PARENT],
            ],
            [
                'title' => 'Attendance Records',
                'icon' => 'clipboard-check',
                'route' => 'dashboard.children.attendance',
                'roles' => [self::ROLE_PARENT],
            ],
            [
                'title' => 'Fee Status',
                'icon' => 'currency',
                'route' => 'dashboard.children.fees',
                'roles' => [self::ROLE_PARENT],
            ],
        ];
    }

    private function getCourseSellingMenuStructure(): array
    {
        $admins = [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN];

        return [
            ['title' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'dashboard', 'roles' => self::getAllRoles()],
            [
                'title' => 'Students', 'icon' => 'students', 'route' => null, 'roles' => $admins,
                'children' => [
                    ['title' => 'All Students', 'icon' => 'user', 'route' => 'dashboard.students.index', 'roles' => $admins],
                    ['title' => 'Add New Student', 'icon' => 'plus', 'route' => 'dashboard.students.create', 'roles' => $admins],
                    ['title' => 'Admission Applications', 'icon' => 'document', 'route' => 'dashboard.students.admission-form', 'roles' => $admins],
                ],
            ],
            [
                'title' => 'Courses', 'icon' => 'courses', 'route' => null,
                'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_TEACHER],
                'children' => [
                    ['title' => 'Courses & Modules', 'icon' => 'collection', 'route' => 'dashboard.courses.index', 'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_TEACHER]],
                    ['title' => 'Videos & Demo Classes', 'icon' => 'video', 'route' => 'dashboard.courses.index', 'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_TEACHER]],
                    ['title' => 'Learning Materials', 'icon' => 'document', 'route' => 'dashboard.courses.materials', 'roles' => [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_TEACHER]],
                ],
            ],
            [
                'title' => 'Payments', 'icon' => 'payments', 'route' => null, 'roles' => $admins,
                'children' => [
                    ['title' => 'Payment Dashboard', 'icon' => 'chart-bar', 'route' => 'dashboard.payments.index', 'roles' => $admins],
                    ['title' => 'bKash Reviews', 'icon' => 'clipboard-check', 'route' => 'payment.review.list', 'roles' => $admins],
                    ['title' => 'Invoices & Receipts', 'icon' => 'document-text', 'route' => 'dashboard.payments.invoices', 'roles' => $admins],
                ],
            ],
            ['title' => 'Certificates', 'icon' => 'academic-cap', 'route' => 'dashboard.certificates.index', 'roles' => $admins],
            ['title' => 'Announcements', 'icon' => 'announcement', 'route' => 'dashboard.announcements.index', 'roles' => $admins],
            [
                'title' => 'Services & Store', 'icon' => 'briefcase', 'route' => 'dashboard.services.index', 'roles' => $admins,
            ],
            ['title' => 'Team Members', 'icon' => 'users', 'route' => 'dashboard.teachers.index', 'roles' => $admins],
            [
                'title' => 'Website Content', 'icon' => 'globe', 'route' => null, 'roles' => [self::ROLE_SUPER_ADMIN],
                'children' => [
                    ['title' => 'All Pages', 'icon' => 'document', 'route' => 'dashboard.cms.index', 'roles' => [self::ROLE_SUPER_ADMIN]],
                    ['title' => 'Services Page', 'icon' => 'briefcase', 'route' => 'dashboard.cms.index', 'roles' => [self::ROLE_SUPER_ADMIN]],
                    ['title' => 'Team Page', 'icon' => 'users', 'route' => 'dashboard.cms.index', 'roles' => [self::ROLE_SUPER_ADMIN]],
                ],
            ],
            ['title' => 'Users & Roles', 'icon' => 'users', 'route' => 'dashboard.users.index', 'roles' => [self::ROLE_SUPER_ADMIN]],
            ['title' => 'Settings', 'icon' => 'settings', 'route' => 'dashboard.settings.index', 'roles' => [self::ROLE_SUPER_ADMIN]],
            ['title' => 'My Courses', 'icon' => 'courses', 'route' => 'student.courses', 'roles' => [self::ROLE_STUDENT]],
            ['title' => 'My Certificates', 'icon' => 'academic-cap', 'route' => 'student.certificates.index', 'roles' => [self::ROLE_STUDENT]],
        ];
    }

    /**
     * Get menu items for a specific role (without user context).
     * Useful for testing and documentation.
     *
     * @param string $role
     * @return array<int, array<string, mixed>>
     */
    public function getMenuItemsForRole(string $role): array
    {
        $menuItems = $this->getFullMenuStructure();

        // Super Admin sees all menu items
        if ($role === self::ROLE_SUPER_ADMIN) {
            return $menuItems;
        }

        return $this->filterByRole($menuItems, [$role]);
    }

    /**
     * Get all routes that a user can access based on their role.
     *
     * @param User $user
     * @return array<string>
     */
    public function getAccessibleRoutes(User $user): array
    {
        $menuItems = $this->getMenuItems($user);
        return $this->extractRoutes($menuItems);
    }

    /**
     * Get all routes accessible for a specific role.
     *
     * @param string $role
     * @return array<string>
     */
    public function getAccessibleRoutesForRole(string $role): array
    {
        $menuItems = $this->getMenuItemsForRole($role);
        return $this->extractRoutes($menuItems);
    }

    /**
     * Extract all routes from menu items recursively.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<string>
     */
    protected function extractRoutes(array $items): array
    {
        $routes = [];

        foreach ($items as $item) {
            if (isset($item['route']) && $item['route'] !== null) {
                $routes[] = $item['route'];
            }

            if (isset($item['children']) && is_array($item['children'])) {
                $routes = array_merge($routes, $this->extractRoutes($item['children']));
            }
        }

        return array_unique($routes);
    }

    /**
     * Check if a user can access a specific route.
     *
     * @param User $user
     * @param string $route
     * @return bool
     */
    public function canAccessRoute(User $user, string $route): bool
    {
        $accessibleRoutes = $this->getAccessibleRoutes($user);
        return in_array($route, $accessibleRoutes);
    }

    /**
     * Get the count of menu items for a user.
     *
     * @param User $user
     * @return int
     */
    public function getMenuItemCount(User $user): int
    {
        $menuItems = $this->getMenuItems($user);
        return $this->countItems($menuItems);
    }

    /**
     * Count all menu items including children.
     *
     * @param array<int, array<string, mixed>> $items
     * @return int
     */
    protected function countItems(array $items): int
    {
        $count = 0;

        foreach ($items as $item) {
            $count++;
            if (isset($item['children']) && is_array($item['children'])) {
                $count += $this->countItems($item['children']);
            }
        }

        return $count;
    }
}
