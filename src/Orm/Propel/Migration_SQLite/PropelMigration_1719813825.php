<?php
use Propel\Generator\Manager\MigrationManager;

/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version 1719813825.
 * Generated on 2024-07-01 06:03:45 by reneklatt */
class PropelMigration_1719813825{
    /**
     * @var string
     */
    public $comment = '';

    /**
     * @param \Propel\Generator\Manager\MigrationManager $manager
     *
     * @return null|false|void
     */
    public function preUp(MigrationManager $manager)
    {
        // add the pre-migration code here
    }

    /**
     * @param \Propel\Generator\Manager\MigrationManager $manager
     *
     * @return null|false|void
     */
    public function postUp(MigrationManager $manager)
    {
        // add the post-migration code here
    }

    /**
     * @param \Propel\Generator\Manager\MigrationManager $manager
     *
     * @return null|false|void
     */
    public function preDown(MigrationManager $manager)
    {
        // add the pre-migration code here
    }

    /**
     * @param \Propel\Generator\Manager\MigrationManager $manager
     *
     * @return null|false|void
     */
    public function postDown(MigrationManager $manager)
    {
        // add the post-migration code here
    }

    /**
     * Get the SQL statements for the Up migration
     *
     * @return array list of the SQL strings to execute for the Up migration
     *               the keys being the datasources
     */
    public function getUpSQL(): array
    {
        $connection_zed = <<< 'EOT'

PRAGMA foreign_keys = OFF;

CREATE TEMPORARY TABLE [spy_app_config__temp__668246c1ad18c] AS SELECT [id_app_config],[tenant_identifier],[is_active],[config],[status],[created_at],[updated_at] FROM [spy_app_config];
DROP TABLE [spy_app_config];

CREATE TABLE [spy_app_config]
(
    [id_app_config] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [tenant_identifier] VARCHAR(255) NOT NULL,
    [is_active] INTEGER DEFAULT 0 NOT NULL,
    [config] MEDIUMTEXT NOT NULL,
    [status] TINYINT DEFAULT 0 NOT NULL,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([tenant_identifier]),
    UNIQUE ([id_app_config])
);

INSERT INTO [spy_app_config] (id_app_config, tenant_identifier, is_active, config, status, created_at, updated_at) SELECT id_app_config, tenant_identifier, is_active, config, status, created_at, updated_at FROM [spy_app_config__temp__668246c1ad18c];
DROP TABLE [spy_app_config__temp__668246c1ad18c];

CREATE TEMPORARY TABLE [spy_currency_store__temp__668246c1ad1ff] AS SELECT [id_currency_store],[fk_currency],[fk_store] FROM [spy_currency_store];
DROP TABLE [spy_currency_store];

CREATE TABLE [spy_currency_store]
(
    [id_currency_store] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_currency] INTEGER NOT NULL,
    [fk_store] INTEGER NOT NULL,
    UNIQUE ([fk_currency],[fk_store]),
    UNIQUE ([id_currency_store]),
    FOREIGN KEY ([fk_currency]) REFERENCES [spy_currency] ([id_currency]),
    FOREIGN KEY ([fk_store]) REFERENCES [spy_store] ([id_store])
);

CREATE INDEX [index-spy_currency_store-fk_currency] ON [spy_currency_store] ([fk_currency]);

CREATE INDEX [index-spy_currency_store-fk_store] ON [spy_currency_store] ([fk_store]);

INSERT INTO [spy_currency_store] (id_currency_store, fk_currency, fk_store) SELECT id_currency_store, fk_currency, fk_store FROM [spy_currency_store__temp__668246c1ad1ff];
DROP TABLE [spy_currency_store__temp__668246c1ad1ff];

CREATE TEMPORARY TABLE [spy_glossary_key__temp__668246c1ad265] AS SELECT [id_glossary_key],[key],[is_active] FROM [spy_glossary_key];
DROP TABLE [spy_glossary_key];

CREATE TABLE [spy_glossary_key]
(
    [id_glossary_key] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [key] VARCHAR(255) NOT NULL,
    [is_active] INTEGER DEFAULT 1 NOT NULL,
    UNIQUE ([key]),
    UNIQUE ([id_glossary_key])
);

CREATE INDEX [spy_glossary_key-index-key] ON [spy_glossary_key] ([key]);

CREATE INDEX [spy_glossary_key-is_active] ON [spy_glossary_key] ([is_active]);

INSERT INTO [spy_glossary_key] (id_glossary_key, key, is_active) SELECT id_glossary_key, key, is_active FROM [spy_glossary_key__temp__668246c1ad265];
DROP TABLE [spy_glossary_key__temp__668246c1ad265];

CREATE TEMPORARY TABLE [spy_glossary_storage__temp__668246c1ad2bb] AS SELECT [id_glossary_storage],[fk_glossary_key],[glossary_key],[locale],[data],[key],[alias_keys],[created_at],[updated_at] FROM [spy_glossary_storage];
DROP TABLE [spy_glossary_storage];

CREATE TABLE [spy_glossary_storage]
(
    [id_glossary_storage] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_glossary_key] INTEGER NOT NULL,
    [glossary_key] VARCHAR(255) NOT NULL,
    [locale] VARCHAR(5) NOT NULL,
    [data] MEDIUMTEXT,
    [key] VARCHAR(1024) NOT NULL,
    [alias_keys] VARCHAR(255),
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([alias_keys]),
    UNIQUE ([id_glossary_storage])
);

