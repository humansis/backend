CREATE VIEW view_smartcard_preliminary_invoice AS
SELECT IF(a.project_id IS NOT NULL, CONCAT(sp.vendor_id, "_", spr.currency, "_", a.project_id),
          CONCAT(sp.vendor_id, "_", spr.currency, "_", "NULL")) AS id,
       a.project_id                                           as project_id,
       spr.currency                                           as currency,
       sp.vendor_id                                           as vendor_id,
       SUM(spr.value)                                         as value,
       GROUP_CONCAT(DISTINCT sp.id)                           as purchase_ids,
       COUNT(DISTINCT sp.id)                                  as purchase_count
FROM smartcard_purchase AS sp
         INNER JOIN smartcard_purchase_record AS spr ON sp.id = spr.smartcard_purchase_id
         LEFT JOIN assistance a on sp.assistance_id = a.id
WHERE sp.redemption_batch_id IS NULL
  AND vendor_id IS NOT NULL
  AND currency IS NOT NULL
GROUP BY spr.currency, a.project_id, sp.vendor_id
ORDER BY spr.currency, a.project_id, sp.vendor_id
