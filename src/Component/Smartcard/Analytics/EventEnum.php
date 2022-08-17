<?php declare(strict_types=1);

namespace Component\Smartcard\Analytics;

final class EventEnum
{
    const ASSISTANCE_START = 'assistance_start';
    const ASSISTANCE_VALIDATED = 'assistance_validated';
    const ASSISTANCE_END = 'assistance_end';
    const BENEFICIARY_CREATED = 'beneficiary_created';
    const BENEFICIARY_PACKAGE_ASSIGNED = 'beneficiary_package_assigned';
    const BENEFICIARY_DEPOSIT_GIVEN = 'beneficiary_deposit_given';
    const BENEFICIARY_UPDATED = 'beneficiary_updated_manually';
    const BENEFICIARY_IMPORTED = 'beneficiary_updated_by_import';
    const PURCHASE_MADE = 'purchase_made';
    const PURCHASE_INVOICED = 'purchase_invoiced';
    const CARD_ASSIGNED = 'assigned_card_to_beneficiary';
    const CARD_DISABLED = 'card_dismissed';
    const CARD_REUSED = 'card_was_given_someone_else';
    const VENDOR_SYNC = 'vendor_synced_app';
    const VENDOR_INVOICE = 'vendor_made_invoice';
}
