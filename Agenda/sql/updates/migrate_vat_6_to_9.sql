-- Zet oude 6%-regels om naar 9% (alleen als je 6% nog hebt)
UPDATE invoice_items
SET vat_rate = 0.09
WHERE ABS(vat_rate - 0.06) < 0.0001;
