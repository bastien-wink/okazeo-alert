<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230703222459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('ALTER TABLE game RENAME COLUMN okazeo_name TO okkazeo_name');
        $this->addSql('ALTER TABLE game RENAME COLUMN okazeo_image_url TO okkazeo_image_url');
        $this->addSql('ALTER TABLE game RENAME COLUMN okazeo_id TO okkazeo_id');
    }

    public function down(Schema $schema): void
    {

    }
}
