<?php

namespace App\Enums;

enum InterviewType: string
{
    case PhoneScreen = 'phone_screen';
    case Technical = 'technical';
    case Behavioral = 'behavioral';
    case Panel = 'panel';
    case Final = 'final';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PhoneScreen => 'Phone Screen',
            self::Technical => 'Technical',
            self::Behavioral => 'Behavioral',
            self::Panel => 'Panel',
            self::Final => 'Final',
            self::Other => 'Other',
        };
    }
}
