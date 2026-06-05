<?php

declare(strict_types=1);

namespace App\Enums\Reports;

enum ReportLifecycleReasonCode: string
{
    case RetryFailedGeneration = 'retry_failed_generation';
    case RetryExpiredOutput = 'retry_expired_output';
    case RequestedInError = 'requested_in_error';
    case DuplicateRequest = 'duplicate_request';
    case NoLongerNeeded = 'no_longer_needed';
    case InvalidFilters = 'invalid_filters';
    case LifecycleConflict = 'lifecycle_conflict';
    case AccessDenied = 'access_denied';
    case ValidationRejected = 'validation_rejected';
}