CREATE INDEX [spy_glossary_storage-fk_glossary_key] ON [spy_glossary_storage] ([fk_glossary_key]);

INSERT INTO [spy_glossary_storage] (id_glossary_storage, fk_glossary_key, glossary_key, locale, data, key, alias_keys, created_at, updated_at) SELECT id_glossary_storage, fk_glossary_key, glossary_key, locale, data, key, alias_keys, created_at, updated_at FROM [spy_glossary_storage__temp__668246c1ad2bb];
DROP TABLE [spy_glossary_storage__temp__668246c1ad2bb];

CREATE TEMPORARY TABLE [spy_glossary_translation__temp__668246c1ad330] AS SELECT [id_glossary_translation],[fk_glossary_key],[fk_locale],[value],[is_active] FROM [spy_glossary_translation];
DROP TABLE [spy_glossary_translation];

CREATE TABLE [spy_glossary_translation]
(
    [id_glossary_translation] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_glossary_key] INTEGER NOT NULL,
    [fk_locale] INTEGER NOT NULL,
    [value] MEDIUMTEXT NOT NULL,
    [is_active] INTEGER DEFAULT 1 NOT NULL,
    UNIQUE ([fk_glossary_key],[fk_locale]),
    UNIQUE ([id_glossary_translation]),
    FOREIGN KEY ([fk_glossary_key]) REFERENCES [spy_glossary_key] ([id_glossary_key])
        ON DELETE CASCADE,
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale])
        ON DELETE CASCADE
);

CREATE INDEX [spy_glossary_translation-index-fk_locale] ON [spy_glossary_translation] ([fk_locale]);

CREATE INDEX [spy_glossary_translation-is_active] ON [spy_glossary_translation] ([is_active]);

INSERT INTO [spy_glossary_translation] (id_glossary_translation, fk_glossary_key, fk_locale, value, is_active) SELECT id_glossary_translation, fk_glossary_key, fk_locale, value, is_active FROM [spy_glossary_translation__temp__668246c1ad330];
DROP TABLE [spy_glossary_translation__temp__668246c1ad330];

CREATE TEMPORARY TABLE [spy_locale__temp__668246c1ad39d] AS SELECT [id_locale],[locale_name],[is_active] FROM [spy_locale];
DROP TABLE [spy_locale];

CREATE TABLE [spy_locale]
(
    [id_locale] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [locale_name] VARCHAR(5) NOT NULL,
    [is_active] INTEGER DEFAULT 1 NOT NULL,
    UNIQUE ([locale_name]),
    UNIQUE ([id_locale])
);

CREATE INDEX [spy_locale-index-locale_name] ON [spy_locale] ([locale_name]);

INSERT INTO [spy_locale] (id_locale, locale_name, is_active) SELECT id_locale, locale_name, is_active FROM [spy_locale__temp__668246c1ad39d];
DROP TABLE [spy_locale__temp__668246c1ad39d];

CREATE TEMPORARY TABLE [spy_locale_store__temp__668246c1ad3de] AS SELECT [id_locale_store],[fk_locale],[fk_store] FROM [spy_locale_store];
DROP TABLE [spy_locale_store];

