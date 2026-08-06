<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Demo login (W3Mentors reference accounts)
    |--------------------------------------------------------------------------
    | Matches legacy UserAuth::getSigninForm() prefill on demo URLs:
    | lydia.deckow@dummyid.com / lydia@123
    */
    'login_enabled' => filter_var(
        env('DEMO_LOGIN_ENABLED', env('APP_ENV', 'production') === 'local'),
        FILTER_VALIDATE_BOOL
    ),

    /** Legacy W3Mentors PHP origin for OAuth redirects (GuestUser/facebookLogin, etc.) */
    'legacy_origin' => rtrim((string) env('LEGACY_ORIGIN', 'http://127.0.0.1'), '/'),

    'teacher' => [
        'email' => env('DEMO_TEACHER_EMAIL', 'lydia.deckow@dummyid.com'),
        'password' => env('DEMO_TEACHER_PASSWORD', 'lydia@123'),
        'first_name' => 'Lydia',
        'last_name' => 'Deckow',
        'username' => 'Lydia_Deckow',
    ],

    'learner' => [
        'email' => env('DEMO_LEARNER_EMAIL', 'zigepu@mailinator.com'),
        'password' => env('DEMO_LEARNER_PASSWORD', 'lydia@123'),
        'first_name' => 'Acton',
        'last_name' => 'Rhodes',
        'username' => 'Acton_Rhodes',
    ],
];
