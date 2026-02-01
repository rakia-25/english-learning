<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260201183719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classrooms (id VARCHAR(36) NOT NULL, teacher_id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, code VARCHAR(8) NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_95F95DC277153098 (code), INDEX IDX_95F95DC241807E1D (teacher_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classroom_student (classroom_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL, INDEX IDX_3DD26E1B6278D5A8 (classroom_id), INDEX IDX_3DD26E1BA76ED395 (user_id), PRIMARY KEY(classroom_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE courses (id VARCHAR(36) NOT NULL, classroom_id VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, content LONGTEXT DEFAULT NULL, sort_order INT DEFAULT 0 NOT NULL, is_published TINYINT(1) DEFAULT 0 NOT NULL, published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A9A55A4C6278D5A8 (classroom_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE exams (id VARCHAR(36) NOT NULL, course_id VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, duration INT NOT NULL, passing_score INT NOT NULL, is_published TINYINT(1) DEFAULT 0 NOT NULL, available_from DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_to DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_69311328591CC992 (course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE questions (id VARCHAR(36) NOT NULL, exam_id VARCHAR(36) NOT NULL, question_text LONGTEXT NOT NULL, question_type VARCHAR(30) NOT NULL, points INT NOT NULL, options JSON DEFAULT NULL, correct_answer JSON NOT NULL, sort_order INT DEFAULT 0 NOT NULL, INDEX IDX_8ADC54D5578D5E91 (exam_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_answers (id VARCHAR(36) NOT NULL, attempt_id VARCHAR(36) NOT NULL, question_id VARCHAR(36) NOT NULL, answer JSON NOT NULL, is_correct TINYINT(1) DEFAULT NULL, points_earned DOUBLE PRECISION DEFAULT NULL, INDEX IDX_BDE673FEB191BE6B (attempt_id), INDEX IDX_BDE673FE1E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_exam_attempts (id VARCHAR(36) NOT NULL, student_id VARCHAR(36) NOT NULL, exam_id VARCHAR(36) NOT NULL, started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', submitted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', score DOUBLE PRECISION DEFAULT NULL, is_passed TINYINT(1) DEFAULT NULL, question_order JSON NOT NULL, INDEX IDX_A93F50C9CB944F1A (student_id), INDEX IDX_A93F50C9578D5E91 (exam_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_progress (id VARCHAR(36) NOT NULL, student_id VARCHAR(36) NOT NULL, course_id VARCHAR(36) NOT NULL, completed_lessons JSON DEFAULT \'[]\' NOT NULL, last_accessed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', progress_percentage DOUBLE PRECISION DEFAULT \'0\' NOT NULL, INDEX IDX_918ABEDDCB944F1A (student_id), INDEX IDX_918ABEDD591CC992 (course_id), UNIQUE INDEX student_course_unique (student_id, course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id VARCHAR(36) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, is_verified TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classrooms ADD CONSTRAINT FK_95F95DC241807E1D FOREIGN KEY (teacher_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classroom_student ADD CONSTRAINT FK_3DD26E1B6278D5A8 FOREIGN KEY (classroom_id) REFERENCES classrooms (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classroom_student ADD CONSTRAINT FK_3DD26E1BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE courses ADD CONSTRAINT FK_A9A55A4C6278D5A8 FOREIGN KEY (classroom_id) REFERENCES classrooms (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exams ADD CONSTRAINT FK_69311328591CC992 FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE questions ADD CONSTRAINT FK_8ADC54D5578D5E91 FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_answers ADD CONSTRAINT FK_BDE673FEB191BE6B FOREIGN KEY (attempt_id) REFERENCES student_exam_attempts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_answers ADD CONSTRAINT FK_BDE673FE1E27F6BF FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_exam_attempts ADD CONSTRAINT FK_A93F50C9CB944F1A FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_exam_attempts ADD CONSTRAINT FK_A93F50C9578D5E91 FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_progress ADD CONSTRAINT FK_918ABEDDCB944F1A FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_progress ADD CONSTRAINT FK_918ABEDD591CC992 FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classrooms DROP FOREIGN KEY FK_95F95DC241807E1D');
        $this->addSql('ALTER TABLE classroom_student DROP FOREIGN KEY FK_3DD26E1B6278D5A8');
        $this->addSql('ALTER TABLE classroom_student DROP FOREIGN KEY FK_3DD26E1BA76ED395');
        $this->addSql('ALTER TABLE courses DROP FOREIGN KEY FK_A9A55A4C6278D5A8');
        $this->addSql('ALTER TABLE exams DROP FOREIGN KEY FK_69311328591CC992');
        $this->addSql('ALTER TABLE questions DROP FOREIGN KEY FK_8ADC54D5578D5E91');
        $this->addSql('ALTER TABLE student_answers DROP FOREIGN KEY FK_BDE673FEB191BE6B');
        $this->addSql('ALTER TABLE student_answers DROP FOREIGN KEY FK_BDE673FE1E27F6BF');
        $this->addSql('ALTER TABLE student_exam_attempts DROP FOREIGN KEY FK_A93F50C9CB944F1A');
        $this->addSql('ALTER TABLE student_exam_attempts DROP FOREIGN KEY FK_A93F50C9578D5E91');
        $this->addSql('ALTER TABLE student_progress DROP FOREIGN KEY FK_918ABEDDCB944F1A');
        $this->addSql('ALTER TABLE student_progress DROP FOREIGN KEY FK_918ABEDD591CC992');
        $this->addSql('DROP TABLE classrooms');
        $this->addSql('DROP TABLE classroom_student');
        $this->addSql('DROP TABLE courses');
        $this->addSql('DROP TABLE exams');
        $this->addSql('DROP TABLE questions');
        $this->addSql('DROP TABLE student_answers');
        $this->addSql('DROP TABLE student_exam_attempts');
        $this->addSql('DROP TABLE student_progress');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