CREATE TABLE [spy_locale_store]
(
    [id_locale_store] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_locale] INTEGER NOT NULL,
    [fk_store] INTEGER NOT NULL,
    UNIQUE ([fk_locale],[fk_store]),
    UNIQUE ([id_locale_store]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale]),
    FOREIGN KEY ([fk_store]) REFERENCES [spy_store] ([id_store])
);

CREATE INDEX [index-spy_locale_store-fk_locale] ON [spy_locale_store] ([fk_locale]);

CREATE INDEX [index-spy_locale_store-fk_store] ON [spy_locale_store] ([fk_store]);

INSERT INTO [spy_locale_store] (id_locale_store, fk_locale, fk_store) SELECT id_locale_store, fk_locale, fk_store FROM [spy_locale_store__temp__668246c1ad3de];
DROP TABLE [spy_locale_store__temp__668246c1ad3de];

CREATE TEMPORARY TABLE [spy_payment__temp__668246c1ad442] AS SELECT [id_payment],[order_reference],[transaction_id],[tenant_identifier],[quote],[status],[redirect_success_url],[redirect_cancel_url],[created_at],[updated_at] FROM [spy_payment];
DROP TABLE [spy_payment];

CREATE TABLE [spy_payment]
(
    [id_payment] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [order_reference] CHAR(36),
    [transaction_id] CHAR(36),
    [tenant_identifier] CHAR(60),
    [quote] MEDIUMTEXT,
    [status] CHAR(64),
    [redirect_success_url] MEDIUMTEXT,
    [redirect_cancel_url] MEDIUMTEXT,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([order_reference],[tenant_identifier]),
    UNIQUE ([transaction_id]),
    UNIQUE ([id_payment])
);

INSERT INTO [spy_payment] (id_payment, order_reference, transaction_id, tenant_identifier, quote, status, redirect_success_url, redirect_cancel_url, created_at, updated_at) SELECT id_payment, order_reference, transaction_id, tenant_identifier, quote, status, redirect_success_url, redirect_cancel_url, created_at, updated_at FROM [spy_payment__temp__668246c1ad442];
DROP TABLE [spy_payment__temp__668246c1ad442];

CREATE TEMPORARY TABLE [spy_payment_refund__temp__668246c1ad4be] AS SELECT [id_payment_refund],[transaction_id],[refund_id],[status],[amount],[currency_code],[order_item_ids],[created_at],[updated_at] FROM [spy_payment_refund];
DROP TABLE [spy_payment_refund];

CREATE TABLE [spy_payment_refund]
(
    [id_payment_refund] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [transaction_id] CHAR(36),
    [refund_id] CHAR(36),
    [status] VARCHAR(255) NOT NULL,
    [amount] INTEGER NOT NULL,
    [currency_code] VARCHAR(10) NOT NULL,
    [order_item_ids] MEDIUMTEXT,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([id_payment_refund]),
    FOREIGN KEY ([transaction_id]) REFERENCES [spy_payment] ([transaction_id])
        ON DELETE CASCADE
);

CREATE INDEX [spy_payment_refund-search_index] ON [spy_payment_refund] ([transaction_id],[status],[order_item_ids]);

INSERT INTO [spy_payment_refund] (id_payment_refund, transaction_id, refund_id, status, amount, currency_code, order_item_ids, created_at, updated_at) SELECT id_payment_refund, transaction_id, refund_id, status, amount, currency_code, order_item_ids, created_at, updated_at FROM [spy_payment_refund__temp__668246c1ad4be];
DROP TABLE [spy_payment_refund__temp__668246c1ad4be];

CREATE TEMPORARY TABLE [spy_queue_process__temp__668246c1ad53f] AS SELECT [id_queue_process],[server_id],[process_pid],[worker_pid],[queue_name],[created_at],[updated_at] FROM [spy_queue_process];
DROP TABLE [spy_queue_process];

CREATE TABLE [spy_queue_process]
(
    [id_queue_process] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [server_id] VARCHAR(255) NOT NULL,
    [process_pid] INTEGER NOT NULL,
    [worker_pid] INTEGER NOT NULL,
    [queue_name] VARCHAR(255) NOT NULL,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([server_id],[process_pid],[queue_name]),
    UNIQUE ([id_queue_process])
);

CREATE INDEX [spy_queue_process-index-key] ON [spy_queue_process] ([server_id],[queue_name]);

INSERT INTO [spy_queue_process] (id_queue_process, server_id, process_pid, worker_pid, queue_name, created_at, updated_at) SELECT id_queue_process, server_id, process_pid, worker_pid, queue_name, created_at, updated_at FROM [spy_queue_process__temp__668246c1ad53f];
DROP TABLE [spy_queue_process__temp__668246c1ad53f];

