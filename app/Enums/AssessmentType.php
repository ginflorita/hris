<?php

namespace App\Enums;

enum AssessmentType: string
{
    case Technical = 'technical';
    case Coding = 'coding';
    case Personality = 'personality';
    case CaseStudy = 'case_study';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'Technical',
            self::Coding => 'Coding',
            self::Personality => 'Personality',
            self::CaseStudy => 'Case Study',
            self::Other => 'Other',
        };
    }
}
