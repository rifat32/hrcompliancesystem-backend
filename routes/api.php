<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssetTypeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\EmailTemplateWrapperController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessEmailSettingController;
use App\Http\Controllers\BusinessInitializationController;
use App\Http\Controllers\BusinessTierController;
use App\Http\Controllers\BusinessTimesController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CandidateRecruitmentProcessController;
use App\Http\Controllers\ClientTicketingSystemController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CustomWebhookController;
use App\Http\Controllers\DashboardManagementController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DropdownOptionsController;
use App\Http\Controllers\EmployeeRotaController;
use App\Http\Controllers\EmploymentStatusController;
use App\Http\Controllers\ExportImportController;
use App\Http\Controllers\FileManagementController;
use App\Http\Controllers\HistoryDetailsController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HrmLocalizedFieldController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\JobPlatformController;
use App\Http\Controllers\JobTypeController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LetterTemplateController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NotificationController;

use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrunController;

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RecruitmentProcessController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\ServicePlanController;
use App\Http\Controllers\SettingAttendanceController;
use App\Http\Controllers\SettingLeaveController;
use App\Http\Controllers\SettingLeaveTypeController;
use App\Http\Controllers\SettingPaymentDateController;
use App\Http\Controllers\SettingPayrollController;
use App\Http\Controllers\SocialSiteController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TaskCategoryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TerminationProcessController;
use App\Http\Controllers\TerminationReasonController;
use App\Http\Controllers\TerminationTypeController;
use App\Http\Controllers\UserAddressHistoryController;
use App\Http\Controllers\UserAssetController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\UserEducationHistoryController;
use App\Http\Controllers\UserJobHistoryController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\UserNoteController;
use App\Http\Controllers\UserPassportHistoryController;
use App\Http\Controllers\UserPayslipController;
use App\Http\Controllers\UserPensionHistoryController;
use App\Http\Controllers\UserRecruitmentProcessController;
use App\Http\Controllers\UserRightToWorkHistoryController;
use App\Http\Controllers\UserSocialSiteController;
use App\Http\Controllers\UserSponsorshipHistoryController;
use App\Http\Controllers\UserVisaHistoryController;
use App\Http\Controllers\WorkLocationController;
use App\Http\Controllers\WorkShiftController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserLetterController;
use App\Http\Controllers\WorkShiftHistoryController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider jistoryin a group which x
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Define route for GET method
Route::get('/health', function () {
    return response()->json(['status' => 'Server is up and running'], 200);
});

// Define route for POST method
Route::post('/health', function () {
    return response()->json(['status' => 'Server is up and running'], 200);
});

// Define route for PUT method
Route::put('/health', function () {
    return response()->json(['status' => 'Server is up and running'], 200);
});

// Define route for DELETE method
Route::delete('/health', function () {
    return response()->json(['status' => 'Server is up and running'], 200);
});

// Define route for PATCH method
Route::patch('/health', function () {
    return response()->json(['status' => 'Server is up and running'], 200);
});



Route::post('/v1.0/files/single-file-upload', [FileManagementController::class, "createFileSingle"]);
Route::post('/v1.0/files/multiple-file-upload', [FileManagementController::class, "createFileMultiple"]);



Route::post('/v1.0/register', [AuthController::class, "register"]);
Route::post('/v1.0/login', [AuthController::class, "login"]);
Route::post('/v2.0/login', [AuthController::class, "loginV2"]);
Route::post('/v1.0/send-otp', [AuthController::class, "sendOtp"]);
Route::post('/v1.0/login-with-otp', [AuthController::class, "loginWithOtp"]);



Route::post('/forgetpassword', [AuthController::class, "storeToken"]);
Route::post('/v2.0/forgetpassword', [AuthController::class, "storeTokenV2"]);
Route::post('/resend-email-verify-mail', [AuthController::class, "resendEmailVerifyToken"]);
Route::patch('/forgetpassword/reset/{token}', [AuthController::class, "changePasswordByToken"]);
Route::post('/auth/check/email', [AuthController::class, "checkEmail"]);
Route::post('/auth/check/business/email', [AuthController::class, "checkBusinessEmail"]);


// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^
// Protected Routes
// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^