CREATE TEMPORARY TABLE [spy_store__temp__668246c1ad5a0] AS SELECT [id_store],[fk_currency],[fk_locale],[name] FROM [spy_store];
DROP TABLE [spy_store];

CREATE TABLE [spy_store]
(
    [id_store] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_currency] INTEGER,
    [fk_locale] INTEGER,
    [name] VARCHAR(255),
    UNIQUE ([id_store]),
    FOREIGN KEY ([fk_currency]) REFERENCES [spy_currency] ([id_currency]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale])
);

CREATE INDEX [index-spy_store-fk_currency] ON [spy_store] ([fk_currency]);

CREATE INDEX [index-spy_store-fk_locale] ON [spy_store] ([fk_locale]);

INSERT INTO [spy_store] (id_store, fk_currency, fk_locale, name) SELECT id_store, fk_currency, fk_locale, name FROM [spy_store__temp__668246c1ad5a0];
DROP TABLE [spy_store__temp__668246c1ad5a0];

CREATE TEMPORARY TABLE [spy_touch__temp__668246c1ad600] AS SELECT [id_touch],[item_type],[item_event],[item_id],[touched] FROM [spy_touch];
DROP TABLE [spy_touch];

CREATE TABLE [spy_touch]
(
    [id_touch] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [item_type] VARCHAR(255) NOT NULL,
    [item_event] TINYINT NOT NULL,
    [item_id] INTEGER NOT NULL,
    [touched] TIMESTAMP NOT NULL,
    UNIQUE ([item_id],[item_event],[item_type]),
    UNIQUE ([id_touch])
);

CREATE INDEX [spy_touch-index-item_id] ON [spy_touch] ([item_id]);

CREATE INDEX [index_spy_touch-item_event_item_type_touched] ON [spy_touch] ([item_event],[item_type],[touched]);

INSERT INTO [spy_touch] (id_touch, item_type, item_event, item_id, touched) SELECT id_touch, item_type, item_event, item_id, touched FROM [spy_touch__temp__668246c1ad600];
DROP TABLE [spy_touch__temp__668246c1ad600];

CREATE TEMPORARY TABLE [spy_touch_search__temp__668246c1ad659] AS SELECT [id_touch_search],[fk_locale],[fk_store],[fk_touch],[key] FROM [spy_touch_search];
DROP TABLE [spy_touch_search];

CREATE TABLE [spy_touch_search]
(
    [id_touch_search] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_locale] INTEGER NOT NULL,
    [fk_store] INTEGER,
    [fk_touch] INTEGER NOT NULL,
    [key] VARCHAR(255) NOT NULL,
    UNIQUE ([fk_locale],[key]),
    UNIQUE ([id_touch_search]),
    FOREIGN KEY ([fk_touch]) REFERENCES [spy_touch] ([id_touch]),
    FOREIGN KEY ([fk_store]) REFERENCES [spy_store] ([id_store]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale])
);

CREATE INDEX [spy_touch_search-index-key] ON [spy_touch_search] ([key]);

INSERT INTO [spy_touch_search] (id_touch_search, fk_locale, fk_store, fk_touch, key) SELECT id_touch_search, fk_locale, fk_store, fk_touch, key FROM [spy_touch_search__temp__668246c1ad659];
DROP TABLE [spy_touch_search__temp__668246c1ad659];

CREATE TEMPORARY TABLE [spy_touch_storage__temp__668246c1ad6c7] AS SELECT [id_touch_storage],[fk_locale],[fk_store],[fk_touch],[key] FROM [spy_touch_storage];
DROP TABLE [spy_touch_storage];

CREATE TABLE [spy_touch_storage]
(
    [id_touch_storage] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_locale] INTEGER NOT NULL,
    [fk_store] INTEGER,
    [fk_touch] INTEGER NOT NULL,
    [key] VARCHAR(255) NOT NULL,
    UNIQUE ([fk_locale],[key]),
    UNIQUE ([id_touch_storage]),
    FOREIGN KEY ([fk_touch]) REFERENCES [spy_touch] ([id_touch]),
    FOREIGN KEY ([fk_store]) REFERENCES [spy_store] ([id_store]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale])
);

CREATE INDEX [spy_touch_storage-index-key] ON [spy_touch_storage] ([key]);

