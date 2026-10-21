<?php

return [
    // OFF by default. Explicitly enable only after merchant sandbox verification.
    'stripe_refunds_enabled' => (bool) env('HAMQADAM_STRIPE_REFUNDS_ENABLED', false),
];
