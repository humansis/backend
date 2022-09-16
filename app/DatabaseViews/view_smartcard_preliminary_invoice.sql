CREATE VIEW view_smartcard_preliminary_invoice AS
SELECT IF(
           a.project_id IS NOT NULL,
           CONCAT(spa.vendor_id, "_", spa.currency, "_", a.project_id),
           CONCAT(spa.vendor_id, "_", spa.currency, "_", "NULL")
           )                    AS id,
       a.project_id             as project_id,
       spa.currency             as currency,
       spa.vendor_id            as vendor_id,
       SUM(spa.value)           as value,
       JSON_ARRAYAGG(spa.spaid) as purchase_ids,
       COUNT(spa.spaid)         as purchase_count
FROM (
    SELECT sp.id            as spaid,
             sp.assistance_id as sp_ass,
             SUM(spr.value)   as value,
             spr.currency     as currency,
             sp.vendor_id     as vendor_id

    FROM smartcard_purchase AS sp
    INNER JOIN smartcard_purchase_record AS spr ON sp.id = spr.smartcard_purchase_id
      WHERE sp.redemption_batch_id IS NULL
        AND vendor_id IS NOT NULL
        AND currency IS NOT NULL
      GROUP BY spr.currency, sp.id
) spa
    LEFT JOIN assistance a on spa.sp_ass = a.id
GROUP BY spa.currency, a.project_id, spa.vendor_id
ORDER BY spa.currency, a.project_id, spa.vendor_id;