INSERT INTO [spy_touch_storage] (id_touch_storage, fk_locale, fk_store, fk_touch, key) SELECT id_touch_storage, fk_locale, fk_store, fk_touch, key FROM [spy_touch_storage__temp__668246c1ad6c7];
DROP TABLE [spy_touch_storage__temp__668246c1ad6c7];

PRAGMA foreign_keys = ON;
EOT;

        return [
            'zed' => $connection_zed,
        ];
    }

    /**
     * Get the SQL statements for the Down migration
     *
     * @return array list of the SQL strings to execute for the Down migration
     *               the keys being the datasources
     */
    public function getDownSQL(): array
    {
        $connection_zed = <<< 'EOT'

PRAGMA foreign_keys = OFF;

CREATE TEMPORARY TABLE [spy_app_config__temp__668246c1ad7dc] AS SELECT [id_app_config],[tenant_identifier],[is_active],[config],[status],[created_at],[updated_at] FROM [spy_app_config];
DROP TABLE [spy_app_config];

CREATE TABLE [spy_app_config]
(
    [id_app_config] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [tenant_identifier] VARCHAR(255) NOT NULL,
    [is_active] INTEGER DEFAULT 0 NOT NULL,
    [config] MEDIUMTEXT NOT NULL,
    [status] TINYINT DEFAULT 0 NOT NULL,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([tenant_identifier]),
    UNIQUE ([id_app_config])
);

INSERT INTO [spy_app_config] (id_app_config, tenant_identifier, is_active, config, status, created_at, updated_at) SELECT id_app_config, tenant_identifier, is_active, config, status, created_at, updated_at FROM [spy_app_config__temp__668246c1ad7dc];
DROP TABLE [spy_app_config__temp__668246c1ad7dc];

CREATE TEMPORARY TABLE [spy_currency_store__temp__668246c1ad83e] AS SELECT [id_currency_store],[fk_currency],[fk_store] FROM [spy_currency_store];
DROP TABLE [spy_currency_store];

CREATE TABLE [spy_currency_store]
(
    [id_currency_store] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_currency] INTEGER NOT NULL,
    [fk_store] INTEGER NOT NULL,
    UNIQUE ([fk_currency],[fk_store]),
    UNIQUE ([id_currency_store]),
    FOREIGN KEY ([fk_store]) REFERENCES [spy_store] ([id_store]),
    FOREIGN KEY ([fk_currency]) REFERENCES [spy_currency] ([id_currency])
);

CREATE INDEX [index-spy_currency_store-fk_store] ON [spy_currency_store] ([fk_store]);

CREATE INDEX [index-spy_currency_store-fk_currency] ON [spy_currency_store] ([fk_currency]);

INSERT INTO [spy_currency_store] (id_currency_store, fk_currency, fk_store) SELECT id_currency_store, fk_currency, fk_store FROM [spy_currency_store__temp__668246c1ad83e];
DROP TABLE [spy_currency_store__temp__668246c1ad83e];

CREATE TEMPORARY TABLE [spy_glossary_key__temp__668246c1ad894] AS SELECT [id_glossary_key],[key],[is_active] FROM [spy_glossary_key];
DROP TABLE [spy_glossary_key];

CREATE TABLE [spy_glossary_key]
(
    [id_glossary_key] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [key] VARCHAR(255) NOT NULL,
    [is_active] INTEGER DEFAULT 1 NOT NULL,
    UNIQUE ([key]),
    UNIQUE ([id_glossary_key])
);

CREATE INDEX [spy_glossary_key-is_active] ON [spy_glossary_key] ([is_active]);

CREATE INDEX [spy_glossary_key-index-key] ON [spy_glossary_key] ([key]);

INSERT INTO [spy_glossary_key] (id_glossary_key, key, is_active) SELECT id_glossary_key, key, is_active FROM [spy_glossary_key__temp__668246c1ad894];
DROP TABLE [spy_glossary_key__temp__668246c1ad894];

CREATE TEMPORARY TABLE [spy_glossary_storage__temp__668246c1ad8e5] AS SELECT [id_glossary_storage],[fk_glossary_key],[glossary_key],[locale],[data],[key],[alias_keys],[created_at],[updated_at] FROM [spy_glossary_storage];
DROP TABLE [spy_glossary_storage];

