#
# Table structure for table 'tx_forms2db_domain_model_mail'
#
CREATE TABLE tx_forms2db_domain_model_mail (
	persistence_id varchar(255) DEFAULT '' NOT NULL,
	form_id varchar(255) DEFAULT '' NOT NULL,
    plugin_id int(11) NOT NULL,
    mail text
);
