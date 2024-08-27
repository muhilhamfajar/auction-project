<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240824055109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE auto_bid (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, user_id INT NOT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, status SMALLINT NOT NULL, UNIQUE INDEX UNIQ_6A3F5A9ED17F50A6 (uuid), INDEX IDX_6A3F5A9E126F525E (item_id), INDEX IDX_6A3F5A9EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bid (id INT AUTO_INCREMENT NOT NULL, bidder_id INT NOT NULL, item_id INT NOT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, status SMALLINT NOT NULL, bid_time DATETIME DEFAULT NULL, is_auto_bid TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_4AF2B3F3D17F50A6 (uuid), INDEX IDX_4AF2B3F3BE40AFAE (bidder_id), INDEX IDX_4AF2B3F3126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bid_config (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, status SMALLINT NOT NULL, max_bid_amount NUMERIC(10, 2) NOT NULL, bid_alert_percentage INT DEFAULT NULL, reserved_amount NUMERIC(10, 2) DEFAULT NULL, UNIQUE INDEX UNIQ_222C3204D17F50A6 (uuid), UNIQUE INDEX UNIQ_222C3204A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, status SMALLINT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, starting_price NUMERIC(10, 2) DEFAULT NULL, auction_start_time DATETIME DEFAULT NULL, auction_end_time DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_1F1B251ED17F50A6 (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item_media (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, status SMALLINT NOT NULL, name VARCHAR(255) DEFAULT NULL, caption VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_408BBADCD17F50A6 (uuid), INDEX IDX_408BBADC126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE auto_bid ADD CONSTRAINT FK_6A3F5A9E126F525E FOREIGN KEY (item_id) REFERENCES item (id)');
        $this->addSql('ALTER TABLE auto_bid ADD CONSTRAINT FK_6A3F5A9EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE bid ADD CONSTRAINT FK_4AF2B3F3BE40AFAE FOREIGN KEY (bidder_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE bid ADD CONSTRAINT FK_4AF2B3F3126F525E FOREIGN KEY (item_id) REFERENCES item (id)');
        $this->addSql('ALTER TABLE bid_config ADD CONSTRAINT FK_222C3204A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE item_media ADD CONSTRAINT FK_408BBADC126F525E FOREIGN KEY (item_id) REFERENCES item (id)');
        $this->addSql('ALTER TABLE user ADD status SMALLINT NOT NULL, CHANGE uuid uuid VARCHAR(36) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE auto_bid DROP FOREIGN KEY FK_6A3F5A9E126F525E');
        $this->addSql('ALTER TABLE auto_bid DROP FOREIGN KEY FK_6A3F5A9EA76ED395');
        $this->addSql('ALTER TABLE bid DROP FOREIGN KEY FK_4AF2B3F3BE40AFAE');
        $this->addSql('ALTER TABLE bid DROP FOREIGN KEY FK_4AF2B3F3126F525E');
        $this->addSql('ALTER TABLE bid_config DROP FOREIGN KEY FK_222C3204A76ED395');
        $this->addSql('ALTER TABLE item_media DROP FOREIGN KEY FK_408BBADC126F525E');
        $this->addSql('DROP TABLE auto_bid');
        $this->addSql('DROP TABLE bid');
        $this->addSql('DROP TABLE bid_config');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE item_media');
        $this->addSql('ALTER TABLE user DROP status, CHANGE uuid uuid VARCHAR(36) DEFAULT NULL');
    }
}
