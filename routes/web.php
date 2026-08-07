<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdmissionController;

Route::get('/', [HomeController::class, 'index']);

Route::get('/courses', [HomeController::class, 'courses'])->name('courses');

Route::get('/about', [HomeController::class, 'about'])->name('about');

Route::get('/teachers', [HomeController::class, 'teachers'])->name('teachers');

Route::get('/students', [HomeController::class, 'students'])->name('students');

Route::get('/results', [HomeController::class, 'results'])->name('results');

Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'submitContact'])->name('contact.submit')->middleware('throttle:5,1');
Route::get('/services', [HomeController::class, 'services'])->name('services');
Route::get('/team', [HomeController::class, 'team'])->name('team');
Route::get('/courses/{course}/demo', [HomeController::class, 'courseDemo'])->name('courses.demo');
Route::get('/courses/{course}/demo/{video}/stream', [HomeController::class, 'streamCourseDemo'])->name('courses.demo.stream');
Route::get('/certificates/verify/{code?}', [\App\Http\Controllers\CertificateController::class, 'verify'])->name('certificates.verify');
Route::get('/admission/offline', [AdmissionController::class, 'createOffline'])->name('admission.offline');
Route::get('/admission', [AdmissionController::class, 'create'])->name('admission.create');
Route::post('/admission', [AdmissionController::class, 'store'])->middleware('throttle:10,1')->name('admission.store');

Route::get('/announcements/{announcement}', [HomeController::class, 'showAnnouncement'])->name('announcement.show');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::put('/change-password', [AuthController::class, 'changePassword'])->middleware('throttle:5,1')->name('password.change.update');
});

// Unauthorized Access Route
Route::get('/unauthorized', function () {
    return view('errors.unauthorized');
})->name('unauthorized');

