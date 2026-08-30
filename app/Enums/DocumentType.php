<?php

namespace App\Enums;

enum DocumentType: string
{
    case Resume = 'resume';
    case Contract = 'contract';
    case GovernmentId = 'government_id';
    case Certificate = 'certificate';
    case MedicalRecord = 'medical_record';
    case Other = 'other';
}
