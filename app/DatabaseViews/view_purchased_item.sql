CREATE VIEW view_purchased_item AS
SELECT
    CASE
        WHEN sd.id  IS NOT NULL THEN CONCAT(db.id, "_", sd.id, "_", spr.product_id)
        WHEN b.id   IS NOT NULL THEN CONCAT(db.id, "_", b.id, "_", vpr.product_id)
        END AS id,
    db.beneficiary_id,
    db.assistance_id,
    a.project_id,
    a.location_id,
    CASE
        WHEN sd.id  IS NOT NULL THEN spr.product_id
        WHEN b.id   IS NOT NULL THEN vpr.product_id
        END AS product_id,
    CASE
        WHEN ab.bnf_type = "hh"   THEN "Household"
        WHEN ab.bnf_type = "bnf"  THEN "Beneficiary"
        WHEN ab.bnf_type = "inst" THEN "Institution"
        WHEN ab.bnf_type = "comm" THEN "Community"
        END AS bnf_type,
    c.id as commodity_id,
    c.modality_type as modality_type,
    CASE
        WHEN sd.id  IS NOT NULL THEN DATE_FORMAT(sd.distributed_at, "%Y-%m-%dT%TZ")
        WHEN b.id   IS NOT NULL THEN null
        END AS date_distribution,
    CASE
        WHEN sd.id  IS NOT NULL THEN DATE_FORMAT(sp.used_at, "%Y-%m-%dT%TZ")
        WHEN b.id   IS NOT NULL THEN DATE_FORMAT(vp.used_at, "%Y-%m-%dT%TZ")
        END AS date_purchase,
    CASE
        WHEN sd.id IS NOT NULL THEN s.code
        WHEN b.id  IS NOT NULL THEN v.code
        END AS carrier_number,
    CASE
        WHEN sd.id IS NOT NULL THEN spr.value
        WHEN b.id  IS NOT NULL THEN vpr.value
        END AS value,
    CASE
        WHEN sd.id  IS NOT NULL THEN sp.vendor_id
        WHEN b.id   IS NOT NULL THEN vp.vendor_id
        END AS vendor_id,
    CASE
        WHEN sd.id  IS NOT NULL THEN LPAD(sbatch.id, 6, 0)
        WHEN b.id   IS NOT NULL THEN null
        END AS invoice_number,
    CASE
        WHEN sd.id  IS NOT NULL THEN spr.currency
        WHEN b.id   IS NOT NULL THEN b.currency
        END AS currency

FROM distribution_beneficiary db
         JOIN assistance a ON a.id=db.assistance_id AND a.assistance_type="distribution"
         JOIN abstract_beneficiary ab ON ab.id=db.beneficiary_id
         JOIN commodity c ON c.assistance_id=db.assistance_id

    -- smartcards
         LEFT JOIN assistance_relief_package pack ON pack.assistance_beneficiary_id=db.id
         LEFT JOIN smartcard_deposit sd ON sd.relief_package_id=pack.id
         LEFT JOIN smartcard_beneficiary s ON s.id=sd.smartcard_id
         LEFT JOIN smartcard_purchase sp ON s.id=sp.smartcard_id
         LEFT JOIN smartcard_purchase_record spr ON sp.id=spr.smartcard_purchase_id
         LEFT JOIN smartcard_redemption_batch sbatch ON sp.redemption_batch_id=sbatch.id

    -- vouchers
         LEFT JOIN booklet b ON b.distribution_beneficiary_id=db.id
         LEFT JOIN voucher v ON v.booklet_id=b.id
         LEFT JOIN voucher_purchase vp ON vp.id=v.voucher_purchase_id
         LEFT JOIN voucher_purchase_record vpr ON vpr.voucher_purchase_id=vp.id

WHERE (spr.id IS NOT NULL OR vpr.id IS NOT NULL)