// Dashboard Routes (Protected)
Route::middleware('auth')->group(function () {
    Route::post('/dashboard/course-mode', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate(['mode' => 'required|in:online,offline']);
        $request->session()->put('course_mode', $validated['mode']);
        return back();
    })->name('dashboard.course-mode');
    // Main Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::middleware('role:super-admin')->group(function () {
        Route::match(['get', 'post'], '/dashboard/config', [DashboardController::class, 'config'])->name('dashboard.config');
        Route::get('/dashboard/chart-data/{type}', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');
    });

    // Admin Panel Routes
    Route::middleware('role:admin,super-admin')->prefix('dashboard')->name('dashboard.')->group(function () {
        // User Management
        Route::resource('users', UserController::class)->middleware('role:super-admin');

        // Ajax Routes for Student-Batch Dependency
        Route::get('students/get-batches/{courseId}', [AdminStudentController::class, 'getBatches'])->name('students.get-batches');
        Route::get('students/get-courses/{class}', [AdminStudentController::class, 'getCourses'])->name('students.get-courses');

        // Student Management - Specific routes MUST come before resource routes
        Route::get('students/admission-form', [AdminStudentController::class, 'admissionForm'])->name('students.admission-form');
        Route::get('students/batch-assignment', [AdminStudentController::class, 'batchAssignment'])->name('students.batch-assignment');
        Route::post('students/batch-assignment/update', [AdminStudentController::class, 'updateBatchAssignment'])->name('students.batch-assignment.update');
        Route::post('students/batch-assignment/bulk', [AdminStudentController::class, 'bulkBatchAssignment'])->name('students.batch-assignment.bulk');
        Route::get('students/attendance', [AdminStudentController::class, 'attendance'])->name('students.attendance');
        Route::get('students/sms', [AdminStudentController::class, 'sms'])->name('students.sms');
        Route::get('students/routine', [AdminStudentController::class, 'routine'])->name('students.routine');
        Route::get('students/results', [AdminStudentController::class, 'results'])->name('students.results');
        Route::resource('students', AdminStudentController::class);

        // Teacher Management - Specific routes MUST come before resource routes
        Route::get('teachers/assignments', [AdminTeacherController::class, 'assignments'])->name('teachers.assignments');
        Route::get('teachers/{teacher}/assignment/edit', [AdminTeacherController::class, 'assignmentEdit'])->name('teachers.assignment.edit');
        Route::put('teachers/{teacher}/assignment', [AdminTeacherController::class, 'assignmentUpdate'])->name('teachers.assignment.update');
        Route::delete('teachers/{teacher}/assignment', [AdminTeacherController::class, 'assignmentRemove'])->name('teachers.assignment.remove');
        Route::get('teachers/categories', [AdminTeacherController::class, 'categories'])->name('teachers.categories');
        Route::post('teachers/categories', [AdminTeacherController::class, 'categoryStore'])->name('teachers.categories.store');
        Route::put('teachers/categories/{category}', [AdminTeacherController::class, 'categoryUpdate'])->name('teachers.categories.update');
        Route::delete('teachers/categories/{category}', [AdminTeacherController::class, 'categoryDestroy'])->name('teachers.categories.destroy');
        Route::get('teachers/salary', [AdminTeacherController::class, 'salary'])->name('teachers.salary');
        Route::resource('teachers', AdminTeacherController::class);

        // Role Management
        Route::resource('roles', RoleController::class)->only(['index', 'show', 'edit', 'update'])->middleware('role:super-admin');

        // Course Management - Specific routes MUST come before resource routes
        Route::get('courses/routine', [CourseController::class, 'routine'])->name('courses.routine');
        Route::get('courses/materials', [CourseController::class, 'materials'])->name('courses.materials');
        Route::get('courses/attendance', [CourseController::class, 'attendance'])->name('courses.attendance');
        Route::get('courses/groups', [CourseController::class, 'groups'])->name('courses.groups');
        
        // Course Videos
        Route::prefix('courses/{course}/videos')->name('courses.videos.')->group(function() {
            Route::get('/', [\App\Http\Controllers\Admin\CourseVideoController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\CourseVideoController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\CourseVideoController::class, 'store'])->name('store');
            Route::get('/{video}/edit', [\App\Http\Controllers\Admin\CourseVideoController::class, 'edit'])->name('edit');
            Route::put('/{video}', [\App\Http\Controllers\Admin\CourseVideoController::class, 'update'])->name('update');
            Route::delete('/{video}', [\App\Http\Controllers\Admin\CourseVideoController::class, 'destroy'])->name('destroy');
            Route::get('/{video}/stream', [\App\Http\Controllers\Admin\CourseVideoController::class, 'stream'])->name('stream');
            Route::post('/reorder', [\App\Http\Controllers\Admin\CourseVideoController::class, 'reorder'])->name('reorder');
        });

        Route::resource('courses', CourseController::class);

        // Batch Management
        Route::resource('batches', BatchController::class);

        // Class Management
        Route::get('classes', [\App\Http\Controllers\Admin\ClassController::class, 'index'])->name('classes.index');
        Route::get('classes/{class}', [\App\Http\Controllers\Admin\ClassController::class, 'show'])->name('classes.show');

        // Online Exam Management
        Route::middleware('student.exam.access')->group(function() {
            Route::resource('exams', \App\Http\Controllers\Admin\OnlineExamController::class);
            Route::get('mcq-exams', [\App\Http\Controllers\Admin\OnlineExamController::class, 'mcq'])->name('exams.mcq');
            Route::get('cq-exams', [\App\Http\Controllers\Admin\OnlineExamController::class, 'cq'])->name('exams.cq');
            Route::get('live-exams', [\App\Http\Controllers\Admin\OnlineExamController::class, 'live'])->name('exams.live');
            Route::get('exam-results', [\App\Http\Controllers\Admin\OnlineExamController::class, 'results'])->name('exams.results');
            Route::get('exam-leaderboard', [\App\Http\Controllers\Admin\OnlineExamController::class, 'leaderboard'])->name('exams.leaderboard');
            
            // Question Management for Exams
            Route::prefix('exams/{exam}/questions')->name('exams.questions.')->group(function() {
                Route::post('/', [\App\Http\Controllers\Admin\OnlineExamController::class, 'storeQuestion'])->name('store');
                Route::get('/{question}', [\App\Http\Controllers\Admin\OnlineExamController::class, 'getQuestion'])->name('show');
                Route::put('/{question}', [\App\Http\Controllers\Admin\OnlineExamController::class, 'updateQuestion'])->name('update');
                Route::delete('/{question}', [\App\Http\Controllers\Admin\OnlineExamController::class, 'destroyQuestion'])->name('destroy');
            });

            // Question Import/Export
            Route::get('exams/{exam}/import-questions', [\App\Http\Controllers\Admin\OnlineExamController::class, 'importQuestions'])->name('exams.import-questions');
            Route::post('exams/{exam}/import-questions', [\App\Http\Controllers\Admin\OnlineExamController::class, 'processImport'])->name('exams.process-import');
            Route::get('exams/{exam}/export-questions', [\App\Http\Controllers\Admin\OnlineExamController::class, 'exportQuestions'])->name('exams.export-questions');
            Route::get('exams/download-template', [\App\Http\Controllers\Admin\OnlineExamController::class, 'downloadTemplate'])->name('exams.download-template');

            // Results Management
            Route::get('exams/{exam}/results/{result}', [\App\Http\Controllers\Admin\OnlineExamController::class, 'viewResult'])->name('exams.view-result');
            Route::get('exams/{exam}/results/{result}/edit', [\App\Http\Controllers\Admin\OnlineExamController::class, 'editResult'])->name('exams.edit-result');
            Route::put('exams/{exam}/results/{result}', [\App\Http\Controllers\Admin\OnlineExamController::class, 'updateResult'])->name('exams.update-result');
            Route::delete('exams/{exam}/results/{result}', [\App\Http\Controllers\Admin\OnlineExamController::class, 'deleteResult'])->name('exams.delete-result');
            Route::get('exams/{exam}/export-results', [\App\Http\Controllers\Admin\OnlineExamController::class, 'exportResults'])->name('exams.export-results');

            // Offline Exam Review
            Route::get('exams/{exam}/review', [\App\Http\Controllers\Admin\OnlineExamController::class, 'reviewSubmissions'])->name('exams.review-submissions');
            Route::get('exams/{exam}/review/{submission}', [\App\Http\Controllers\Admin\OnlineExamController::class, 'reviewSubmission'])->name('exams.review-submission');
            Route::post('exams/{exam}/review/{submission}', [\App\Http\Controllers\Admin\OnlineExamController::class, 'saveReview'])->name('exams.save-review');
        });

        // Accounts Management
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AccountController::class, 'index'])->name('index');
            Route::get('income', [\App\Http\Controllers\Admin\AccountController::class, 'income'])->name('income');
            Route::post('income', [\App\Http\Controllers\Admin\AccountController::class, 'storeIncome'])->name('income.store');
            Route::delete('income/{income}', [\App\Http\Controllers\Admin\AccountController::class, 'destroyIncome'])->name('income.destroy');
            Route::get('expenses', [\App\Http\Controllers\Admin\AccountController::class, 'expenses'])->name('expenses');
            Route::post('expenses', [\App\Http\Controllers\Admin\AccountController::class, 'storeExpense'])->name('expenses.store');
            Route::put('expenses/{expense}', [\App\Http\Controllers\Admin\AccountController::class, 'updateExpense'])->name('expenses.update');
            Route::delete('expenses/{expense}', [\App\Http\Controllers\Admin\AccountController::class, 'destroyExpense'])->name('expenses.destroy');
            Route::get('reports', [\App\Http\Controllers\Admin\AccountController::class, 'reports'])->name('reports');
            Route::get('export', [\App\Http\Controllers\Admin\AccountController::class, 'export'])->name('export');
            Route::post('export-pdf', [\App\Http\Controllers\Admin\AccountController::class, 'exportPdf'])->name('export-pdf');
        });

        // Inventory Management
        Route::resource('inventory', \App\Http\Controllers\Admin\InventoryController::class);
        Route::post('inventory/{inventory}/purchase', [\App\Http\Controllers\Admin\InventoryController::class, 'purchase'])->name('inventory.purchase');
        Route::post('inventory/{inventory}/usage', [\App\Http\Controllers\Admin\InventoryController::class, 'usage'])->name('inventory.usage');
        Route::get('inventory-report', [\App\Http\Controllers\Admin\InventoryController::class, 'report'])->name('inventory.report');

        // Announcements
        Route::resource('announcements', \App\Http\Controllers\Admin\AnnouncementController::class);

        // Course Materials (nested under courses)
        Route::prefix('courses/{course}')->name('courses.')->group(function () {
            Route::resource('materials', \App\Http\Controllers\Admin\MaterialController::class);
            Route::post('materials/reorder', [\App\Http\Controllers\Admin\MaterialController::class, 'reorder'])->name('materials.reorder');
        });

        // Class Schedules
        Route::resource('schedules', \App\Http\Controllers\Admin\ScheduleController::class);
        Route::post('schedules/check-conflict', [\App\Http\Controllers\Admin\ScheduleController::class, 'checkConflict'])->name('schedules.check-conflict');
        Route::get('exams/routine', [\App\Http\Controllers\Admin\OnlineExamController::class, 'routine'])->name('exams.routine');

        // Teacher Salaries
        Route::resource('salaries', \App\Http\Controllers\Admin\SalaryController::class);
        Route::get('salaries/teacher/{teacher}/history', [\App\Http\Controllers\Admin\SalaryController::class, 'history'])->name('salaries.history');
        Route::get('salaries-report', [\App\Http\Controllers\Admin\SalaryController::class, 'report'])->name('salaries.report');

        // Backups (Super Admin Only)
        Route::middleware('role:super-admin')->prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\BackupController::class, 'create'])->name('create');
            Route::post('/{filename}/restore', [\App\Http\Controllers\Admin\BackupController::class, 'restore'])->name('restore');
            Route::get('/{filename}/download', [\App\Http\Controllers\Admin\BackupController::class, 'download'])->name('download');
            Route::delete('/{filename}', [\App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('destroy');
        });

        // Activity Logs (Super Admin Only)
        Route::middleware('role:super-admin')->group(function () {
            Route::get('activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-logs.index');
        });

        // Data Import
        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ImportController::class, 'index'])->name('index');
            Route::post('/upload', [\App\Http\Controllers\Admin\ImportController::class, 'upload'])->name('upload');
            Route::get('/preview', [\App\Http\Controllers\Admin\ImportController::class, 'preview'])->name('preview');
            Route::post('/execute', [\App\Http\Controllers\Admin\ImportController::class, 'execute'])->name('execute');
        });

        // Communication
        Route::get('communication', [\App\Http\Controllers\Admin\CommunicationController::class, 'index'])->name('communication.index');
        Route::post('communication/send', [\App\Http\Controllers\Admin\CommunicationController::class, 'send'])->name('communication.send');
        Route::post('communication/send-bulk', [\App\Http\Controllers\Admin\CommunicationController::class, 'sendBulk'])->name('communication.send-bulk');
        Route::match(['get', 'post'], 'communication/send-result', [\App\Http\Controllers\Admin\CommunicationController::class, 'sendResult'])->name('communication.send-result');
        Route::get('communication/logs', [\App\Http\Controllers\Admin\CommunicationController::class, 'logs'])->name('communication.logs');
        Route::post('communication/retry/{smsLog}', [\App\Http\Controllers\Admin\CommunicationController::class, 'retry'])->name('communication.retry');
        Route::post('communication/retry-bulk', [\App\Http\Controllers\Admin\CommunicationController::class, 'retryBulk'])->name('communication.retry-bulk');
        Route::post('communication/retry-failed', [\App\Http\Controllers\Admin\CommunicationController::class, 'retryFailed'])->name('communication.retry-failed');
        Route::post('communication/payment-notification/{payment}', [\App\Http\Controllers\Admin\CommunicationController::class, 'sendPaymentNotification'])->name('communication.payment-notification');
        Route::post('communication/payment-reminders', [\App\Http\Controllers\Admin\CommunicationController::class, 'sendPaymentReminders'])->name('communication.payment-reminders');
        Route::post('communication/result-notification', [\App\Http\Controllers\Admin\CommunicationController::class, 'sendResultNotification'])->name('communication.result-notification');
        Route::get('communication/templates', [\App\Http\Controllers\Admin\CommunicationController::class, 'templates'])->name('communication.templates');
        Route::post('communication/templates', [\App\Http\Controllers\Admin\CommunicationController::class, 'storeTemplate'])->name('communication.templates.store');
        Route::put('communication/templates/{template}', [\App\Http\Controllers\Admin\CommunicationController::class, 'updateTemplate'])->name('communication.templates.update');
        Route::delete('communication/templates/{template}', [\App\Http\Controllers\Admin\CommunicationController::class, 'deleteTemplate'])->name('communication.templates.delete');

        // Email (Brevo) Communication
        Route::get('email', [\App\Http\Controllers\Admin\EmailController::class, 'index'])->name('email.index');
        Route::post('email/send', [\App\Http\Controllers\Admin\EmailController::class, 'send'])->name('email.send');
        Route::post('email/send-bulk', [\App\Http\Controllers\Admin\EmailController::class, 'sendBulk'])->name('email.send-bulk');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('attendance', [\App\Http\Controllers\Admin\ReportController::class, 'attendance'])->name('attendance');
            Route::get('performance', [\App\Http\Controllers\Admin\ReportController::class, 'performance'])->name('performance');
            Route::get('payment-summary', [\App\Http\Controllers\Admin\ReportController::class, 'paymentSummary'])->name('payment-summary');
            Route::get('student', [\App\Http\Controllers\Admin\ReportController::class, 'student'])->name('student');
            Route::get('charts', [\App\Http\Controllers\Admin\ReportController::class, 'charts'])->name('charts');
            Route::get('export', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('export');
            Route::post('export-pdf', [\App\Http\Controllers\Admin\ReportController::class, 'exportPdf'])->name('export-pdf');
            Route::post('export-excel', [\App\Http\Controllers\Admin\ReportController::class, 'exportExcel'])->name('export-excel');
            Route::get('dashboard-data', [\App\Http\Controllers\Admin\ReportController::class, 'dashboardData'])->name('dashboard-data');
        });

        // Payment Management
        Route::get('payments/receipts', [AdminPaymentController::class, 'receipts'])->name('payments.receipts');
        Route::get('payments/invoices', [AdminPaymentController::class, 'invoices'])->name('payments.invoices');
        Route::post('payments/invoices', [AdminPaymentController::class, 'createInvoice'])->name('payments.invoices.store');
        Route::get('payments/invoices/{invoice}', [AdminPaymentController::class, 'showInvoice'])->name('payments.invoice.show');
        Route::get('payments/notifications', [AdminPaymentController::class, 'notifications'])->name('payments.notifications');
        Route::post('payments/notifications', [AdminPaymentController::class, 'sendNotification'])->name('payments.notifications.send');
        Route::post('payments/send-notification', [AdminPaymentController::class, 'sendNotification'])->name('payments.send-notification');
        Route::get('payments/tracking', [AdminPaymentController::class, 'tracking'])->name('payments.tracking');
        Route::get('payments/{payment}/receipt', [AdminPaymentController::class, 'receipt'])->name('payments.receipt');
        Route::get('payments/student/{student}/history', [AdminPaymentController::class, 'history'])->name('payments.history');
        Route::resource('payments', AdminPaymentController::class);

        Route::resource('services', \App\Http\Controllers\Admin\ServiceController::class)->except(['show']);

        Route::get('certificates', [\App\Http\Controllers\Admin\CertificateController::class, 'index'])->name('certificates.index');
        Route::post('certificates', [\App\Http\Controllers\Admin\CertificateController::class, 'store'])->name('certificates.store');
        Route::put('certificates/{certificate}', [\App\Http\Controllers\Admin\CertificateController::class, 'update'])->name('certificates.update');
        Route::post('certificates/{certificate}/email', [\App\Http\Controllers\Admin\CertificateController::class, 'email'])->name('certificates.email');
        Route::post('certificates/{certificate}/revoke', [\App\Http\Controllers\Admin\CertificateController::class, 'revoke'])->name('certificates.revoke');

        // Settings (Super Admin Only)
        Route::middleware('role:super-admin')->group(function () {
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::put('settings/form-fields', [SettingsController::class, 'updateFormFields'])->name('settings.update-form-fields');
            Route::get('settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
        });

        // CMS Pages (Super Admin Only)
        Route::prefix('cms')->name('cms.')->middleware('role:super-admin')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PageController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\PageController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\PageController::class, 'store'])->name('store');
            Route::get('/home', [\App\Http\Controllers\Admin\PageController::class, 'editHome'])->name('home');
            Route::get('/about', [\App\Http\Controllers\Admin\PageController::class, 'editAbout'])->name('about');
            Route::get('/contact', [\App\Http\Controllers\Admin\PageController::class, 'editContact'])->name('contact');
            Route::get('/courses', [\App\Http\Controllers\Admin\PageController::class, 'editCourses'])->name('courses');
            Route::get('/teachers', [\App\Http\Controllers\Admin\PageController::class, 'editTeachers'])->name('teachers');
            Route::get('/students', [\App\Http\Controllers\Admin\PageController::class, 'editStudents'])->name('students');
            Route::get('/results', [\App\Http\Controllers\Admin\PageController::class, 'editResults'])->name('results');
            Route::get('/{page}/edit', [\App\Http\Controllers\Admin\PageController::class, 'edit'])->name('edit');
            Route::put('/{page}', [\App\Http\Controllers\Admin\PageController::class, 'update'])->name('update');
            Route::delete('/{page}', [\App\Http\Controllers\Admin\PageController::class, 'destroy'])->name('destroy');
        });
        
    });

    Route::middleware('role:parent')->prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('children', [\App\Http\Controllers\ParentPortalController::class, 'index'])->name('children');
        Route::get('children/progress', [\App\Http\Controllers\ParentPortalController::class, 'progress'])->name('children.progress');
        Route::get('children/attendance', [\App\Http\Controllers\ParentPortalController::class, 'attendance'])->name('children.attendance');
        Route::get('children/fees', [\App\Http\Controllers\ParentPortalController::class, 'fees'])->name('children.fees');
    });

    // Teacher Portal Routes
    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\TeacherController::class, 'dashboard'])->name('dashboard');
        Route::get('/batches', [\App\Http\Controllers\TeacherController::class, 'batches'])->name('batches');
        Route::get('/batches/{batch}/students', [\App\Http\Controllers\TeacherController::class, 'students'])->name('batches.students');
        Route::get('/attendance', [\App\Http\Controllers\TeacherController::class, 'attendance'])->name('attendance');
        Route::post('/attendance', [\App\Http\Controllers\TeacherController::class, 'saveAttendance'])->name('attendance.save');
        Route::get('/exams', [\App\Http\Controllers\TeacherController::class, 'exams'])->name('exams');
        Route::get('/schedule', [\App\Http\Controllers\TeacherController::class, 'schedule'])->name('schedule');
    });

    // Student Portal Routes
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\StudentPortalController::class, 'dashboard'])->name('dashboard');
        
        // Materials
        Route::get('/materials', [\App\Http\Controllers\StudentPortalController::class, 'materials'])->name('materials');
        Route::get('/materials/{material}/download', [\App\Http\Controllers\StudentPortalController::class, 'downloadMaterial'])->name('materials.download');
        
        // Schedule
        Route::get('/schedule', [\App\Http\Controllers\StudentPortalController::class, 'schedule'])->name('schedule');
        
        // Exams
        Route::get('/exams', [\App\Http\Controllers\StudentPortalController::class, 'exams'])->name('exams');
        Route::get('/exams/{exam}/start', [\App\Http\Controllers\StudentPortalController::class, 'startExam'])->name('exams.start');
        Route::post('/exams/{exam}/submit', [\App\Http\Controllers\StudentPortalController::class, 'submitExam'])->name('exams.submit');
        Route::post('/exams/attempt/{attempt}/save-answer', [\App\Http\Controllers\StudentPortalController::class, 'saveAnswer'])->name('exams.save-answer');
        Route::post('/exams/attempt/{attempt}/tab-switch', [\App\Http\Controllers\StudentPortalController::class, 'recordTabSwitch'])->name('exams.record-tab-switch');
        Route::get('/exams/result/{result}', [\App\Http\Controllers\StudentPortalController::class, 'examResult'])->name('exam-result');
        
        // CQ Exams
        Route::get('/exams/{exam}/cq', [\App\Http\Controllers\StudentPortalController::class, 'showCqExam'])->name('exams.cq');
        Route::post('/exams/{exam}/cq/upload', [\App\Http\Controllers\StudentPortalController::class, 'uploadCqAnswer'])->name('exams.cq.upload');
        Route::post('/exams/screenshot/upload', [\App\Http\Controllers\StudentPortalController::class, 'uploadScreenshot'])->name('exams.screenshot.upload');
        Route::get('/cq-submission/{submission}', [\App\Http\Controllers\StudentPortalController::class, 'viewCqSubmission'])->name('cq-submission');
        
        // Results
        Route::get('/results', [\App\Http\Controllers\StudentPortalController::class, 'results'])->name('results');
        Route::get('/results/{result}/mark-sheet', [\App\Http\Controllers\StudentPortalController::class, 'downloadMarkSheet'])->name('results.mark-sheet');
        Route::get('/performance-trends', [\App\Http\Controllers\StudentPortalController::class, 'performanceTrends'])->name('performance-trends');
        
        // Payments
        Route::get('/payments', [\App\Http\Controllers\StudentPortalController::class, 'payments'])->name('payments');
        Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\StudentPortalController::class, 'downloadReceipt'])->name('payments.receipt');
        
        // Course Enrollment Payment Routes (Task 15)
        Route::get('/courses', [\App\Http\Controllers\StudentPortalController::class, 'courses'])->name('courses');
        Route::get('/courses/{course}/enroll', [\App\Http\Controllers\StudentPortalController::class, 'enroll'])->name('course.enroll');
        Route::get('/courses/{course}/watch', [\App\Http\Controllers\StudentPortalController::class, 'watchCourse'])->name('course.watch');
        Route::get('/courses/{course}/watch/{video}', [\App\Http\Controllers\StudentPortalController::class, 'watchCourse'])->name('course.video');
        Route::get('/courses/{course}/watch/{video}/stream', [\App\Http\Controllers\StudentPortalController::class, 'streamVideo'])->name('course.video.stream');
        Route::post('/courses/{course}/watch/{video}/complete', [\App\Http\Controllers\StudentPortalController::class, 'completeVideo'])->name('course.video.complete');
        Route::get('/certificates', [\App\Http\Controllers\CertificateController::class, 'index'])->name('certificates.index');
        Route::get('/certificates/{certificate}', [\App\Http\Controllers\CertificateController::class, 'show'])->name('certificates.show');
        Route::get('/payment/form/{course}', [\App\Http\Controllers\PaymentController::class, 'showForm'])->name('payment.form');
        Route::post('/payment/submit', [\App\Http\Controllers\PaymentController::class, 'submit'])->name('payment.submit');
        Route::get('/payment/dashboard', [\App\Http\Controllers\PaymentController::class, 'dashboard'])->name('payment.dashboard');
        Route::get('/payment/{payment}/proof', [\App\Http\Controllers\PaymentController::class, 'proof'])->name('payment.proof');
    });
});

// Admin Payment Review Routes (Task 15)
Route::middleware(['auth', 'role:admin,super-admin'])->prefix('admin')->name('payment.')->group(function () {
    Route::get('/payment-review', [\App\Http\Controllers\PaymentController::class, 'reviewList'])->name('review.list');
    Route::get('/payment-review/{payment}', [\App\Http\Controllers\PaymentController::class, 'reviewDetail'])->name('review.detail');
    Route::get('/payment-review/{payment}/proof', [\App\Http\Controllers\PaymentController::class, 'proof'])->name('proof');
    Route::post('/payment-review/{payment}/approve', [\App\Http\Controllers\PaymentController::class, 'approve'])->name('approve');
    Route::post('/payment-review/{payment}/reject', [\App\Http\Controllers\PaymentController::class, 'reject'])->name('reject');
});
