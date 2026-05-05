<?php
declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\MeController;
use App\Controllers\MeReservationsController;
use App\Controllers\VenueController;
use App\Controllers\ReservationController;
use App\Controllers\Operator\DashboardController as OpDashboard;
use App\Controllers\Operator\DepositController as OpDeposit;
use App\Controllers\Operator\BookingController as OpBooking;
use App\Controllers\Operator\SlotController as OpSlot;
use App\Controllers\Operator\PricingController as OpPricing;
use App\Controllers\Operator\VenueController as OpVenue;
use App\Controllers\Operator\CouponController as OpCoupon;
use App\Controllers\Operator\EquipmentController as OpEquip;
use App\Controllers\Operator\CoachController as OpCoach;
use App\Controllers\Operator\ApiTokenController as OpApiToken;
use App\Controllers\RecurringController;
use App\Controllers\Api\VenueSlotController as ApiVenueSlot;
use App\Controllers\Api\CalendarController as ApiCalendar;
use App\Controllers\Api\VenueListController as ApiVenueList;
use App\Controllers\Api\MeController as ApiMe;
use App\Controllers\Api\EquipmentController as ApiEquipment;
use App\Controllers\LessonController;
use App\Controllers\SeoController;
use App\Controllers\SupportController;
use App\Controllers\Admin\DashboardController as AdDashboard;
use App\Controllers\Admin\VenueController as AdVenue;
use App\Controllers\Admin\UserController as AdUser;
use App\Controllers\Admin\ReportController as AdReport;
use App\Controllers\NotificationController;
use App\Controllers\Api\ReservationController as ApiReservation;
use App\Controllers\Api\FavoriteController as ApiFavorite;
use App\Controllers\Api\HealthController;

/** @var \App\Core\Router $router */

// ─── Public (React 렌더) ─────────────────────────────────
$router->get('/',              [HomeController::class,  'index']);
$router->get('/venues',        [VenueController::class, 'index']);
$router->get('/venues/{id}',   [VenueController::class, 'show']);

// ─── Auth ─────────────────────────────────────────────────
$router->get ('/login',                  [AuthController::class, 'showLogin']);
$router->post('/login',                  [AuthController::class, 'login']);
$router->get ('/register',               [AuthController::class, 'showRegister']);
$router->post('/register',               [AuthController::class, 'register']);
$router->post('/logout',                 [AuthController::class, 'logout']);
$router->get ('/auth/kakao',             [AuthController::class, 'kakaoRedirect']);
$router->get ('/auth/kakao/callback',    [AuthController::class, 'kakaoCallback']);
$router->get ('/auth/kakao/complete',    [AuthController::class, 'kakaoCompleteForm']);
$router->post('/auth/kakao/complete',    [AuthController::class, 'kakaoCompleteSubmit']);

// ─── Me (요인증) ────────────────────────────────────────────
$router->get('/me',              [MeController::class,             'index']);
$router->get('/me/reservations', [MeReservationsController::class, 'index']);

// ─── 예약 ─────────────────────────────────────────────────
$router->get ('/reservations/{code}',           [ReservationController::class, 'show']);
$router->post('/reservations/{code}/mark-paid', [ReservationController::class, 'markPaid']);
$router->get ('/reservations/{code}/entry',     [ReservationController::class, 'entry']);

// ─── 사용자측 보조 ──────────────────────────────────────────
$router->get('/notifications', [NotificationController::class, 'index']);
$router->get('/search',        [NotificationController::class, 'search']);

// ─── 운영자 ───────────────────────────────────────────────
$router->get ('/operator',                              [OpDashboard::class, 'index']);
$router->get ('/operator/deposits',                     [OpDeposit::class,   'index']);
$router->post('/operator/deposits/{code}/confirm',      [OpDeposit::class,   'confirm']);
$router->post('/operator/deposits/{code}/cancel',       [OpDeposit::class,   'cancel']);

$router->get ('/operator/bookings',                     [OpBooking::class,   'index']);
$router->post('/operator/bookings/{code}/check-in',     [OpBooking::class,   'checkIn']);
$router->post('/operator/bookings/{code}/noshow',       [OpBooking::class,   'noshow']);
$router->post('/operator/bookings/{code}/cancel',       [OpBooking::class,   'cancel']);

$router->get ('/operator/slots',                        [OpSlot::class,      'index']);
$router->post('/operator/slots/add',                    [OpSlot::class,      'add']);
$router->post('/operator/slots/{id}/delete',            [OpSlot::class,      'delete']);

$router->get ('/operator/pricing',                      [OpPricing::class,   'index']);
$router->post('/operator/pricing/hot-deal',             [OpPricing::class,   'createHotDeal']);
$router->post('/operator/pricing/{id}/cancel',          [OpPricing::class,   'cancelDeal']);
$router->post('/operator/pricing/auto-rules',           [OpPricing::class,   'createAutoRule']);
$router->post('/operator/pricing/auto-rules/{id}/toggle', [OpPricing::class, 'toggleAutoRule']);
$router->post('/operator/pricing/auto-rules/{id}/delete', [OpPricing::class, 'deleteAutoRule']);

$router->get ('/operator/venues',                       [OpVenue::class,     'index']);
$router->get ('/operator/venues/{id}/edit',             [OpVenue::class,     'edit']);
$router->post('/operator/venues/{id}',                  [OpVenue::class,     'update']);
$router->post('/operator/venues/{id}/courts/add',       [OpVenue::class,     'addCourt']);
$router->post('/operator/venues/{id}/courts/{cid}/delete', [OpVenue::class,  'deleteCourt']);