CREATE TABLE [spy_glossary_storage]
(
    [id_glossary_storage] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_glossary_key] INTEGER NOT NULL,
    [glossary_key] VARCHAR(255) NOT NULL,
    [locale] VARCHAR(5) NOT NULL,
    [data] MEDIUMTEXT,
    [key] VARCHAR(1024) NOT NULL,
    [alias_keys] VARCHAR(255),
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([alias_keys]),
    UNIQUE ([id_glossary_storage])
);

CREATE INDEX [spy_glossary_storage-fk_glossary_key] ON [spy_glossary_storage] ([fk_glossary_key]);

INSERT INTO [spy_glossary_storage] (id_glossary_storage, fk_glossary_key, glossary_key, locale, data, key, alias_keys, created_at, updated_at) SELECT id_glossary_storage, fk_glossary_key, glossary_key, locale, data, key, alias_keys, created_at, updated_at FROM [spy_glossary_storage__temp__668246c1ad8e5];
DROP TABLE [spy_glossary_storage__temp__668246c1ad8e5];

CREATE TEMPORARY TABLE [spy_glossary_translation__temp__668246c1ad955] AS SELECT [id_glossary_translation],[fk_glossary_key],[fk_locale],[value],[is_active] FROM [spy_glossary_translation];
DROP TABLE [spy_glossary_translation];

CREATE TABLE [spy_glossary_translation]
(
    [id_glossary_translation] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_glossary_key] INTEGER NOT NULL,
    [fk_locale] INTEGER NOT NULL,
    [value] MEDIUMTEXT NOT NULL,
    [is_active] INTEGER DEFAULT 1 NOT NULL,
    UNIQUE ([fk_glossary_key],[fk_locale]),
    UNIQUE ([id_glossary_translation]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale])
        ON DELETE CASCADE,
    FOREIGN KEY ([fk_glossary_key]) REFERENCES [spy_glossary_key] ([id_glossary_key])
        ON DELETE CASCADE
);

CREATE INDEX [spy_glossary_translation-is_active] ON [spy_glossary_translation] ([is_active]);

CREATE INDEX [spy_glossary_translation-index-fk_locale] ON [spy_glossary_translation] ([fk_locale]);

INSERT INTO [spy_glossary_translation] (id_glossary_translation, fk_glossary_key, fk_locale, value, is_active) SELECT id_glossary_translation, fk_glossary_key, fk_locale, value, is_active FROM [spy_glossary_translation__temp__668246c1ad955];
DROP TABLE [spy_glossary_translation__temp__668246c1ad955];

CREATE TEMPORARY TABLE [spy_locale__temp__668246c1ad9be] AS SELECT [id_locale],[locale_name],[is_active] FROM [spy_locale];
DROP TABLE [spy_locale];

CREATE TABLE [spy_locale]
(
    [id_locale] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [locale_name] VARCHAR(5) NOT NULL,
    [is_active] INTEGER DEFAULT 1 NOT NULL,
    UNIQUE ([locale_name]),
    UNIQUE ([id_locale])
);

CREATE INDEX [spy_locale-index-locale_name] ON [spy_locale] ([locale_name]);

INSERT INTO [spy_locale] (id_locale, locale_name, is_active) SELECT id_locale, locale_name, is_active FROM [spy_locale__temp__668246c1ad9be];
DROP TABLE [spy_locale__temp__668246c1ad9be];

CREATE TEMPORARY TABLE [spy_locale_store__temp__668246c1ad9fd] AS SELECT [id_locale_store],[fk_locale],[fk_store] FROM [spy_locale_store];
DROP TABLE [spy_locale_store];

CREATE TABLE [spy_locale_store]
(
    [id_locale_store] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_locale] INTEGER NOT NULL,
    [fk_store] INTEGER NOT NULL,
    UNIQUE ([fk_locale],[fk_store]),
    UNIQUE ([id_locale_store]),
    FOREIGN KEY ([fk_store]) REFERENCES [spy_store] ([id_store]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale])
);

CREATE INDEX [index-spy_locale_store-fk_store] ON [spy_locale_store] ([fk_store]);

CREATE INDEX [index-spy_locale_store-fk_locale] ON [spy_locale_store] ([fk_locale]);

INSERT INTO [spy_locale_store] (id_locale_store, fk_locale, fk_store) SELECT id_locale_store, fk_locale, fk_store FROM [spy_locale_store__temp__668246c1ad9fd];
DROP TABLE [spy_locale_store__temp__668246c1ad9fd];

