-- Automations articles
--
INSERT INTO {prefix}trigger VALUES
(NULL,1,2,'outdated_article','','',2,'a:2:{i:1;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:1:"1";}}i:2;a:2:{s:4:"item";s:12:"date_updated";s:4:"rule";a:3:{i:0;s:4:"more";i:1;s:3:"180";i:2;s:15:"period_old_days";}}}','a:2:{i:1;a:2:{s:4:"item";s:5:"email";s:4:"rule";a:1:{i:0;s:6:"author";}}i:2;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:1:"4";}}}','',1,0),
(NULL,1,2,'outdated_article_grouped','','',2,'a:2:{i:1;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:1:"1";}}i:2;a:2:{s:4:"item";s:12:"date_updated";s:4:"rule";a:3:{i:0;s:4:"more";i:1;s:3:"180";i:2;s:15:"period_old_days";}}}','a:2:{i:1;a:2:{s:4:"item";s:18:"email_user_grouped";s:4:"rule";a:1:{i:0;s:6:"author";}}i:2;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:1:"4";}}}','',2,0);

-- Automations files
--
INSERT INTO {prefix}trigger VALUES 
(NULL,2,2,'outdated_file','','',2,'a:2:{i:1;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:1:"1";}}i:2;a:2:{s:4:"item";s:12:"date_updated";s:4:"rule";a:3:{i:0;s:4:"more";i:1;s:3:"180";i:2;s:15:"period_old_days";}}}','a:2:{i:1;a:2:{s:4:"item";s:5:"email";s:4:"rule";a:1:{i:0;s:6:"author";}}i:2;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:1:"4";}}}','',1,0), 
(NULL,2,2,'outdated_file_grouped','','',2,'a:2:{i:1;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:1:"1";}}i:2;a:2:{s:4:"item";s:12:"date_updated";s:4:"rule";a:3:{i:0;s:4:"more";i:1;s:3:"180";i:2;s:15:"period_old_days";}}}','a:2:{i:1;a:2:{s:4:"item";s:18:"email_user_grouped";s:4:"rule";a:1:{i:0;s:6:"author";}}i:2;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:1:"4";}}}','',2,0);

-- Workflow articles
--
INSERT INTO {prefix}trigger VALUES
(NULL,1,4,'approval_category_admin','','',2,'a:1:{i:2;a:2:{s:4:"item";s:15:"privilege_level";s:4:"rule";a:2:{i:0;s:5:"equal";i:1;s:1:"5";}}}','a:1:{i:1;a:3:{s:5:"title";s:0:"";s:4:"item";s:6:"assign";s:4:"rule";a:1:{i:0;s:14:"category_admin";}}}','',5,1);

-- -- Workflow files
--
INSERT INTO {prefix}trigger VALUES
(NULL,2,4,'approval_category_admin','','',2,'a:1:{i:2;a:2:{s:4:"item";s:15:"privilege_level";s:4:"rule";a:2:{i:0;s:5:"equal";i:1;s:1:"5";}}}','a:1:{i:1;a:3:{s:5:"title";s:0:"";s:4:"item";s:6:"assign";s:4:"rule";a:1:{i:0;s:14:"category_admin";}}}','',5,1);
