<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211225115806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE answers (id INT AUTO_INCREMENT NOT NULL, related_question_id INT NOT NULL, editor_id INT NOT NULL, content LONGTEXT NOT NULL, INDEX IDX_50D0C606D5CC883B (related_question_id), INDEX IDX_50D0C6066995AC4C (editor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE answers_likes (answer_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_3916CBAEAA334807 (answer_id), INDEX IDX_3916CBAEA76ED395 (user_id), PRIMARY KEY(answer_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE answers_dislikes (answer_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_6BADC7F9AA334807 (answer_id), INDEX IDX_6BADC7F9A76ED395 (user_id), PRIMARY KEY(answer_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE faqs (id INT AUTO_INCREMENT NOT NULL, moderator_id INT DEFAULT NULL, name VARCHAR(45) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_8934BEE5D0AFA354 (moderator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE questions (id INT AUTO_INCREMENT NOT NULL, creator_id INT NOT NULL, title VARCHAR(45) NOT NULL, content LONGTEXT NOT NULL, creation_date DATETIME NOT NULL, INDEX IDX_8ADC54D561220EA6 (creator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE questions_belongings (question_id INT NOT NULL, faq_id INT NOT NULL, INDEX IDX_B9990E961E27F6BF (question_id), INDEX IDX_B9990E9681BEC8C2 (faq_id), PRIMARY KEY(question_id, faq_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE questions_likes (question_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_CCB2DE011E27F6BF (question_id), INDEX IDX_CCB2DE01A76ED395 (user_id), PRIMARY KEY(question_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE questions_dislikes (question_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5F875CB41E27F6BF (question_id), INDEX IDX_5F875CB4A76ED395 (user_id), PRIMARY KEY(question_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_requests (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_16646B41A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE skills (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(45) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(45) NOT NULL, last_name VARCHAR(45) NOT NULL, first_name VARCHAR(45) NOT NULL, occupation VARCHAR(45) NOT NULL, profile_picture VARCHAR(200) NOT NULL, cv VARCHAR(200) NOT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shared_skills (user_id INT NOT NULL, skill_id INT NOT NULL, INDEX IDX_B6C91444A76ED395 (user_id), INDEX IDX_B6C914445585C142 (skill_id), PRIMARY KEY(user_id, skill_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE answers ADD CONSTRAINT FK_50D0C606D5CC883B FOREIGN KEY (related_question_id) REFERENCES questions (id)');
        $this->addSql('ALTER TABLE answers ADD CONSTRAINT FK_50D0C6066995AC4C FOREIGN KEY (editor_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE answers_likes ADD CONSTRAINT FK_3916CBAEAA334807 FOREIGN KEY (answer_id) REFERENCES answers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE answers_likes ADD CONSTRAINT FK_3916CBAEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE answers_dislikes ADD CONSTRAINT FK_6BADC7F9AA334807 FOREIGN KEY (answer_id) REFERENCES answers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE answers_dislikes ADD CONSTRAINT FK_6BADC7F9A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE faqs ADD CONSTRAINT FK_8934BEE5D0AFA354 FOREIGN KEY (moderator_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE questions ADD CONSTRAINT FK_8ADC54D561220EA6 FOREIGN KEY (creator_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE questions_belongings ADD CONSTRAINT FK_B9990E961E27F6BF FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questions_belongings ADD CONSTRAINT FK_B9990E9681BEC8C2 FOREIGN KEY (faq_id) REFERENCES faqs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questions_likes ADD CONSTRAINT FK_CCB2DE011E27F6BF FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questions_likes ADD CONSTRAINT FK_CCB2DE01A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questions_dislikes ADD CONSTRAINT FK_5F875CB41E27F6BF FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questions_dislikes ADD CONSTRAINT FK_5F875CB4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_requests ADD CONSTRAINT FK_16646B41A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE shared_skills ADD CONSTRAINT FK_B6C91444A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shared_skills ADD CONSTRAINT FK_B6C914445585C142 FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE answers_likes DROP FOREIGN KEY FK_3916CBAEAA334807');
        $this->addSql('ALTER TABLE answers_dislikes DROP FOREIGN KEY FK_6BADC7F9AA334807');
        $this->addSql('ALTER TABLE questions_belongings DROP FOREIGN KEY FK_B9990E9681BEC8C2');
        $this->addSql('ALTER TABLE answers DROP FOREIGN KEY FK_50D0C606D5CC883B');
        $this->addSql('ALTER TABLE questions_belongings DROP FOREIGN KEY FK_B9990E961E27F6BF');
        $this->addSql('ALTER TABLE questions_likes DROP FOREIGN KEY FK_CCB2DE011E27F6BF');
        $this->addSql('ALTER TABLE questions_dislikes DROP FOREIGN KEY FK_5F875CB41E27F6BF');
        $this->addSql('ALTER TABLE shared_skills DROP FOREIGN KEY FK_B6C914445585C142');
        $this->addSql('ALTER TABLE answers DROP FOREIGN KEY FK_50D0C6066995AC4C');
        $this->addSql('ALTER TABLE answers_likes DROP FOREIGN KEY FK_3916CBAEA76ED395');
        $this->addSql('ALTER TABLE answers_dislikes DROP FOREIGN KEY FK_6BADC7F9A76ED395');
        $this->addSql('ALTER TABLE faqs DROP FOREIGN KEY FK_8934BEE5D0AFA354');
        $this->addSql('ALTER TABLE questions DROP FOREIGN KEY FK_8ADC54D561220EA6');
        $this->addSql('ALTER TABLE questions_likes DROP FOREIGN KEY FK_CCB2DE01A76ED395');
        $this->addSql('ALTER TABLE questions_dislikes DROP FOREIGN KEY FK_5F875CB4A76ED395');
        $this->addSql('ALTER TABLE reset_password_requests DROP FOREIGN KEY FK_16646B41A76ED395');
        $this->addSql('ALTER TABLE shared_skills DROP FOREIGN KEY FK_B6C91444A76ED395');
        $this->addSql('DROP TABLE answers');
        $this->addSql('DROP TABLE answers_likes');
        $this->addSql('DROP TABLE answers_dislikes');
        $this->addSql('DROP TABLE faqs');
        $this->addSql('DROP TABLE questions');
        $this->addSql('DROP TABLE questions_belongings');
        $this->addSql('DROP TABLE questions_likes');
        $this->addSql('DROP TABLE questions_dislikes');
        $this->addSql('DROP TABLE reset_password_requests');
        $this->addSql('DROP TABLE skills');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE shared_skills');
    }
}