CREATE TEMPORARY TABLE [spy_payment__temp__668246c1ada5f] AS SELECT [id_payment],[order_reference],[transaction_id],[tenant_identifier],[quote],[status],[redirect_success_url],[redirect_cancel_url],[created_at],[updated_at] FROM [spy_payment];
DROP TABLE [spy_payment];

CREATE TABLE [spy_payment]
(
    [id_payment] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [order_reference] CHAR(36),
    [transaction_id] CHAR(36),
    [tenant_identifier] CHAR(60),
    [quote] MEDIUMTEXT,
    [status] CHAR(64),
    [redirect_success_url] MEDIUMTEXT,
    [redirect_cancel_url] MEDIUMTEXT,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([transaction_id]),
    UNIQUE ([order_reference],[tenant_identifier]),
    UNIQUE ([id_payment])
);

INSERT INTO [spy_payment] (id_payment, order_reference, transaction_id, tenant_identifier, quote, status, redirect_success_url, redirect_cancel_url, created_at, updated_at) SELECT id_payment, order_reference, transaction_id, tenant_identifier, quote, status, redirect_success_url, redirect_cancel_url, created_at, updated_at FROM [spy_payment__temp__668246c1ada5f];
DROP TABLE [spy_payment__temp__668246c1ada5f];

CREATE TEMPORARY TABLE [spy_payment_refund__temp__668246c1adad9] AS SELECT [id_payment_refund],[transaction_id],[refund_id],[status],[amount],[currency_code],[order_item_ids],[created_at],[updated_at] FROM [spy_payment_refund];
DROP TABLE [spy_payment_refund];

CREATE TABLE [spy_payment_refund]
(
    [id_payment_refund] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [transaction_id] CHAR(36),
    [refund_id] CHAR(36),
    [status] VARCHAR(255) NOT NULL,
    [amount] INTEGER NOT NULL,
    [currency_code] VARCHAR(10) NOT NULL,
    [order_item_ids] MEDIUMTEXT,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([id_payment_refund]),
    FOREIGN KEY ([transaction_id]) REFERENCES [spy_payment] ([transaction_id])
        ON DELETE CASCADE
);

CREATE INDEX [spy_payment_refund-search_index] ON [spy_payment_refund] ([transaction_id],[status],[order_item_ids]);

INSERT INTO [spy_payment_refund] (id_payment_refund, transaction_id, refund_id, status, amount, currency_code, order_item_ids, created_at, updated_at) SELECT id_payment_refund, transaction_id, refund_id, status, amount, currency_code, order_item_ids, created_at, updated_at FROM [spy_payment_refund__temp__668246c1adad9];
DROP TABLE [spy_payment_refund__temp__668246c1adad9];

CREATE TEMPORARY TABLE [spy_queue_process__temp__668246c1adb57] AS SELECT [id_queue_process],[server_id],[process_pid],[worker_pid],[queue_name],[created_at],[updated_at] FROM [spy_queue_process];
DROP TABLE [spy_queue_process];

CREATE TABLE [spy_queue_process]
(
    [id_queue_process] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [server_id] VARCHAR(255) NOT NULL,
    [process_pid] INTEGER NOT NULL,
    [worker_pid] INTEGER NOT NULL,
    [queue_name] VARCHAR(255) NOT NULL,
    [created_at] TIMESTAMP,
    [updated_at] TIMESTAMP,
    UNIQUE ([server_id],[process_pid],[queue_name]),
    UNIQUE ([id_queue_process])
);

CREATE INDEX [spy_queue_process-index-key] ON [spy_queue_process] ([server_id],[queue_name]);

INSERT INTO [spy_queue_process] (id_queue_process, server_id, process_pid, worker_pid, queue_name, created_at, updated_at) SELECT id_queue_process, server_id, process_pid, worker_pid, queue_name, created_at, updated_at FROM [spy_queue_process__temp__668246c1adb57];
DROP TABLE [spy_queue_process__temp__668246c1adb57];

CREATE TEMPORARY TABLE [spy_store__temp__668246c1adbb7] AS SELECT [id_store],[fk_currency],[fk_locale],[name] FROM [spy_store];
DROP TABLE [spy_store];

CREATE TABLE [spy_store]
(
    [id_store] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_currency] INTEGER,
    [fk_locale] INTEGER,
    [name] VARCHAR(255),
    UNIQUE ([id_store]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale]),
    FOREIGN KEY ([fk_currency]) REFERENCES [spy_currency] ([id_currency])
);

