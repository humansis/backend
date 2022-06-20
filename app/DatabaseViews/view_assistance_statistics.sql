CREATE VIEW view_assistance_statistics AS
select
    a.id as                                 assistance_id,
    count(distinct db.beneficiary_id) as    number_of_beneficiaries,
    sum(rp.amount_to_distribute) as         amount_total,
    sum(rp.amount_distributed) as           amount_distributed,
    sum(rp.amount_distributed) as           amount_used,
    sum(rp.amount_distributed) as           amount_sent,
    sum(rp.amount_distributed) as           amount_picked_up
from assistance a
         left join distribution_beneficiary db on a.id = db.assistance_id
         left join assistance_relief_package rp on db.id = rp.assistance_beneficiary_id
where rp.state != 'Canceled'
group by a.id
order by a.id

