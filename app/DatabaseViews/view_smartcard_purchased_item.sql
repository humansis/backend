CREATE VIEW view_smartcard_purchased_item AS
SELECT
    spr.id,
    s.beneficiary_id as beneficiary_id,
    b.household_id as household_id,
    a.id as assistance_id,
    a.project_id,
    a.location_id,
    spr.product_id,
    DATE_FORMAT(sp.used_at, "%Y-%m-%dT%TZ") AS date_purchase,
    s.code as smartcard_code,
    spr.value,
    sp.vendor_id,
    LPAD(srb.id, 6, 0) AS invoice_number,
    spr.currency,
    ni.id_number
FROM smartcard_purchase_record spr
         LEFT JOIN smartcard_purchase sp ON sp.id = spr.smartcard_purchase_id
         LEFT JOIN smartcard_beneficiary s ON sp.smartcard_beneficiary_id = s.id
         LEFT JOIN invoice srb ON sp.invoice_id = srb.id
         LEFT JOIN beneficiary b ON s.beneficiary_id = b.id
         LEFT JOIN person p ON b.person_id = p.id
         LEFT JOIN national_id ni ON ni.id = ( -- to ensure that only 1 (first one) national id will be joined and no duplicities occur
                SELECT national_id.id
                FROM national_id
                WHERE national_id.person_id = p.id
                LIMIT 1
            )
         LEFT JOIN distribution_beneficiary db ON db.assistance_id = sp.assistance_id AND db.beneficiary_id = b.id
         LEFT JOIN assistance_relief_package rp ON rp.id = (
                SELECT reliefPackage.id
                FROM assistance_relief_package reliefPackage
                WHERE reliefPackage.assistance_beneficiary_id = db.id
                LIMIT 1
            )
         LEFT JOIN assistance a ON db.assistance_id = a.id
