<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250402094103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE user_followers (follower_user_id INT NOT NULL, following_user_id INT NOT NULL, PRIMARY KEY(follower_user_id, following_user_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_84E8704370FC2906 ON user_followers (follower_user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_84E870431896F387 ON user_followers (following_user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_followers ADD CONSTRAINT FK_84E8704370FC2906 FOREIGN KEY (follower_user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_followers ADD CONSTRAINT FK_84E870431896F387 FOREIGN KEY (following_user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD language_code VARCHAR(10) DEFAULT 'en' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD timezone VARCHAR(50) DEFAULT 'UTC' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD app_preferences JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD notification_preferences JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD profile_picture_url VARCHAR(1024) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD cover_picture_url VARCHAR(1024) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD short_bio JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD long_bio JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD interests JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD social_links JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_users_language_code ON "user" (language_code)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_followers DROP CONSTRAINT FK_84E8704370FC2906
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_followers DROP CONSTRAINT FK_84E870431896F387
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_followers
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_users_language_code
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP language_code
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP timezone
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP app_preferences
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP notification_preferences
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP profile_picture_url
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP cover_picture_url
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP short_bio
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP long_bio
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP interests
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP social_links
        SQL);
    }
}
