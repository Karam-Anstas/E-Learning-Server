<?php

use App\Http\Controllers\API\V1\{
    CategoryController,
    CertificateController,
    CourseController,
    EnrollmentController,
    RatingController,
    QuizController,
    QuizAttemptController,
    UpdateUserInfoController,
    VideoWatchController,
    SavedCourseController,
    NoteController,
    ContactMessageController,
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{
    RegisteredUserController,
    RegisterUserFromPhoneController,
    EmailVerificationNotificationController,
    NewPassWordController,
    PasswordResetLinkController,
    SessionController,
    VerifyEmailController,
};

// Apply JSON response middleware to all API routes
Route::middleware(['json.response'])->group(function () {

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//This routes made for authentication

//public routes
Route::middleware("guest:sanctum")->group(function () {
    Route::post("/register", [RegisteredUserController::class, "store"])
        ->name("register");

    Route::post("/phone-register", [RegisterUserFromPhoneController::class, "store"]);

    Route::post("/login", [SessionController::class, "store"])
        ->name("login");

    Route::post("/forgot-password", [PasswordResetLinkController::class, "store"])
        ->name("password.reset");

    Route::post("/reset-password", [NewPassWordController::class, "store"])
        ->name("password.store");

    Route::post("/send-reset-otp", [RegisterUserFromPhoneController::class, "sendResetOtp"]);
    Route::post("/reset-password-with-otp", [RegisterUserFromPhoneController::class, "resetPasswordWithOtp"]);
});

// Protected routes (require auth via Sanctum token)
Route::middleware("auth:sanctum")->group(function () {

    Route::delete("/logout", [SessionController::class, "destroy"])
        ->name("logout");

    Route::post("/email/verification-notification", [EmailVerificationNotificationController::class, "store"])
        ->middleware("throttle:6,1")
        ->name("verification.send");
});

Route::post("/resend-email-verification-link", [EmailVerificationNotificationController::class, "resend"])
    ->middleware("throttle:6.1")
    ->name("verification.link.resend");

// Email verification (signed URL + auth)
Route::get("/verify-email/{id}/{hash}", [VerifyEmailController::class, "__invoke"])
    ->middleware(["signed", "throttle:6,1"])
    ->name("verification.verify");

Route::post("/verify-otp", [RegisterUserFromPhoneController::class, "verifyOtp"]);

//This routes made for category/courses/sections/videos

Route::group(["prefix" => "v1", "namespace" => "App\Http\Controllers\API\V1"], function () {
    Route::get("categories", [CategoryController::class, "index"]);
    Route::get("/courses", [CourseController::class, "index"]);
    Route::get("/courses/trending", [CourseController::class, "trending"]);
    Route::get("/courses/{course}", [CourseController::class, "show"]);

    //course progress
    Route::get("/courses/{course}/progress", [EnrollmentController::class, "getCourseProgress"])->middleware("auth:sanctum");
    Route::post("/courses/{course}/progress", [EnrollmentController::class, "updateProgress"])->middleware("auth:sanctum");

    //Watched videos
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/videos/{video}/watch', [VideoWatchController::class, 'markAsWatched']);
        Route::get('/user/watched-videos', [VideoWatchController::class, 'getWatchedVideos']);

        // Saved Courses Routes
        Route::get('/saved-courses', [SavedCourseController::class, 'index']);
        Route::post('/saved-courses/{course}', [SavedCourseController::class, 'store']);
        Route::delete('/saved-courses/{course}', [SavedCourseController::class, 'destroy']);
        Route::get('/saved-courses/{course}/check', [SavedCourseController::class, 'isSaved']);
    });

    //enrollments routes
    Route::post("/courses/{course}/enroll", [EnrollmentController::class, "enroll"])->middleware("auth:sanctum");
    Route::delete("/courses/{course}/unenroll", [EnrollmentController::class, "unenroll"])->middleware("auth:sanctum");
    Route::get("/courses/{course}/enrollments", [EnrollmentController::class, "enrolledUsers"])->middleware("auth:sanctum");
    Route::get("/users/{user}/enrollments", [EnrollmentController::class, "userEnrollments"])->middleware("auth:sanctum");

    //ratings
    Route::post("/courses/{course}/ratings", [RatingController::class, "store"])->middleware("auth:sanctum");
    Route::put("/courses/{course}/ratings/{rating}", [RatingController::class, "update"])->middleware("auth:sanctum");
    Route::delete("/courses/{course}/ratings/{rating}", [RatingController::class, "destroy"])->middleware("auth:sanctum");
    Route::get('courses/{course}/my-rating', [RatingController::class, 'myRating'])->middleware("auth:sanctum");

    Route::post("/enrollment/check", [EnrollmentController::class, "isEnrolled"])->middleware("auth:sanctum");

    // Quiz Routes
    Route::get("courses/{course}/quiz", [QuizController::class, "index"]);
    Route::get("courses/{course}/quiz/{quiz}", [QuizController::class, "show"]);
    Route::post("courses/{course}/quiz/{quiz}/submit", [QuizController::class, "submit"])->middleware("auth:sanctum");
    Route::get('courses/{courseId}/quizzes/{quizId}/attempt-status', [QuizController::class, 'checkAttemptStatus'])->middleware("auth:sanctum");

    //user attempt
    Route::post("courses/{course}/quiz/attempts", [QuizAttemptController::class, "start"])->middleware("auth:sanctum");

    // Route::post("quiz-attempts/{attempt}/answers", [QuizAttemptController::class, "submitAnswer"])->middleware("auth:sanctum");
    Route::post("quiz-attempts/{attempt}/complete", [QuizAttemptController::class, "complete"])->middleware("auth:sanctum");
    Route::post("quiz-attempts/{attempt}/results", [QuizAttemptController::class, "results"])->middleware("auth:sanctum");

    //certificate
    Route::post("/courses/{course}/certificate", [CertificateController::class, "generate"])->middleware("auth:sanctum");

    //updateUserInfo
    Route::post("/profile", [UpdateUserInfoController::class, "updateProfile"])->middleware("auth:sanctum");

    // Notes routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('notes', NoteController::class);
    });
});

// Contact Messages Routes
Route::prefix('v1')->group(function () {
    Route::post('/contact', [ContactMessageController::class, 'store']);

    // Admin routes (protected)
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::get('/contact/messages', [ContactMessageController::class, 'index']);
        Route::patch('/contact/messages/{message}/read', [ContactMessageController::class, 'markAsRead']);
        Route::delete('/contact/messages/{message}', [ContactMessageController::class, 'destroy']);
    });
});

Route::middleware("auth:sanctum")->get("/notifications", function (Request $request) {
    return $request->user()->unreadNotifications;
});

Route::middleware("auth:sanctum")->post("/notifications/read", function (Request $request) {
    $request->user()->unreadNotifications->markAsRead();
    return response()->noContent();
});

// Google Login Routes
Route::get('/auth/google', [SessionController::class, 'redirectToGoogle']);
Route::get('/auth/google/call-back', [SessionController::class, 'handleGoogleCallback']);

}); // End of json.response middleware group