Route::middleware(['auth:api', "mail.setting",])->group(function () {

    Route::post('/v2.0/files/single-file-upload', [FileManagementController::class, "createFileSingleV2"]);
    Route::post('/v2.0/files/multiple-file-upload', [FileManagementController::class, "createFileMultipleV2"]);
    Route::get('/v1.0/file/{filename}', [FileManagementController::class, "getFile"]);
    Route::post('/v1.0/logout', [AuthController::class, "logout"]);
    Route::get('/v1.0/user', [AuthController::class, "getUser"]);
    Route::get('/v2.0/user', [AuthController::class, "getUserV2"]);
    Route::get('/v3.0/user', [AuthController::class, "getUserV3"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // notification management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::get('/v1.0/notifications', [NotificationController::class, "getNotifications"]);
    Route::get('/v1.0/notifications/{business_id}/{perPage}', [NotificationController::class, "getNotificationsByBusinessId"]);
    Route::put('/v1.0/notifications/change-status', [NotificationController::class, "updateNotificationStatus"]);
    Route::delete('/v1.0/notifications/{id}', [NotificationController::class, "deleteNotificationById"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // notification management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // dashboard section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::get('/v1.0/business-employee-dashboard/present-hours', [DashboardManagementController::class, "getBusinessEmployeeDashboardDataPresentHours"]);
    Route::get('/v1.0/business-employee-dashboard', [DashboardManagementController::class, "getBusinessEmployeeDashboardData"]);
    Route::get('/v2.0/business-employee-dashboard', [DashboardManagementController::class, "getBusinessEmployeeDashboardDataV2"]);
    Route::get('/v1.0/business-employee-dashboard/leaves', [DashboardManagementController::class, "getBusinessEmployeeDashboardDataLeaves"]);



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    // end dashboard section
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


    Route::get('/v1.0/reseller-dashboard', [DashboardManagementController::class, "getResellerDashboardData"]);
    Route::get('/v1.0/business-manager-dashboard', [DashboardManagementController::class, "getBusinessManagerDashboardData"]);

    Route::get('/v1.0/business-manager-dashboard/other-widgets', [DashboardManagementController::class, "getBusinessManagerDashboardDataOtherWidgets"]);
    Route::get('/v1.0/business-manager-dashboard/sponsorship-expiries/{duration}', [DashboardManagementController::class, "getBusinessManagerDashboardDataSponsorshipExpiries"]);
    Route::get('/v1.0/business-manager-dashboard/right-to-work-expiries/{duration}', [DashboardManagementController::class, "getBusinessManagerDashboardDataRightToWorkExpiries"]);
    Route::get('/v1.0/business-manager-dashboard/visa-expiries/{duration}', [DashboardManagementController::class, "getBusinessManagerDashboardDataVisaExpiries"]);
    Route::get('/v1.0/business-manager-dashboard/passport-expiries/{duration}', [DashboardManagementController::class, "getBusinessManagerDashboardDataPassportExpiries"]);
    Route::get('/v1.0/business-manager-dashboard/pension-expiries/{duration}', [DashboardManagementController::class, "getBusinessManagerDashboardDataPensionExpiries"]);
    Route::get('/v1.0/business-manager-dashboard/combined-expiries', [DashboardManagementController::class, "getBusinessManagerDashboardDataCombinedExpiries"]);
    Route::get('/v1.0/business-manager-dashboard/holidays', [DashboardManagementController::class, "getBusinessManagerDashboardDataHolidays"]);
    Route::get('/v1.0/business-manager-dashboard/leaves', [DashboardManagementController::class, "getBusinessManagerDashboardDataLeaves"]);
    Route::get('/v1.0/business-manager-dashboard/leaves/{status}/{duration}', [DashboardManagementController::class, "getBusinessManagerDashboardDataLeavesByStatus"]);
    Route::get('/v1.0/business-manager-dashboard/leaves-holidays', [DashboardManagementController::class, "getBusinessManagerDashboardDataLeavesAndHolidays"]);
    Route::get('/v1.0/business-manager-dashboard/pensions/{status}/{duration}', [DashboardManagementController::class, "getBusinessManagerDashboardDataPensionsByStatus"]);
    Route::get('/v1.0/business-manager-dashboard/pensions', [DashboardManagementController::class, "getBusinessManagerDashboardDataPensions"]);
    Route::get('/v1.0/business-manager-dashboard/present', [DashboardManagementController::class, "getBusinessManagerDashboardDataPresent"]);
    Route::get('/v1.0/business-manager-dashboard/absent', [DashboardManagementController::class, "getBusinessManagerDashboardDataAbsent"]);
    Route::get('/v1.0/business-manager-dashboard/open-roles/{duration}', [DashboardManagementController::class, "getBusinessManagerDashboardDataOpenRoles"]);
    Route::get('/v1.0/business-manager-dashboard/total-employee/{duration}', [DashboardManagementController::class, "getBusinessManagerDashboardDataTotalEmployee"]);
    Route::get('/v1.0/business-manager-dashboard/present-absent-hours', [DashboardManagementController::class, "getBusinessManagerDashboardDataPresentAbsentHours"]);
    Route::get('/v1.0/business-manager-dashboard/present-absent-days', [DashboardManagementController::class, "getBusinessManagerDashboardDataPresentAbsentDays"]);


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // announcements  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/announcements', [AnnouncementController::class, "createAnnouncement"]);
    Route::put('/v1.0/announcements', [AnnouncementController::class, "updateAnnouncement"]);
    Route::get('/v1.0/announcements', [AnnouncementController::class, "getAnnouncements"]);
    Route::get('/v1.0/announcements/{id}', [AnnouncementController::class, "getAnnouncementById"]);
    Route::delete('/v1.0/announcements/{ids}', [AnnouncementController::class, "deleteAnnouncementsByIds"]);

    Route::get('/v1.0/clients/announcements', [AnnouncementController::class, "getAnnouncementsClient"]);
    Route::get('/v1.0/clients/announcements-count', [AnnouncementController::class, "getAnnouncementsCountClient"]);
    Route::put('/v1.0/clients/announcements/change-status', [AnnouncementController::class, "updateAnnouncementStatus"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end announcements management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


});




Route::middleware(['auth:api', "mail.setting", "business.subscription.check", "authorization.check"])->group(function () {


    Route::patch('/auth/changepassword', [AuthController::class, "changePassword"]);

    Route::put('/v1.0/update-user-info', [AuthController::class, "updateUserInfo"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // system  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::put('/v1.0/system-settings', [SystemSettingController::class, "updateSystemSetting"]);
    Route::get('/v1.0/system-settings', [SystemSettingController::class, "getSystemSetting"]);



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end system management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // business email setting management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::put('/v1.0/email-settings', [BusinessEmailSettingController::class, "updateEmailSetting"]);
    Route::get('/v1.0/email-settings', [BusinessEmailSettingController::class, "getEmailSetting"]);




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // business email setting management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@





    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // modules  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::put('/v1.0/modules/toggle-active', [ModuleController::class, "toggleActiveModule"]);

    Route::put('/v1.0/business-modules/enable', [ModuleController::class, "enableBusinessModule"]);

    Route::put('/v1.0/service-plan-modules/enable', [ModuleController::class, "enableServicePlanModule"]);

    Route::get('/v1.0/business-modules/{business_id}', [ModuleController::class, "getBusinessModules"]);


    Route::get('/v1.0/modules', [ModuleController::class, "getModules"]);


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end modules management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    //  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/business-tiers', [BusinessTierController::class, "createBusinessTier"]);
    Route::put('/v1.0/business-tiers', [BusinessTierController::class, "updateBusinessTier"]);
    Route::get('/v1.0/business-tiers', [BusinessTierController::class, "getBusinessTiers"]);
    Route::get('/v1.0/business-tiers/{id}', [BusinessTierController::class, "getBusinessTierById"]);
    Route::delete('/v1.0/business-tiers/{ids}', [BusinessTierController::class, "deleteBusinessTiersByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end job platform management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    //  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/service-plans', [ServicePlanController::class, "createServicePlan"]);
    Route::put('/v1.0/service-plans', [ServicePlanController::class, "updateServicePlan"]);
    Route::get('/v1.0/service-plans', [ServicePlanController::class, "getServicePlans"]);
    Route::get('/v1.0/service-plans/{id}', [ServicePlanController::class, "getServicePlanById"]);
    Route::delete('/v1.0/service-plans/{ids}', [ServicePlanController::class, "deleteServicePlansByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end job platform management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@






    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // ********************************************
    // user management section --user
    // ********************************************


    Route::post('/v1.0/users/import', [UserManagementController::class, 'importUsers']);
    Route::post('/v1.0/users', [UserManagementController::class, "createUser"]);
    Route::post('/v2.0/users', [UserManagementController::class, "createUserV2"]);
    Route::put('/v1.0/users', [UserManagementController::class, "updateUser"]);
    Route::put('/v2.0/users', [UserManagementController::class, "updateUserV2"]);
    Route::put('/v3.0/users', [UserManagementController::class, "updateUserV3"]);
    Route::put('/v4.0/users', [UserManagementController::class, "updateUserV4"]);
    Route::put('/v5.0/users', [UserManagementController::class, "updateUserV5"]);

    Route::put('/v1.0/users/update-password', [UserManagementController::class, "updatePassword"]);
    Route::put('/v1.0/users/activate-email-manually', [UserManagementController::class, "activateEmailManually"]);
    Route::put('/v1.0/users/assign-roles', [UserManagementController::class, "assignUserRole"]);
    Route::put('/v1.0/users/assign-permissions', [UserManagementController::class, "assignUserPermission"]);
    Route::put('/v1.0/users/profile', [UserManagementController::class, "updateUserProfile"]);
    Route::put('/v2.0/users/profile', [UserManagementController::class, "updateUserProfileV2"]);
    Route::put('/v1.0/users/profile-picture', [UserManagementController::class, "updateUserProfilePicture"]);
    Route::put('/v1.0/users/toggle-active', [UserManagementController::class, "toggleActiveUser"]);
    Route::put('/v1.0/users/exit', [UserManagementController::class, "exitUser"]);
    Route::put('/v1.0/users/update/exit', [UserManagementController::class, "updateUserExit"]);
    Route::put('/v1.0/users/rejoin', [UserManagementController::class, "rejoinUser"]);
    Route::put('/v1.0/users/toggle-pension-eligibility', [UserManagementController::class, "togglePensionEligibility"]);
    Route::put('/v1.0/users/update-address', [UserManagementController::class, "updateUserAddress"]);
    Route::put('/v1.0/users/update-bank-details', [UserManagementController::class, "updateUserBankDetails"]);
    Route::put('/v1.0/users/update-joining-date', [UserManagementController::class, "updateUserJoiningDate"]);
    Route::put('/v1.0/users/update-emergency-contact', [UserManagementController::class, "updateEmergencyContact"]);
    Route::get('/v1.0/users', [UserManagementController::class, "getUsers"]);
    Route::get('/v2.0/users', [UserManagementController::class, "getUsersV2"]);
    Route::get('/v3.0/users', [UserManagementController::class, "getUsersV3"]);
    Route::get('/v4.0/users', [UserManagementController::class, "getUsersV4"]);
    Route::get('/v5.0/users', [UserManagementController::class, "getUsersV5"]);
    Route::get('/v6.0/users', [UserManagementController::class, "getUsersV6"]);
    Route::get('/v7.0/users', [UserManagementController::class, "getUsersV7"]);
    Route::get('/v8.0/users', [UserManagementController::class, "getUsersV8"]);
    Route::get('/v1.0/users/{id}', [UserManagementController::class, "getUserById"]);
    Route::get('/v1.0/users-exits', [UserManagementController::class, "getUserExits"]);
    Route::get('/v1.0/users-present-absent', [UserManagementController::class, "getUsersPresentAbsent"]);
    Route::get('/v1.0/users-scheduled', [UserManagementController::class, "getScheduledUsers"]);
    Route::get('/v1.0/users/get/user-activity', [UserManagementController::class, "getUserActivity"]);
    Route::get('/v2.0/users/{id}', [UserManagementController::class, "getUserByIdV2"]);
    Route::get('/v3.0/users/{id}', [UserManagementController::class, "getUserByIdV3"]);
    Route::get('/v4.0/users/{id}', [UserManagementController::class, "getUserByIdV4"]);
    Route::get('/v5.0/users/{id}', [UserManagementController::class, "getUserByIdV5"]);
    Route::get('/v1.0/users/generate/employee-id', [UserManagementController::class, "generateEmployeeId"]);
    Route::get('/v1.0/users/validate/employee-id/{user_id}', [UserManagementController::class, "validateEmployeeId"]);
    Route::get('/v1.0/users/get-leave-details/{id}', [UserManagementController::class, "getLeaveDetailsByUserId"]);
    Route::get('/v1.0/load-global-data-for-attendances', [UserManagementController::class, "getLoadGlobalDataForAttendance"]);
    Route::get('/v1.0/users/get-attendance-leave-holiday-weekends/{id}', [UserManagementController::class, "getDisableDaysForAttendanceByUserId"]);
    Route::get('/v1.0/users/get-attendances/{id}', [UserManagementController::class, "getAttendancesByUserId"]);
    Route::get('/v1.0/users/get-leaves/{id}', [UserManagementController::class, "getLeavesByUserId"]);
    Route::get('/v1.0/users/get-holiday-details/{id}', [UserManagementController::class, "getholidayDetailsByUserId"]);
    Route::get('/v1.0/users/get-schedule-information/by-user', [UserManagementController::class, "getScheduleInformation"]);
    Route::get('/v2.0/users/get-schedule-information/by-user', [UserManagementController::class, "getScheduleInformationV2"]);
    Route::get('/v3.0/users/get-schedule-information/by-user', [UserManagementController::class, "getScheduleInformationV3"]);
    Route::get('/v1.0/users/get-recruitment-processes/{id}', [UserManagementController::class, "getRecruitmentProcessesByUserId"]);
    Route::delete('/v1.0/users/{ids}', [UserManagementController::class, "deleteUsersByIds"]);






    Route::get('/v1.0/user-test', [UserManagementController::class, "getUserTest"]);
    Route::post('/v1.0/user-test', [UserManagementController::class, "createUserTest"]);
    Route::post('/v2.0/user-test', [UserManagementController::class, "createUserTestV2"]);









    // ********************************************
    // user management section --role
    // ********************************************
    Route::get('/v1.0/initial-role-permissions', [RolesController::class, "getInitialRolePermissions"]);
    Route::get('/v1.0/initial-permissions', [RolesController::class, "getInitialPermissions"]);
    Route::post('/v1.0/roles', [RolesController::class, "createRole"]);
    Route::put('/v1.0/roles', [RolesController::class, "updateRole"]);
    Route::get('/v1.0/roles', [RolesController::class, "getRoles"]);
    Route::get('/v1.0/roles/{id}', [RolesController::class, "getRoleById"]);
    Route::delete('/v1.0/roles/{ids}', [RolesController::class, "deleteRolesByIds"]);
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    // end user management section
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user document  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/user-documents', [UserDocumentController::class, "createUserDocument"]);
    Route::put('/v1.0/user-documents', [UserDocumentController::class, "updateUserDocument"]);
    Route::get('/v1.0/user-documents', [UserDocumentController::class, "getUserDocuments"]);
    Route::get('/v1.0/user-documents/{id}', [UserDocumentController::class, "getUserDocumentById"]);
    Route::delete('/v1.0/user-documents/{ids}', [UserDocumentController::class, "deleteUserDocumentsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user document management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user recruitment process  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-recruitment-processes', [UserRecruitmentProcessController::class, "createUserRecruitmentProcess"]);
    Route::put('/v1.0/user-recruitment-processes', [UserRecruitmentProcessController::class, "updateUserRecruitmentProcess"]);
    Route::get('/v1.0/user-recruitment-processes/{id}', [UserRecruitmentProcessController::class, "getUserRecruitmentProcessesById"]);
    Route::delete('/v1.0/user-recruitment-processes/{ids}', [UserRecruitmentProcessController::class, "deleteUserRecruitmentProcess"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user recruitment process  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user recruitment process  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/termination-processes', [TerminationProcessController::class, "createTerminationProcess"]);
    Route::put('/v1.0/termination-processes', [TerminationProcessController::class, "updateTerminationProcess"]);
    Route::get('/v1.0/termination-processes/{id}', [TerminationProcessController::class, "getTerminationProcessesById"]);
    Route::delete('/v1.0/termination-processes/{ids}', [TerminationProcessController::class, "deleteTerminationProcess"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user recruitment process  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user recruitment process  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/candidate-recruitment-processes', [CandidateRecruitmentProcessController::class, "createCandidateRecruitmentProcess"]);
    Route::put('/v1.0/candidate-recruitment-processes', [CandidateRecruitmentProcessController::class, "updateCandidateRecruitmentProcess"]);
    Route::get('/v1.0/candidate-recruitment-processes/{id}', [CandidateRecruitmentProcessController::class, "getCandidateRecruitmentProcessesById"]);
    Route::delete('/v1.0/candidate-recruitment-processes/{ids}', [CandidateRecruitmentProcessController::class, "deleteCandidateRecruitmentProcess"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user recruitment process  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user recruitment process  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-recruitment-processes', [UserRecruitmentProcessController::class, "createUserRecruitmentProcess"]);
    Route::put('/v1.0/user-recruitment-processes', [UserRecruitmentProcessController::class, "updateUserRecruitmentProcess"]);
    Route::get('/v1.0/user-recruitment-processes/{id}', [UserRecruitmentProcessController::class, "getUserRecruitmentProcessesById"]);
    Route::delete('/v1.0/user-recruitment-processes/{ids}', [UserRecruitmentProcessController::class, "deleteUserRecruitmentProcess"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user recruitment process  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@







    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user job history  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-job-histories', [UserJobHistoryController::class, "createUserJobHistory"]);
    Route::put('/v1.0/user-job-histories', [UserJobHistoryController::class, "updateUserJobHistory"]);
    Route::get('/v1.0/user-job-histories', [UserJobHistoryController::class, "getUserJobHistories"]);
    Route::get('/v1.0/user-job-histories/{id}', [UserJobHistoryController::class, "getUserJobHistoryById"]);
    Route::delete('/v1.0/user-job-histories/{ids}', [UserJobHistoryController::class, "deleteUserJobHistoriesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user job history management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user education history  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-education-histories', [UserEducationHistoryController::class, "createUserEducationHistory"]);
    Route::put('/v1.0/user-education-histories', [UserEducationHistoryController::class, "updateUserEducationHistory"]);
    Route::get('/v1.0/user-education-histories', [UserEducationHistoryController::class, "getUserEducationHistories"]);
    Route::get('/v1.0/user-education-histories/{id}', [UserEducationHistoryController::class, "getUserEducationHistoryById"]);
    Route::delete('/v1.0/user-education-histories/{ids}', [UserEducationHistoryController::class, "deleteUserEducationHistoriesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user education history management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@






    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user letters management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-letters', [UserLetterController::class, "createUserLetter"]);
    Route::post('/v1.0/user-letters/generate', [UserLetterController::class, "generateUserLetter"]);
    Route::post('/v1.0/user-letters/download', [UserLetterController::class, "downloadUserLetter"]);
    Route::post('/v1.0/user-letters/send', [UserLetterController::class, "sendUserLetterEmail"]);
    Route::put('/v1.0/user-letters', [UserLetterController::class, "updateUserLetter"]);
    Route::put('/v1.0/user-letters/view', [UserLetterController::class, "updateUserLetterView"]);
    Route::get('/v1.0/user-letters', [UserLetterController::class, "getUserLetters"]);
    Route::get('/v1.0/user-letters-histories', [UserLetterController::class, "getUserLetterHistories"]);
    Route::delete('/v1.0/user-letters/{ids}', [UserLetterController::class, "deleteUserLettersByIds"]);


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user letters management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user payslip   management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-payslips', [UserPayslipController::class, "createUserPayslip"]);
    Route::put('/v1.0/user-payslips', [UserPayslipController::class, "updateUserPayslip"]);
    Route::get('/v1.0/user-payslips', [UserPayslipController::class, "getUserPayslips"]);
    Route::get('/v1.0/user-payslips/{id}', [UserPayslipController::class, "getUserPayslipById"]);
    Route::delete('/v1.0/user-payslips/{ids}', [UserPayslipController::class, "deleteUserPayslipsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user payslip history management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user notes management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-notes', [UserNoteController::class, "createUserNote"]);
    Route::put('/v1.0/user-notes', [UserNoteController::class, "updateUserNote"]);
    Route::put('/v1.0/user-notes/by-business-owner', [UserNoteController::class, "updateUserNoteByBusinessOwner"]);
    Route::get('/v1.0/user-notes', [UserNoteController::class, "getUserNotes"]);
    Route::get('/v1.0/user-notes/{id}', [UserNoteController::class, "getUserNoteById"]);
    Route::delete('/v1.0/user-notes/{ids}', [UserNoteController::class, "deleteUserNotesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user notes management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user address history  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-address-histories', [UserAddressHistoryController::class, "createUserAddressHistory"]);
    Route::put('/v1.0/user-address-histories', [UserAddressHistoryController::class, "updateUserAddressHistory"]);
    Route::get('/v1.0/user-address-histories', [UserAddressHistoryController::class, "getUserAddressHistories"]);
    Route::get('/v1.0/user-address-histories/{id}', [UserAddressHistoryController::class, "getUserAddressHistoryById"]);
    Route::delete('/v1.0/user-address-histories/{ids}', [UserAddressHistoryController::class, "deleteUserAddressHistoriesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user address history management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user passport history  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/user-passport-histories', [UserPassportHistoryController::class, "createUserPassportHistory"]);
    Route::put('/v1.0/user-passport-histories', [UserPassportHistoryController::class, "updateUserPassportHistory"]);
    Route::get('/v1.0/user-passport-histories', [UserPassportHistoryController::class, "getUserPassportHistories"]);
    Route::get('/v1.0/user-passport-histories/{id}', [UserPassportHistoryController::class, "getUserPassportHistoryById"]);
    Route::delete('/v1.0/user-passport-histories/{ids}', [UserPassportHistoryController::class, "deleteUserPassportHistoriesByIds"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user passport history management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user passport history  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/user-visa-histories', [UserVisaHistoryController::class, "createUserVisaHistory"]);
    Route::put('/v1.0/user-visa-histories', [UserVisaHistoryController::class, "updateUserVisaHistory"]);
    Route::get('/v1.0/user-visa-histories', [UserVisaHistoryController::class, "getUserVisaHistories"]);
    Route::get('/v1.0/user-visa-histories/{id}', [UserVisaHistoryController::class, "getUserVisaHistoryById"]);
    Route::delete('/v1.0/user-visa-histories/{ids}', [UserVisaHistoryController::class, "deleteUserVisaHistoriesByIds"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user passport history management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@






    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user passport history  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/user-right-to-work-histories', [UserRightToWorkHistoryController::class, "createUserRightToWorkHistory"]);
    Route::put('/v1.0/user-right-to-work-histories', [UserRightToWorkHistoryController::class, "updateRightToWorkHistory"]);
    Route::get('/v1.0/user-right-to-work-histories', [UserRightToWorkHistoryController::class, "getUserRightToWorkHistories"]);
    Route::get('/v1.0/user-right-to-work-histories/{id}', [UserRightToWorkHistoryController::class, "getUserRightToWorkHistoryById"]);
    Route::delete('/v1.0/user-right-to-work-histories/{ids}', [UserRightToWorkHistoryController::class, "deleteUserRightToWorkHistoriesByIds"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user passport history management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user sponsorship history history  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-sponsorship-histories', [UserSponsorshipHistoryController::class, "createUserSponsorshipHistory"]);
    Route::put('/v1.0/user-sponsorship-histories', [UserSponsorshipHistoryController::class, "updateUserSponsorshipHistory"]);
    Route::get('/v1.0/user-sponsorship-histories', [UserSponsorshipHistoryController::class, "getUserSponsorshipHistories"]);
    Route::get('/v1.0/user-sponsorship-histories/{id}', [UserSponsorshipHistoryController::class, "getUserSponsorshipHistoryById"]);
    Route::delete('/v1.0/user-sponsorship-histories/{ids}', [UserSponsorshipHistoryController::class, "deleteUserSponsorshipHistoriesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user sponsorship  history management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@








    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user pension history management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-pension-histories', [UserPensionHistoryController::class, "createUserPensionHistory"]);
    Route::put('/v1.0/user-pension-histories', [UserPensionHistoryController::class, "updateUserPensionHistory"]);
    Route::get('/v1.0/user-pension-histories', [UserPensionHistoryController::class, "getUserPensionHistories"]);
    Route::get('/v1.0/user-pension-histories/{id}', [UserPensionHistoryController::class, "getUserPensionHistoryById"]);
    Route::delete('/v1.0/user-pension-histories/{ids}', [UserPensionHistoryController::class, "deleteUserPensionHistoriesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user pension  history management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@










    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user asset  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/user-assets', [UserAssetController::class, "createUserAsset"]);
    Route::put('/v1.0/user-assets/add-existing', [UserAssetController::class, "addExistingUserAsset"]);
    Route::put('/v1.0/user-assets', [UserAssetController::class, "updateUserAsset"]);
    Route::put('/v1.0/user-assets/return', [UserAssetController::class, "returnUserAsset"]);
    Route::get('/v1.0/user-assets', [UserAssetController::class, "getUserAssets"]);
    Route::get('/v1.0/user-assets/{id}', [UserAssetController::class, "getUserAssetById"]);
    Route::delete('/v1.0/user-assets/{ids}', [UserAssetController::class, "deleteUserAssetsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user asset management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // user social site  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/user-social-sites', [UserSocialSiteController::class, "createUserSocialSite"]);
    Route::put('/v1.0/user-social-sites', [UserSocialSiteController::class, "updateUserSocialSite"]);
    Route::get('/v1.0/user-social-sites', [UserSocialSiteController::class, "getUserSocialSites"]);
    Route::get('/v1.0/user-social-sites/{id}', [UserSocialSiteController::class, "getUserSocialSiteById"]);
    Route::delete('/v1.0/user-social-sites/{ids}', [UserSocialSiteController::class, "deleteUserSocialSitesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end user social site management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // business management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/auth/register-with-business', [BusinessController::class, "registerUserWithBusiness"]);
    Route::post('/v1.0/businesses', [BusinessController::class, "createBusiness"]);
    Route::put('/v1.0/businesses/toggle-active', [BusinessController::class, "toggleActiveBusiness"]);
    Route::put('/v1.0/businesses', [BusinessController::class, "updateBusiness"]);
    Route::put('/v1.0/businesses-take-over', [BusinessController::class, "takeOverBusiness"]);
    Route::put('/v1.0/businesses-part-4', [BusinessController::class, "updateBusinessPart4"]);
    Route::put('/v1.0/businesses-part-1', [BusinessController::class, "updateBusinessPart1"]);
    Route::put('/v1.0/businesses-part-2', [BusinessController::class, "updateBusinessPart2"]);
    Route::put('/v2.0/businesses-part-2', [BusinessController::class, "updateBusinessPart2V2"]);
    Route::put('/v1.0/businesses-part-3', [BusinessController::class, "updateBusinessPart3"]);
    Route::put('/v1.0/business-pension-information', [BusinessController::class, "updateBusinessPensionInformation"]);
    Route::put('/v1.0/businesses/separate', [BusinessController::class, "updateBusinessSeparate"]);
    Route::get('/v1.0/businesses', [BusinessController::class, "getBusinesses"]);
    Route::get('/v1.0/businesses/{id}', [BusinessController::class, "getBusinessById"]);
    Route::get('/v2.0/businesses/{id}', [BusinessController::class, "getBusinessByIdV2"]);
    Route::get('/v1.0/businesses-id-by-email/{email}', [BusinessController::class, "getBusinessIdByEmail"]);
    Route::get('/v1.0/businesses-download/{id}', [ExportImportController::class, "downloadBusinessById"]);
    Route::post('/v1.0/businesses-import', [ExportImportController::class, "importBusiness"]);
    Route::get('/v1.0/business-subscriptions/{id}', [BusinessController::class, "getSubscriptionsByBusinessId"]);
    Route::delete('/v1.0/businesses/{ids}', [BusinessController::class, "deleteBusinessesByIds"]);
    Route::get('/v1.0/businesses/by-business-owner/all', [BusinessController::class, "getAllBusinessesByBusinessOwner"]);
    Route::get('/v1.0/businesses-pension-information/{id}', [BusinessController::class, "getBusinessPensionInformationById"]);
    Route::get('/v1.0/businesses-pension-information-history/{id}', [BusinessController::class, "getBusinessPensionInformationHistoryByBusinessId"]);
    Route::delete('/v1.0/businesses-pension-information-history/{ids}', [BusinessController::class, "deleteBusinessPensionInformationHistoryByIds"]);

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    // end business management section
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // business Time Management
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::patch('/v1.0/business-times', [BusinessTimesController::class, "updateBusinessTimes"]);
    Route::get('/v1.0/business-times', [BusinessTimesController::class, "getBusinessTimes"]);


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // Garage Background Image Management
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    // end businesses Background Image Management
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // template management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    // ********************************************
    // template management section --wrapper
    // ********************************************
    Route::put('/v1.0/email-template-wrappers', [EmailTemplateWrapperController::class, "updateEmailTemplateWrapper"]);
    Route::get('/v1.0/email-template-wrappers/{perPage}', [EmailTemplateWrapperController::class, "getEmailTemplateWrappers"]);
    Route::get('/v1.0/email-template-wrappers/single/{id}', [EmailTemplateWrapperController::class, "getEmailTemplateWrapperById"]);

    // ********************************************
    // template management section
    // ********************************************
    Route::post('/v1.0/email-templates', [EmailTemplateController::class, "createEmailTemplate"]);
    Route::put('/v1.0/email-templates', [EmailTemplateController::class, "updateEmailTemplate"]);
    Route::get('/v1.0/email-templates/{perPage}', [EmailTemplateController::class, "getEmailTemplates"]);
    Route::get('/v1.0/email-templates/single/{id}', [EmailTemplateController::class, "getEmailTemplateById"]);
    Route::get('/v1.0/email-template-types', [EmailTemplateController::class, "getEmailTemplateTypes"]);
    Route::delete('/v1.0/email-templates/{id}', [EmailTemplateController::class, "deleteEmailTemplateById"]);

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    // template management section
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


    // ********************************************
    // notification template management section
    // ********************************************

    Route::put('/v1.0/notification-templates', [NotificationTemplateController::class, "updateNotificationTemplate"]);
    Route::get('/v1.0/notification-templates/{perPage}', [NotificationTemplateController::class, "getNotificationTemplates"]);
    Route::get('/v1.0/notification-templates/single/{id}', [NotificationTemplateController::class, "getEmailTemplateById"]);
    Route::get('/v1.0/notification-template-types', [NotificationTemplateController::class, "getNotificationTemplateTypes"]);
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    // notification template management section
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // payment type management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/payment-types', [PaymentTypeController::class, "createPaymentType"]);
    Route::put('/v1.0/payment-types', [PaymentTypeController::class, "updatePaymentType"]);
    Route::get('/v1.0/payment-types/{perPage}', [PaymentTypeController::class, "getPaymentTypes"]);
    Route::delete('/v1.0/payment-types/{id}', [PaymentTypeController::class, "deletePaymentTypeById"]);
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    // payment type management section
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%





    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // asset types management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/asset-types', [AssetTypeController::class, "createAssetType"]);
    Route::put('/v1.0/asset-types', [AssetTypeController::class, "updateAssetType"]);
    Route::put('/v1.0/asset-types/toggle-active', [AssetTypeController::class, "toggleActiveAssetType"]);
    Route::get('/v1.0/asset-types', [AssetTypeController::class, "getAssetTypes"]);
    Route::delete('/v1.0/asset-types/{ids}', [AssetTypeController::class, "deleteAssetTypesByIds"]);



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end asset types management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@






    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // department  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/departments', [DepartmentController::class, "createDepartment"]);
    Route::put('/v1.0/departments', [DepartmentController::class, "updateDepartment"]);
    Route::put('/v1.0/departments/toggle-active', [DepartmentController::class, "toggleActiveDepartment"]);
    Route::get('/v1.0/departments', [DepartmentController::class, "getDepartments"]);
    Route::get('/v2.0/departments', [DepartmentController::class, "getDepartmentsV2"]);
    Route::get('/v3.0/departments', [DepartmentController::class, "getDepartmentsV3"]);
    Route::get('/v4.0/departments', [DepartmentController::class, "getDepartmentsV4"]);
    Route::get('/v5.0/departments', [DepartmentController::class, "getDepartmentsV5"]);

    Route::get('/v1.0/departments/{id}', [DepartmentController::class, "getDepartmentById"]);
    Route::delete('/v1.0/departments/{ids}', [DepartmentController::class, "deleteDepartmentsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end department  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // holiday  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/holidays', [HolidayController::class, "createHoliday"]);
    Route::put('/v1.0/holidays', [HolidayController::class, "updateHoliday"]);
    Route::put('/v1.0/holidays/approve', [HolidayController::class, "approveHoliday"]);
    Route::get('/v1.0/holidays', [HolidayController::class, "getHolidays"]);
    Route::get('/v1.0/holidays/{id}', [HolidayController::class, "getHolidayById"]);
    Route::delete('/v1.0/holidays/{ids}', [HolidayController::class, "deleteHolidaysByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end holiday  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    Route::post('/register-device-token', [DeviceTokenController::class, 'store']);
    Route::post('/send-push', [NotificationController::class, 'sendPush']);



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // work shift  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/work-shifts', [WorkShiftController::class, "createWorkShift"]);
    Route::put('/v1.0/work-shifts', [WorkShiftController::class, "updateWorkShift"]);
    Route::put('/v1.0/work-shifts/toggle-active', [WorkShiftController::class, "toggleActiveWorkShift"]);
    Route::get('/v1.0/work-shifts', [WorkShiftController::class, "getWorkShifts"]);
    Route::get('/v2.0/work-shifts', [WorkShiftController::class, "getWorkShiftsV2"]);
    Route::get('/v3.0/work-shifts', [WorkShiftController::class, "getWorkShiftsV3"]);
    Route::get('/v1.0/work-shifts/{id}', [WorkShiftController::class, "getWorkShiftById"]);
    Route::get('/v1.0/work-shifts/get-by-user-id/{user_id}', [WorkShiftController::class, "getWorkShiftByUserId"]);
    Route::get('/v2.0/work-shifts/get-by-user-id/{user_id}', [WorkShiftController::class, "getWorkShiftByUserIdV2"]);
    Route::get('/v4.0/work-shifts/get-by-user-id/{user_id}', [WorkShiftController::class, "getWorkShiftByUserIdV4"]);
    Route::get('/v3.0/work-shifts/get-by-user-id/{user_id}', [WorkShiftController::class, "getWorkShiftByUserIdV3"]);
    Route::delete('/v1.0/work-shifts/{ids}', [WorkShiftController::class, "deleteWorkShiftsByIds"]);


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end work shift  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // work shift  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::put('/v1.0/update-work-shift', [WorkShiftHistoryController::class, "updateUserWorkShift"]);
    Route::put('/v1.0/update-work-shift-history', [WorkShiftHistoryController::class, "updateUserWorkShiftHistory"]);
    Route::put('/v1.0/add-work-shift', [WorkShiftHistoryController::class, "addUserWorkShift"]);
    Route::get('/v1.0/work-shift-histories/{id}', [WorkShiftHistoryController::class, "getWorkShiftHistoryById"]);
    Route::get('/v1.0/current-work-shift-history/{employee_id}', [WorkShiftHistoryController::class, "getCurrentWorkShiftHistory"]);
    Route::get('/v1.0/work-shift-histories-last-activity-dates', [WorkShiftHistoryController::class, "getLastActivityDates"]);
    Route::delete('/v1.0/work-shift-histories/{ids}', [WorkShiftHistoryController::class, "deleteWorkShiftHistoriesById"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end work shift  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@







    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // work shift  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/employee-rotas', [EmployeeRotaController::class, "createEmployeeRota"]);
    Route::put('/v1.0/employee-rotas', [EmployeeRotaController::class, "updateEmployeeRota"]);
    Route::put('/v1.0/employee-rotas/toggle-active', [EmployeeRotaController::class, "toggleActiveEmployeeRota"]);
    Route::get('/v1.0/employee-rotas', [EmployeeRotaController::class, "getEmployeeRotas"]);
    Route::get('/v1.0/employee-rotas/{id}', [EmployeeRotaController::class, "getEmployeeRotaById"]);
    Route::get('/v1.0/employee-rotas/get-by-user-id/{user_id}', [EmployeeRotaController::class, "getEmployeeRotaByUserId"]);
    Route::delete('/v1.0/employee-rotas/{ids}', [EmployeeRotaController::class, "deleteEmployeeRotasByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end work shift  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@





    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // job platform  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/job-platforms', [JobPlatformController::class, "createJobPlatform"]);
    Route::put('/v1.0/job-platforms', [JobPlatformController::class, "updateJobPlatform"]);
    Route::put('/v1.0/job-platforms/toggle-active', [JobPlatformController::class, "toggleActiveJobPlatform"]);
    Route::get('/v1.0/job-platforms', [JobPlatformController::class, "getJobPlatforms"]);
    Route::get('/v1.0/job-platforms/{id}', [JobPlatformController::class, "getJobPlatformById"]);
    Route::delete('/v1.0/job-platforms/{ids}', [JobPlatformController::class, "deleteJobPlatformsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end job platform management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    //  social media management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/social-sites', [SocialSiteController::class, "createSocialSite"]);
    Route::put('/v1.0/social-sites', [SocialSiteController::class, "updateSocialSite"]);
    Route::get('/v1.0/social-sites', [SocialSiteController::class, "getSocialSites"]);
    Route::get('/v1.0/social-sites/{id}', [SocialSiteController::class, "getSocialSiteById"]);
    Route::delete('/v1.0/social-sites/{ids}', [SocialSiteController::class, "deleteSocialSitesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end social media management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // designation  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/designations', [DesignationController::class, "createDesignation"]);
    Route::put('/v1.0/designations', [DesignationController::class, "updateDesignation"]);
    Route::put('/v1.0/designations/toggle-active', [DesignationController::class, "toggleActiveDesignation"]);
    Route::get('/v1.0/designations', [DesignationController::class, "getDesignations"]);
    Route::get('/v1.0/designations/{id}', [DesignationController::class, "getDesignationById"]);
    Route::delete('/v1.0/designations/{ids}', [DesignationController::class, "deleteDesignationsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end designation management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // termination types  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/termination-types', [TerminationTypeController::class, "createTerminationType"]);
    Route::put('/v1.0/termination-types', [TerminationTypeController::class, "updateTerminationType"]);
    Route::put('/v1.0/termination-types/toggle-active', [TerminationTypeController::class, "toggleActiveTerminationType"]);
    Route::get('/v1.0/termination-types', [TerminationTypeController::class, "getTerminationTypes"]);
    Route::get('/v1.0/termination-types/{id}', [TerminationTypeController::class, "getTerminationTypeById"]);
    Route::delete('/v1.0/termination-types/{ids}', [TerminationTypeController::class, "deleteTerminationTypesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end designation management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // termination reasons  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/termination-reasons', [TerminationReasonController::class, "createTerminationReason"]);
    Route::put('/v1.0/termination-reasons', [TerminationReasonController::class, "updateTerminationReason"]);
    Route::put('/v1.0/termination-reasons/toggle-active', [TerminationReasonController::class, "toggleActiveTerminationReason"]);
    Route::get('/v1.0/termination-reasons', [TerminationReasonController::class, "getTerminationReasons"]);
    Route::get('/v1.0/termination-reasons/{id}', [TerminationReasonController::class, "getTerminationReasonById"]);
    Route::delete('/v1.0/termination-reasons/{ids}', [TerminationReasonController::class, "deleteTerminationReasonsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end designation management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@







    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // bank  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/banks', [BankController::class, "createBank"]);
    Route::put('/v1.0/banks', [BankController::class, "updateBank"]);
    Route::put('/v1.0/banks/toggle-active', [BankController::class, "toggleActiveBank"]);
    Route::get('/v1.0/banks', [BankController::class, "getBanks"]);
    Route::get('/v1.0/banks/{id}', [BankController::class, "getBankById"]);
    Route::delete('/v1.0/banks/{ids}', [BankController::class, "deleteBanksByIds"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end bank management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // designation  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/job-types', [JobTypeController::class, "createJobType"]);
    Route::put('/v1.0/job-types', [JobTypeController::class, "updateJobType"]);
    Route::put('/v1.0/job-types/toggle-active', [JobTypeController::class, "toggleActiveJobType"]);
    Route::get('/v1.0/job-types', [JobTypeController::class, "getJobTypes"]);
    Route::get('/v1.0/job-types/{id}', [JobTypeController::class, "getJobTypeById"]);
    Route::delete('/v1.0/job-types/{ids}', [JobTypeController::class, "deleteJobTypesByIds"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end designation management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // work locations  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/work-locations', [WorkLocationController::class, "createWorkLocation"]);
    Route::put('/v1.0/work-locations', [WorkLocationController::class, "updateWorkLocation"]);
    Route::put('/v1.0/work-locations/toggle-active', [WorkLocationController::class, "toggleActiveWorkLocation"]);
    Route::get('/v1.0/work-locations', [WorkLocationController::class, "getWorkLocations"]);
    Route::get('/v1.0/work-locations/{id}', [WorkLocationController::class, "getWorkLocationById"]);



    Route::delete('/v1.0/work-locations/{ids}', [WorkLocationController::class, "deleteWorkLocationsByIds"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end work locations management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // recruitment processes  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/recruitment-processes', [RecruitmentProcessController::class, "createRecruitmentProcess"]);
    Route::put('/v1.0/recruitment-processes', [RecruitmentProcessController::class, "updateRecruitmentProcess"]);
    Route::put('/v1.0/recruitment-processes/position/multiple', [RecruitmentProcessController::class, "updateRecruitmentProcessPositionMultiple"]);
    Route::put('/v1.0/recruitment-processes/toggle-active', [RecruitmentProcessController::class, "toggleActiveRecruitmentProcess"]);
    Route::get('/v1.0/recruitment-processes', [RecruitmentProcessController::class, "getRecruitmentProcesses"]);
    Route::get('/v1.0/recruitment-processes/{id}', [RecruitmentProcessController::class, "getRecruitmentProcessById"]);
    Route::delete('/v1.0/recruitment-processes/{ids}', [RecruitmentProcessController::class, "deleteRecruitmentProcessesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end recruitment processes management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@








    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // letter templates management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/letter-templates', [LetterTemplateController::class, "createLetterTemplate"]);
    Route::put('/v1.0/letter-templates', [LetterTemplateController::class, "updateLetterTemplate"]);
    Route::put('/v1.0/letter-templates/toggle-active', [LetterTemplateController::class, "toggleActiveLetterTemplate"]);
    Route::get('/v1.0/letter-templates', [LetterTemplateController::class, "getLetterTemplates"]);
    Route::delete('/v1.0/letter-templates/{ids}', [LetterTemplateController::class, "deleteLetterTemplatesByIds"]);
    Route::get('/v1.0/letter-template-variables', [LetterTemplateController::class, "getLetterTemplateVariables"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end letter templates management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@





    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // employment status management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/employment-statuses', [EmploymentStatusController::class, "createEmploymentStatus"]);
    Route::put('/v1.0/employment-statuses', [EmploymentStatusController::class, "updateEmploymentStatus"]);
    Route::put('/v1.0/employment-statuses/toggle-active', [EmploymentStatusController::class, "toggleActiveEmploymentStatus"]);
    Route::get('/v1.0/employment-statuses', [EmploymentStatusController::class, "getEmploymentStatuses"]);
    Route::get('/v1.0/employment-statuses/{id}', [EmploymentStatusController::class, "getEmploymentStatusById"]);
    Route::delete('/v1.0/employment-statuses/{ids}', [EmploymentStatusController::class, "deleteEmploymentStatusesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end employment status  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // setting leave types  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/setting-leave-types', [SettingLeaveTypeController::class, "createSettingLeaveType"]);
    Route::put('/v1.0/setting-leave-types', [SettingLeaveTypeController::class, "updateSettingLeaveType"]);
    Route::put('/v1.0/setting-leave-types/toggle-active', [SettingLeaveTypeController::class, "toggleActiveSettingLeaveType"]);
    Route::put('/v1.0/setting-leave-types/toggle-earning-enabled', [SettingLeaveTypeController::class, "toggleEarningEnabledSettingLeaveType"]);
    Route::get('/v1.0/setting-leave-types', [SettingLeaveTypeController::class, "getSettingLeaveTypes"]);
    Route::get('/v1.0/setting-leave-types/{id}', [SettingLeaveTypeController::class, "getSettingLeaveTypeById"]);
    Route::delete('/v1.0/setting-leave-types/{ids}', [SettingLeaveTypeController::class, "deleteSettingLeaveTypesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end setting leave types management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // setting leave  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/setting-leave', [SettingLeaveController::class, "createSettingLeave"]);
    Route::get('/v1.0/setting-leave', [SettingLeaveController::class, "getSettingLeave"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end setting leave management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // leaves  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




    Route::get('/v1.0/manipulate-leave', [LeaveController::class, "manipulateLeaveHours"]);
    Route::post('/v1.0/leaves', [LeaveController::class, "createLeave"]);
    Route::put('/v1.0/leaves/approve', [LeaveController::class, "approveLeave"]);
    Route::put('/v1.0/leaves/approve/arrears', [LeaveController::class, "approveLeaveArrear"]);
    Route::put('/v1.0/leave-records/approve/arrears', [LeaveController::class, "approveLeaveRecordArrear"]);
    Route::put('/v1.0/leaves/bypass', [LeaveController::class, "bypassLeave"]);
    Route::put('/v1.0/leaves', [LeaveController::class, "updateLeave"]);
    Route::get('/v1.0/leaves', [LeaveController::class, "getLeaves"]);
    Route::get('/v1.0/leave-arrears', [LeaveController::class, "getLeaveArrears"]);
    Route::get('/v2.0/leaves', [LeaveController::class, "getLeavesV2"]);
    Route::get('/v3.0/leaves', [LeaveController::class, "getLeavesV3"]);
    Route::get('/v4.0/leaves', [LeaveController::class, "getLeavesV4"]);
    Route::get('/v1.0/leaves-get-dates-for-leave-year/{year}', [LeaveController::class, "getLeaveDatesForLeaveYear"]);
    Route::get('/v1.0/leaves/{id}', [LeaveController::class, "getLeaveById"]);
    Route::get('/v1.0/leaves-get-current-hourly-rate', [LeaveController::class, "getLeaveCurrentHourlyRate"]);
    Route::delete('/v1.0/leaves/{ids}', [LeaveController::class, "deleteLeavesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end leaves management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // setting attendance  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/setting-attendance', [SettingAttendanceController::class, "createSettingAttendance"]);
    Route::get('/v1.0/setting-attendance', [SettingAttendanceController::class, "getSettingAttendance"]);
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end setting attendance management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // attendances  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    Route::post('/v1.0/attendances/self/check-in', [AttendanceController::class, "createSelfAttendanceCheckIn"]);
    Route::put('/v1.0/attendances/self/check-out', [AttendanceController::class, "createSelfAttendanceCheckOut"]);
    Route::put('/v1.0/attendances/self/check-out/request', [AttendanceController::class, "createSelfAttendanceCheckOutRequest"]);
    Route::put('/v1.0/attendances/manager/check-out', [AttendanceController::class, "createManagerAttendanceCheckOut"]);
    Route::post('/v1.0/attendances/multiple', [AttendanceController::class, "createMultipleAttendance"]);
    Route::put('/v1.0/attendances', [AttendanceController::class, "updateAttendance"]);
    Route::put('/v1.0/attendances/approve', [AttendanceController::class, "approveAttendance"]);
    Route::put('/v1.0/attendances-split/approve', [AttendanceController::class, "approveAttendanceSplit"]);
    Route::put('/v1.0/attendances/approve/arrears', [AttendanceController::class, "approveAttendanceArrear"]);
    Route::get('/v1.0/attendances', [AttendanceController::class, "getAttendances"]);
    Route::get('/v2.0/attendances', [AttendanceController::class, "getAttendancesV2"]);
    Route::get('/v3.0/attendances', [AttendanceController::class, "getAttendancesV3"]);
    Route::get('/v4.0/attendances', [AttendanceController::class, "getAttendancesV4"]);
    Route::get('/v5.0/attendances', [AttendanceController::class, "getAttendancesV5"]);
    Route::get('/v1.0/attendance-arrears', [AttendanceController::class, "getAttendanceArrears"]);
    Route::get('/v1.0/attendances/{id}', [AttendanceController::class, "getAttendanceById"]);
    Route::get('/v1.0/attendances/show/check-in-status', [AttendanceController::class, "getCurrentAttendance"]);
    Route::get('/v2.0/attendances/show/check-in-status', [AttendanceController::class, "getCurrentAttendanceV2"]);

    Route::delete('/v1.0/attendances/{ids}', [AttendanceController::class, "deleteAttendancesByIds"]);
    Route::delete('/v1.0/attendances-leaves/{type}/{id}', [AttendanceController::class, "deleteAttendancesLeaveRecordsById"]);
    Route::post('/v1.0/attendances/bypass/multiple', [AttendanceController::class, "createMultipleBypassAttendance"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end attendances management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // history details  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::get('/v1.0/histories/user-assets', [HistoryDetailsController::class, "getUserAssetHistory"]);
    Route::get('/v1.0/histories/user-address-details', [HistoryDetailsController::class, "getUserAddressDetailsHistory"]);
    Route::get('/v1.0/histories/user-attendance-details', [HistoryDetailsController::class, "getUserAttendanceDetailsHistory"]);
    Route::get('/v1.0/histories/user-leave-details', [HistoryDetailsController::class, "getUserLeaveDetailsHistory"]);
    Route::get('/v1.0/histories/user-work-shift', [HistoryDetailsController::class, "getUserWorkShiftHistory"]);
    Route::get('/v1.0/histories/user-project', [HistoryDetailsController::class, "getUserProjectHistory"]);


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end history details management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // hrm localized fields management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/hrm-localized-fields', [HrmLocalizedFieldController::class, "createHrmLocalizedField"]);
    Route::put('/v1.0/hrm-localized-fields', [HrmLocalizedFieldController::class, "updateHrmLocalizedField"]);
    Route::get('/v1.0/hrm-localized-fields', [HrmLocalizedFieldController::class, "getHrmLocalizedFields"]);
    Route::delete('/v1.0/hrm-localized-fields/{ids}', [HrmLocalizedFieldController::class, "deleteHrmLocalizedFieldsByIds"]);



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end hrm localized fields management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // setting payrun  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/setting-payrun', [SettingPayrollController::class, "createSettingPayrun"]);
    Route::get('/v1.0/setting-payrun', [SettingPayrollController::class, "getSettingPayrun"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end setting payrun management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // payruns  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/payruns', [PayrunController::class, "createPayrun"]);
    Route::put('/v1.0/payruns', [PayrunController::class, "updatePayrun"]);
    Route::put('/v1.0/payruns/toggle-active', [PayrunController::class, "toggleActivePayrun"]);
    Route::get('/v1.0/payruns', [PayrunController::class, "getPayruns"]);
    Route::get('/v1.0/payruns/{id}', [PayrunController::class, "getPayrunById"]);
    Route::delete('/v1.0/payruns/{ids}', [PayrunController::class, "deletePayrunsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end payruns  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::get('/v1.0/payrolls/report', [PayrollController::class, "getPayrollsReport"]);
    Route::get('/v1.0/pending-payroll-users', [PayrollController::class, "getPendingPayrollUsers"]);
    Route::get('/v1.0/payroll-list', [PayrollController::class, "getPayrollList"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // payruns  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // setting payment date  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/setting-payment-dates', [SettingPaymentDateController::class, "createSettingPaymentDate"]);
    Route::get('/v1.0/setting-payment-dates', [SettingPaymentDateController::class, "getSettingPaymentDate"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // setting payment date  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // setting payslip  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/setting-payslip', [SettingPayrollController::class, "createSettingPayslip"]);
    Route::get('/v1.0/setting-payslip', [SettingPayrollController::class, "getSettingPayslip"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end setting payslip management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // job listings  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/job-listings', [JobListingController::class, "createJobListing"]);
    Route::put('/v1.0/job-listings', [JobListingController::class, "updateJobListing"]);
    Route::get('/v1.0/job-listings', [JobListingController::class, "getJobListings"]);
    Route::get('/v1.0/job-listings/{id}', [JobListingController::class, "getJobListingById"]);
    Route::delete('/v1.0/job-listings/{ids}', [JobListingController::class, "deleteJobListingsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end job listings  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@






    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // candidates  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/candidates', [CandidateController::class, "createCandidate"]);
    Route::put('/v1.0/candidates', [CandidateController::class, "updateCandidate"]);
    Route::get('/v1.0/candidates', [CandidateController::class, "getCandidates"]);
    Route::get('/v1.0/candidates/{id}', [CandidateController::class, "getCandidateById"]);
    Route::delete('/v1.0/candidates/{ids}', [CandidateController::class, "deleteCandidatesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end candidates management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // project  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    Route::post('/v1.0/projects', [ProjectController::class, "createProject"]);
    Route::put('/v1.0/projects/assign-user', [ProjectController::class, "assignUser"]);
    Route::put('/v1.0/projects/discharge-user', [ProjectController::class, "dischargeUser"]);
    Route::put('/v1.0/projects/assign-project', [ProjectController::class, "assignProject"]);
    Route::put('/v1.0/projects/discharge-project', [ProjectController::class, "dischargeProject"]);

    Route::put('/v1.0/projects', [ProjectController::class, "updateProject"]);
    Route::get('/v1.0/projects', [ProjectController::class, "getProjects"]);
    Route::get('/v1.0/projects/{id}', [ProjectController::class, "getProjectById"]);
    Route::delete('/v1.0/projects/{ids}', [ProjectController::class, "deleteProjectsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end project  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // project  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/task-categories', [TaskCategoryController::class, "createTaskCategory"]);
    Route::put('/v1.0/task-categories', [TaskCategoryController::class, "updateTaskCategory"]);
    Route::put('/v1.0/task-categories/toggle-active', [TaskCategoryController::class, "toggleActiveTaskCategory"]);
    Route::put('/v1.0/task-categories/position', [TaskCategoryController::class, "updateTaskCategoryPosition"]);
    Route::get('/v1.0/task-categories', [TaskCategoryController::class, "getTaskCategories"]);
    Route::get('/v2.0/task-categories', [TaskCategoryController::class, "getTaskCategoriesV2"]);
    Route::get('/v1.0/task-categories/{id}', [TaskCategoryController::class, "getTaskCategoryById"]);
    Route::get('/v1.0/task-categories-by-project-id/{project_id}', [TaskCategoryController::class, "getTaskCategoryByProjectId"]);
    Route::get('/v2.0/task-categories-by-project-id/{project_id}', [TaskCategoryController::class, "getTaskCategoryByProjectId"]);
    Route::delete('/v1.0/task-categories/{ids}', [TaskCategoryController::class, "deleteTaskCategoriesByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end project  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@






    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // project  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/labels', [LabelController::class, "createLabel"]);
    Route::put('/v1.0/labels', [LabelController::class, "updateLabel"]);
    Route::put('/v1.0/labels/assign', [LabelController::class, "assignLabel"]);
    Route::put('/v1.0/labels/discharge', [LabelController::class, "dischargeLabel"]);
    Route::get('/v1.0/labels', [LabelController::class, "getLabels"]);
    Route::get('/v1.0/labels/{id}', [LabelController::class, "getLabelById"]);
    Route::delete('/v1.0/labels/{ids}', [LabelController::class, "deleteLabelsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end project  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@





    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // project  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/tasks', [TaskController::class, "createTask"]);
    Route::put('/v1.0/tasks', [TaskController::class, "updateTask"]);
    Route::put('/v1.0/tasks/position', [TaskController::class, "updateTaskPosition"]);
    Route::get('/v1.0/tasks', [TaskController::class, "getTasks"]);
    Route::get('/v1.0/tasks/{id}', [TaskController::class, "getTaskById"]);
    Route::get('/v2.0/tasks/{id}', [TaskController::class, "getTaskByIdV2"]);
    Route::delete('/v1.0/tasks/{ids}', [TaskController::class, "deleteTasksByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end project  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // comment  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/comments', [CommentController::class, "createComment"]);
    Route::put('/v1.0/comments', [CommentController::class, "updateComment"]);
    Route::get('/v1.0/comments', [CommentController::class, "getComments"]);
    Route::get('/v1.0/comments/{id}', [CommentController::class, "getCommentById"]);
    Route::delete('/v1.0/comments/{ids}', [CommentController::class, "deleteCommentsByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end comment  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // reminders  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::post('/v1.0/reminders', [ReminderController::class, "createReminder"]);
    Route::put('/v1.0/reminders', [ReminderController::class, "updateReminder"]);
    Route::get('/v1.0/reminders-entity-names', [ReminderController::class, "getReminderEntityNames"]);
    Route::get('/v1.0/reminders', [ReminderController::class, "getReminders"]);
    Route::get('/v1.0/reminders/{id}', [ReminderController::class, "getReminderById"]);
    Route::delete('/v1.0/reminders/{ids}', [ReminderController::class, "deleteRemindersByIds"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end reminders management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // dropdown  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

    Route::get('/v1.0/dropdown-options/employee-form', [DropdownOptionsController::class, "getEmployeeFormDropdownData"]);
    Route::get('/v2.0/dropdown-options/employee-form', [DropdownOptionsController::class, "getEmployeeFormDropdownDataV2"]);
    Route::get('/v1.0/dropdown-options/employee-filter', [DropdownOptionsController::class, "getEmployeeFilterDropdownData"]);
    Route::get('/v1.0/dropdown-options/attendance-filter', [DropdownOptionsController::class, "getAttendanceFilterDropdownData"]);

    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end dropdown management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // initialization  management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::post('/v1.0/business-initializations/employee-setup', [BusinessInitializationController::class, "storeEmployeeSetupData"]);
    Route::get('/v1.0/business-initializations/employee-setup', [BusinessInitializationController::class, "getEmployeeSetupData"]);
    Route::post('/v1.0/business-initializations/recruitment-process-setup', [BusinessInitializationController::class, "storeRecruitmentProcessSetupData"]);
    Route::get('/v1.0/business-initializations/recruitment-process-setup', [BusinessInitializationController::class, "getRecruitmentProcessSetupData"]);
    Route::post('/v1.0/business-initializations/business-flow-setup', [BusinessInitializationController::class, "storeBusinessFlowSetupData"]);
    Route::get('/v1.0/business-initializations/business-flow-setup', [BusinessInitializationController::class, "getBusinessFlowSetupData"]);
    Route::post('/v1.0/business-initializations/general-setup', [BusinessInitializationController::class, "storeGeneralSetupData"]);
    Route::get('/v1.0/business-initializations/general-setup', [BusinessInitializationController::class, "getGeneralSetupData"]);


    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    // end initialization management section
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


    // @@@@@@
    // ticketing system
    // @@@@@@
    Route::get('/get-ticket-token', [ClientTicketingSystemController::class, "getTicketToken"]);
    // @@@@@@
    // ticketing system
    // @@@@@@




});

// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^
// end admin routes
// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^


Route::get('/v1.0/client/recruitment-processes', [RecruitmentProcessController::class, "getRecruitmentProcessesClient"]);

Route::get('/v1.0/client/job-platforms', [JobPlatformController::class, "getJobPlatformsClient"]);

Route::get('/v1.0/client/job-listings', [JobListingController::class, "getJobListingsClient"]);

Route::get('/v1.0/client/job-listings/{id}', [JobListingController::class, "getJobListingByIdClient"]);
Route::post('/v1.0/client/candidates', [CandidateController::class, "createCandidateClient"]);

Route::post('/v1.0/client/auth/register-with-business', [BusinessController::class, "registerUserWithBusinessClient"]);

Route::get('/v1.0/client/service-plans', [ServicePlanController::class, "getServicePlanClient"]);

Route::post('/v1.0/client/check-discount', [ServicePlanController::class, "checkDiscountClient"]);

Route::get('/v1.0/client/system-settings', [SystemSettingController::class, "getSystemSettingSettingClient"]);
Route::get('/v1.0/client/users', [UserManagementController::class, "getUsersClient"]);
Route::get('/v1.0/client/users/get-leave-details/{id}', [UserManagementController::class, "getLeaveDetailsByUserId"]);
Route::get('/v1.0/client/users/get-attendance-leave-holiday-weekends/{id}', [UserManagementController::class, "getDisableDaysForAttendanceByUserId"]);
Route::get('/v1.0/client/leaves-get-dates-for-leave-year/{year}', [LeaveController::class, "getLeaveDatesForLeaveYear"]);
Route::get('/v2.0/client/work-shifts/get-by-user-id/{user_id}', [WorkShiftController::class, "getWorkShiftByUserIdV2"]);
Route::get('/v1.0/client/manipulate-leave', [LeaveController::class, "manipulateLeaveHours"]);

Route::post('/v1.0/client/leaves', [LeaveController::class, "createLeave"]);
Route::get('/v1.0/client/businesses/{id}', [BusinessController::class, "getBusinessByIdClient"]);


Route::post('webhooks/stripe', [CustomWebhookController::class, "handleStripeWebhook"])->name("stripe.webhook");




Route::get('/v1.0/work-locations-current-time/{id}', [WorkLocationController::class, "getCurrentTimeWorkLocationId"]);

// remove below routes.
