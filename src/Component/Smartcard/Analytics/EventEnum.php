<?php

declare(strict_types=1);

namespace Component\Smartcard\Analytics;

final class EventEnum
{
    public const ASSISTANCE_START = 'assistance_start';
    public const ASSISTANCE_VALIDATED = 'assistance_validated';
    public const ASSISTANCE_END = 'assistance_end';
    public const BENEFICIARY_CREATED = 'beneficiary_created';
    public const BENEFICIARY_PACKAGE_ASSIGNED = 'beneficiary_package_assigned';
    public const BENEFICIARY_DEPOSIT_GIVEN = 'beneficiary_deposit_given';
    public const BENEFICIARY_UPDATED = 'beneficiary_updated_manually';
    public const BENEFICIARY_IMPORTED = 'beneficiary_updated_by_import';
    public const PURCHASE_MADE = 'purchase_made';
    public const PURCHASE_INVOICED = 'purchase_invoiced';
    public const CARD_ASSIGNED = 'assigned_card_to_beneficiary';
    public const CARD_DISABLED = 'card_dismissed';
    public const CARD_REUSED = 'card_was_given_someone_else';
    public const VENDOR_SYNC = 'vendor_synced_app';
    public const VENDOR_INVOICE = 'vendor_made_invoice';
}