// ─── API ──────────────────────────────────────────────────
$router->get ('/api/health',                       [HealthController::class, 'index']);
$router->post('/api/reservations',                 [ApiReservation::class,   'create']);
$router->get ('/api/reservations/{code}/status',   [ApiReservation::class,   'status']);
$router->post('/api/favorites/{venueId}/toggle',   [ApiFavorite::class,      'toggle']);
$router->post('/api/reservations/{code}/cancel',   [ApiReservation::class,   'cancel']);
$router->get ('/api/venues/{id}/slots',            [ApiVenueSlot::class,     'show']);
$router->get ('/api/venues/{id}/calendar.ics',     [ApiCalendar::class,      'venue']);
$router->get ('/api/venues',                       [ApiVenueList::class,     'index']);
$router->get ('/api/me/location',                  [ApiMe::class,            'getLocation']);
$router->post('/api/me/location',                  [ApiMe::class,            'setLocation']);

// 환불계좌 관리 (사용자)
$router->get ('/me/refund-account',  [\App\Controllers\MeBankController::class, 'form']);
$router->post('/me/refund-account',  [\App\Controllers\MeBankController::class, 'update']);
$router->get ('/api/popular/areas',                [ApiMe::class,            'popularAreas']);
$router->get ('/api/popular/searches',             [ApiMe::class,            'popularSearches']);
$router->get ('/api/me/unread',                    [ApiMe::class,            'unreadCount']);
$router->get ('/api/venues/{id}/equipment',        [ApiEquipment::class,     'venue']);
$router->post('/api/lessons',                      [LessonController::class, 'create']);

// ─── SEO ──────────────────────────────────────────────────
$router->get('/robots.txt',  [SeoController::class, 'robots']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);

// ─── 고객센터 ─────────────────────────────────────────────
$router->get('/support',         [SupportController::class, 'index']);
$router->get('/support/terms',   [SupportController::class, 'terms']);
$router->get('/support/privacy', [SupportController::class, 'privacy']);

// ─── 정기 예약 ────────────────────────────────────────────
$router->get ('/recurring/new', [RecurringController::class, 'newForm']);
$router->post('/recurring',     [RecurringController::class, 'create']);

// ─── 운영자 — 추가 영역 ──────────────────────────────────
$router->get ('/operator/coupons',                       [OpCoupon::class,    'index']);
$router->post('/operator/coupons',                       [OpCoupon::class,    'createCoupon']);
$router->post('/operator/memberships',                   [OpCoupon::class,    'createMembership']);

$router->get ('/operator/equipment',                     [OpEquip::class,     'index']);
$router->post('/operator/equipment/add',                 [OpEquip::class,     'add']);
$router->post('/operator/equipment/{id}/delete',         [OpEquip::class,     'delete']);

$router->get ('/operator/coaches',                       [OpCoach::class,     'index']);
$router->post('/operator/coaches/add',                   [OpCoach::class,     'add']);
$router->post('/operator/coaches/{id}/delete',           [OpCoach::class,     'delete']);

$router->get ('/operator/api',                           [OpApiToken::class,  'index']);
$router->post('/operator/api/tokens',                    [OpApiToken::class,  'createToken']);
$router->post('/operator/api/tokens/{id}/revoke',        [OpApiToken::class,  'revokeToken']);
$router->post('/operator/api/webhooks',                  [OpApiToken::class,  'createWebhook']);
$router->post('/operator/api/webhooks/{id}/delete',      [OpApiToken::class,  'deleteWebhook']);

// ─── 관리자 ───────────────────────────────────────────────
$router->get ('/admin',                                  [AdDashboard::class, 'index']);
$router->get ('/admin/venues',                           [AdVenue::class,     'index']);
$router->post('/admin/venues/{id}/approve',              [AdVenue::class,     'approve']);
$router->post('/admin/venues/{id}/reject',               [AdVenue::class,     'reject']);
$router->post('/admin/venues/{id}/reactivate',           [AdVenue::class,     'reactivate']);
$router->get ('/admin/users',                            [AdUser::class,      'index']);
$router->get ('/admin/venues/{id}',                      [AdVenue::class,     'detail']);
$router->post('/operator/api/webhooks/{id}/reactivate',  [OpApiToken::class,  'reactivateWebhook']);
$router->post('/admin/users/{id}/suspend',               [AdUser::class,      'suspend']);
$router->post('/admin/users/{id}/score',                 [AdUser::class,      'adjustScore']);
$router->post('/admin/users/{id}/role',                  [AdUser::class,      'changeRole']);
$router->get ('/admin/reports',                          [AdReport::class,    'index']);

// ─── 운영자 — 신규 구장 신청 ─────────────────────────────
$router->get ('/operator/venues/new',                    [OpVenue::class,     'newForm']);
$router->post('/operator/venues',                        [OpVenue::class,     'create']);

// TODO: 향후 추가될 라우트
//   /venues               목록·검색          GET
//   /venues/{id}          상세              GET
//   /api/venues/{id}/slots?date=YYYY-MM-DD  슬롯 조회 (캘린더용)
//   /reservations         예약 신청          POST
//   /reservations/{code}  예약 상세          GET
//   /me/reservations      내 예약            GET
//   /operator/...         운영자 화면군
//   /admin/...            관리자 화면군
