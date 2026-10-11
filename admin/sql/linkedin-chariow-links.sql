-- Configure checkout links using stable package slugs rather than hard-coded IDs.
INSERT INTO linkedin_settings (setting_key, setting_value)
SELECT CONCAT('chariow_url_', id), CASE slug
 WHEN 'essentiel' THEN 'https://boutique.hiremeguide.com/prd_zvevvoid'
 WHEN 'pro' THEN 'https://boutique.hiremeguide.com/prd_w08gjak7'
 WHEN 'premium' THEN 'https://boutique.hiremeguide.com/prd_u06y9fgw'
END
FROM linkedin_packages WHERE slug IN ('essentiel','pro','premium')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
