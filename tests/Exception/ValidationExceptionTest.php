<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Exception;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Exception\ValidationException;

class ValidationExceptionTest extends TestCase
{
    public function testConstructorWithViolations(): void
    {
        $violations = [
            ['property' => 'email', 'message' => 'Email invalide'],
            ['property' => 'name', 'message' => 'Nom requis'],
        ];
        
        $exception = new ValidationException($violations);
        
        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals($violations, $exception->getViolations());
    }
    
    public function testConstructorWithoutViolations(): void
    {
        $exception = new ValidationException();
        
        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEmpty($exception->getViolations());
    }
    
    public function testAddViolation(): void
    {
        $exception = new ValidationException();
        
        $exception->addViolation('email', 'Email invalide');
        $exception->addViolation('name', 'Nom requis');
        
        $violations = $exception->getViolations();
        
        $this->assertCount(2, $violations);
        $this->assertEquals('email', $violations[0]['property']);
        $this->assertEquals('Email invalide', $violations[0]['message']);
    }
    
    public function testGetViolations(): void
    {
        $violations = [
            ['property' => 'test', 'message' => 'Test message'],
        ];
        
        $exception = new ValidationException($violations);
        
        $this->assertEquals($violations, $exception->getViolations());
    }
}
