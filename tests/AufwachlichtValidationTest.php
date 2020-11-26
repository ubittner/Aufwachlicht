<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class AufwachlichtValidationTest extends TestCaseSymconValidation
{
    public function testValidateAufwachlicht(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateAufwachlichtModule(): void
    {
        $this->validateModule(__DIR__ . '/../Aufwachlicht');
    }
}