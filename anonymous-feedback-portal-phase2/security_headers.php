<?php

// Prevent browsers from interpreting files as a different MIME type.
header("X-Content-Type-Options: nosniff");

// Prevent the application from being embedded in frames.
header("X-Frame-Options: DENY");

// Control how much referrer information is shared.
header("Referrer-Policy: strict-origin-when-cross-origin");

// Prevent sensitive pages from being cached.
header("Cache-Control: no-store");