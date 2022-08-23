CREATE VIEW view_assistance_statistics AS
select a.id                                                        as assistance_id,
       count(distinct db.beneficiary_id)                           as number_of_beneficiaries,
       sum(IF(rp.state != 'Canceled', rp.amount_to_distribute, 0)) as amount_total,
       sum(IF(rp.state != 'Canceled', rp.amount_distributed, 0))   as amount_distributed,
       sum(IF(rp.state != 'Canceled', rp.amount_distributed, 0))   as amount_used,
       sum(IF(rp.state != 'Canceled', rp.amount_distributed, 0))   as amount_sent,
       sum(IF(rp.state != 'Canceled', rp.amount_distributed, 0))   as amount_picked_up
from assistance a
         left join distribution_beneficiary db on a.id = db.assistance_id
         left join assistance_relief_package rp on db.id = rp.assistance_beneficiary_id
group by a.id
order by a.id

