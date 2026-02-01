<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Exam;
use App\Entity\Question;
use App\Entity\StudentExamAttempt;
use App\Entity\User;

/**
 * Génère une tentative d'examen avec questions et options (QCM) randomisées.
 * Ne persiste pas : le contrôleur doit persister l'attempt.
 */
class ExamRandomizerService
{
    /**
     * 1. Récupère les questions, les mélange
     * 2. Pour chaque QCM, mélange les options et mémorise l'ordre
     * 3. Crée StudentExamAttempt avec questionOrder (id + options_order pour QCM)
     * 4. Retourne l'attempt (à persister par l'appelant)
     */
    public function generateAttempt(Exam $exam, User $student): StudentExamAttempt
    {
        $questions = $exam->getQuestions()->toArray();
        shuffle($questions);

        $questionOrder = [];
        foreach ($questions as $question) {
            $item = ['id' => $question->getId()];
            if ($question->getQuestionType() === Question::TYPE_MULTIPLE_CHOICE && $question->getOptions() !== null) {
                $options = $question->getOptions();
                $indices = array_keys($options);
                shuffle($indices);
                $item['options_order'] = $indices;
            }
            $questionOrder[] = $item;
        }

        $attempt = new StudentExamAttempt();
        $attempt->setStudent($student);
        $attempt->setExam($exam);
        $attempt->setQuestionOrder($questionOrder);

        return $attempt;
    }
}
