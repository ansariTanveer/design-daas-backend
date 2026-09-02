<?php

declare(strict_types=1);

/*
 * convert errors to exceptions
 */
set_error_handler(
    function ($severity, $message, $file, $line): bool {
        // $severity should not be reported according to error_reporting()
        if (($severity & error_reporting()) === 0) {
            return true;
        }
        $vendorDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
        // ignore minor severity engine messages caused by 3rd party code
        if (($severity & (E_DEPRECATED | E_NOTICE | E_STRICT)) !== 0 && str_starts_with($file, $vendorDir)) {
            return true;
        }
        // ignore minor severity user messages caused by 3rd party code
        if (($severity & (E_USER_DEPRECATED | E_USER_NOTICE)) !== 0 && str_starts_with($file, $vendorDir)) {
            // top of the $callStack is the call to the error handler (this function) ...
            $callStack = debug_backtrace();
            // ... next() is the call to trigger_error() ...
            next($callStack);
            // ... and next() after that should be the $caller that actually caused the error
            $caller = next($callStack);
            if ($caller !== false && str_starts_with($caller['file'] ?? '', $vendorDir)) {
                return true;
            }
        }
        // convert message to exception
        throw new ErrorException($message, 0, $severity, $file, $line);
    },
);
