CREATE VIEW view_distributed_item AS
SELECT pack.id,
       db.beneficiary_id,
       db.assistance_id,
       a.project_id,
       a.location_id,
       CASE
           WHEN ab.bnf_type = 'hh' THEN 'Household'
           WHEN ab.bnf_type = 'bnf' THEN 'Beneficiary'
           WHEN ab.bnf_type = 'inst' THEN 'Institution'
           WHEN ab.bnf_type = 'comm' THEN 'Community'
           END            AS bnf_type,
       c.id               as commodity_id,
       pack.modality_type as modality_type,
       pack.distributedAt AS date_distribution,
       CASE
           WHEN sd.id IS NOT NULL THEN s.code
           WHEN b.id IS NOT NULL THEN b.code
           END            AS carrier_number,
       CASE
           WHEN sd.id IS NOT NULL THEN sd.value
           WHEN t.id IS NOT NULL THEN CAST(REGEXP_SUBSTR(t.amount_sent, '[0-9]+(\.[0-9]+)?') AS DECIMAL)
           WHEN b.id IS NOT NULL THEN b.value
           WHEN pack.amount_distributed > 0 THEN pack.amount_distributed
           END            AS amount,
       pack.amount_spent  as spent,
       CASE
           WHEN sd.distributed_by_id IS NOT NULL THEN sd.distributed_by_id
           WHEN t.id IS NOT NULL THEN t.sent_by_id
           ELSE pack.distributed_by_id
           END            AS field_officer_id

FROM distribution_beneficiary db
         JOIN assistance_relief_package pack ON pack.assistance_beneficiary_id = db.id AND pack.amount_distributed > 0
         JOIN assistance a ON a.id = db.assistance_id AND a.assistance_type = 'distribution'
         JOIN abstract_beneficiary ab ON ab.id = db.beneficiary_id
         JOIN commodity c ON c.assistance_id = db.assistance_id

    -- smartcards
         LEFT JOIN smartcard_deposit sd ON sd.relief_package_id = pack.id
         LEFT JOIN smartcard s ON s.id = sd.smartcard_id

    -- mobile money
         LEFT JOIN transaction t ON t.relief_package_id = pack.id and t.transaction_status = 1

    -- booklets
         LEFT JOIN (SELECT b.id,
                           b.code,
                           b.relief_package_id,
                           MAX(vp.used_at) AS used_at,
                           SUM(vpr.value)  AS value
                    FROM booklet b
                             JOIN voucher v ON v.booklet_id = b.id
                             LEFT OUTER JOIN voucher_purchase vp ON vp.id = v.voucher_purchase_id
                             LEFT OUTER JOIN voucher_purchase_record vpr ON vpr.voucher_purchase_id = vp.id
                    WHERE b.relief_package_id IS NOT NULL
                    GROUP BY b.id, b.code, b.relief_package_id) AS b ON b.relief_package_id = pack.id;
