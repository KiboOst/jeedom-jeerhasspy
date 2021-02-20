CREATE TABLE IF NOT EXISTS `jeerhasspy_intent`
(
    `id`            int(11)       NOT NULL AUTO_INCREMENT,
    `name`          TEXT          NULL,
    `isEnable`      TINYINT(1)    NULL,
    `configuration` TEXT          NULL,
    `isInteract`    TINYINT(1)    NULL,
    `scenario`      TEXT          NULL,
    `tags`          TEXT          NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
