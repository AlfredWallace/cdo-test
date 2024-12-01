<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241201132543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajout de contraintes d'unicité pour le code pour les provider/member et number pour les documents";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FBF094F77153098 ON company (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8698A7696901F54 ON document (number)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_D8698A7696901F54');
        $this->addSql('DROP INDEX UNIQ_4FBF094F77153098');
    }
}
