INSERT kbp_user SET `id` = 2, `first_name` = 'Admin', `last_name` = 'Demo', `email` = 'admin@demo.com', `username` = 'admin', `password` = MD5('demo'), `date_registered` = NOW(), `active` = 1;
INSERT kbp_priv SET `user_id` = 2, `priv_name_id` = 1, `grantor` = 2, `timestamp` = NOW();