CREATE INDEX [index-spy_store-fk_locale] ON [spy_store] ([fk_locale]);

CREATE INDEX [index-spy_store-fk_currency] ON [spy_store] ([fk_currency]);

INSERT INTO [spy_store] (id_store, fk_currency, fk_locale, name) SELECT id_store, fk_currency, fk_locale, name FROM [spy_store__temp__668246c1adbb7];
DROP TABLE [spy_store__temp__668246c1adbb7];

CREATE TEMPORARY TABLE [spy_touch__temp__668246c1adc16] AS SELECT [id_touch],[item_type],[item_event],[item_id],[touched] FROM [spy_touch];
DROP TABLE [spy_touch];

CREATE TABLE [spy_touch]
(
    [id_touch] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [item_type] VARCHAR(255) NOT NULL,
    [item_event] TINYINT NOT NULL,
    [item_id] INTEGER NOT NULL,
    [touched] TIMESTAMP NOT NULL,
    UNIQUE ([item_id],[item_event],[item_type]),
    UNIQUE ([id_touch])
);

CREATE INDEX [index_spy_touch-item_event_item_type_touched] ON [spy_touch] ([item_event],[item_type],[touched]);

CREATE INDEX [spy_touch-index-item_id] ON [spy_touch] ([item_id]);

INSERT INTO [spy_touch] (id_touch, item_type, item_event, item_id, touched) SELECT id_touch, item_type, item_event, item_id, touched FROM [spy_touch__temp__668246c1adc16];
DROP TABLE [spy_touch__temp__668246c1adc16];

CREATE TEMPORARY TABLE [spy_touch_search__temp__668246c1adc6d] AS SELECT [id_touch_search],[fk_locale],[fk_store],[fk_touch],[key] FROM [spy_touch_search];
DROP TABLE [spy_touch_search];

CREATE TABLE [spy_touch_search]
(
    [id_touch_search] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_locale] INTEGER NOT NULL,
    [fk_store] INTEGER,
    [fk_touch] INTEGER NOT NULL,
    [key] VARCHAR(255) NOT NULL,
    UNIQUE ([fk_locale],[key]),
    UNIQUE ([id_touch_search]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale]),
    FOREIGN KEY ([fk_store]) REFERENCES [spy_store] ([id_store]),
    FOREIGN KEY ([fk_touch]) REFERENCES [spy_touch] ([id_touch])
);

CREATE INDEX [spy_touch_search-index-key] ON [spy_touch_search] ([key]);

INSERT INTO [spy_touch_search] (id_touch_search, fk_locale, fk_store, fk_touch, key) SELECT id_touch_search, fk_locale, fk_store, fk_touch, key FROM [spy_touch_search__temp__668246c1adc6d];
DROP TABLE [spy_touch_search__temp__668246c1adc6d];

CREATE TEMPORARY TABLE [spy_touch_storage__temp__668246c1adcdd] AS SELECT [id_touch_storage],[fk_locale],[fk_store],[fk_touch],[key] FROM [spy_touch_storage];
DROP TABLE [spy_touch_storage];

CREATE TABLE [spy_touch_storage]
(
    [id_touch_storage] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [fk_locale] INTEGER NOT NULL,
    [fk_store] INTEGER,
    [fk_touch] INTEGER NOT NULL,
    [key] VARCHAR(255) NOT NULL,
    UNIQUE ([fk_locale],[key]),
    UNIQUE ([id_touch_storage]),
    FOREIGN KEY ([fk_locale]) REFERENCES [spy_locale] ([id_locale]),
    FOREIGN KEY ([fk_store]) REFERENCES [spy_store] ([id_store]),
    FOREIGN KEY ([fk_touch]) REFERENCES [spy_touch] ([id_touch])
);

CREATE INDEX [spy_touch_storage-index-key] ON [spy_touch_storage] ([key]);

INSERT INTO [spy_touch_storage] (id_touch_storage, fk_locale, fk_store, fk_touch, key) SELECT id_touch_storage, fk_locale, fk_store, fk_touch, key FROM [spy_touch_storage__temp__668246c1adcdd];
DROP TABLE [spy_touch_storage__temp__668246c1adcdd];

PRAGMA foreign_keys = ON;
EOT;

        return [
            'zed' => $connection_zed,
        ];
    }

}