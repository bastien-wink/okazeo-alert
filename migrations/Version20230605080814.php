<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230605080814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE annonce_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE game_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE subscription_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE annonce (id INT NOT NULL, game_id INT DEFAULT NULL, url VARCHAR(255) NOT NULL, image_url VARCHAR(1000) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F65593E5E48FD905 ON annonce (game_id)');
        $this->addSql('CREATE TABLE game (id INT NOT NULL, okazeo_name VARCHAR(255) NOT NULL, okazeo_id INT NOT NULL, okazeo_image_url VARCHAR(255) NOT NULL, bgg_name VARCHAR(255) DEFAULT NULL, bgg_weight VARCHAR(255) DEFAULT NULL, bgg_rank VARCHAR(255) DEFAULT NULL, bgg_year_published VARCHAR(255) DEFAULT NULL, bgg_playing_time VARCHAR(255) DEFAULT NULL, bgg_designer VARCHAR(255) DEFAULT NULL, bgg_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE subscription (id INT NOT NULL, key VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, last_annonce_url VARCHAR(255) DEFAULT NULL, filter_zipcode VARCHAR(255) NOT NULL, filter_range VARCHAR(255) NOT NULL, filter_min_rank VARCHAR(255) DEFAULT NULL, filter_min_year VARCHAR(255) DEFAULT NULL, excluded_games JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE annonce ADD CONSTRAINT FK_F65593E5E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE annonce_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE game_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE subscription_id_seq CASCADE');
        $this->addSql('ALTER TABLE annonce DROP CONSTRAINT FK_F65593E5E48FD905');
        $this->addSql('DROP TABLE annonce');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE subscription');
    }
}
