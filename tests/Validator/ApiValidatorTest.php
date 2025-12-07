<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Validator;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Validator\ApiValidator;
use JulienLinard\Api\Exception\ValidationException;
use JulienLinard\Api\Annotation\ApiProperty;
use JulienLinard\Api\Annotation\ApiResource;

#[ApiResource]
class TestValidatableEntity
{
    #[ApiProperty(required: true, groups: ['write'])]
    public string $name;
    
    #[ApiProperty(required: false, groups: ['write'])]
    public ?string $description = null;
    
    #[ApiProperty(required: true, groups: ['write'])]
    public int $count;
    
    #[ApiProperty(required: false, groups: ['write'])]
    public bool $active = false;
}

class ApiValidatorTest extends TestCase
{
    private ApiValidator $validator;
    
    protected function setUp(): void
    {
        $this->validator = new ApiValidator();
    }
    
    public function testValidateSuccess(): void
    {
        $data = [
            'name' => 'Test',
            'count' => 10,
        ];
        
        $this->validator->validate($data, TestValidatableEntity::class, ['write', 'Default']);
        
        $this->assertTrue(true); // Si pas d'exception, validation réussie
    }
    
    public function testValidateMissingRequiredField(): void
    {
        $this->expectException(ValidationException::class);
        
        $data = [
            'count' => 10,
            // 'name' manquant
        ];
        
        $this->validator->validate($data, TestValidatableEntity::class, ['write', 'Default']);
    }
    
    public function testValidateWrongType(): void
    {
        $this->expectException(ValidationException::class);
        
        $data = [
            'name' => 'Test',
            'count' => 'not-an-int', // Doit être un int
        ];
        
        $this->validator->validate($data, TestValidatableEntity::class, ['write', 'Default']);
    }
    
    public function testValidateWithOptionalField(): void
    {
        $data = [
            'name' => 'Test',
            'count' => 10,
            'description' => 'Optional description',
        ];
        
        $this->validator->validate($data, TestValidatableEntity::class, ['write', 'Default']);
        
        $this->assertTrue(true);
    }
    
    public function testValidateWithGroups(): void
    {
        $data = [
            'name' => 'Test',
            'count' => 10,
        ];
        
        // Validation avec groupe 'write' - name est requis
        $this->validator->validate($data, TestValidatableEntity::class, ['write']);
        
        $this->assertTrue(true);
    }
    
    public function testValidateViolations(): void
    {
        try {
            $data = [
                'count' => 'invalid',
            ];
            
            $this->validator->validate($data, TestValidatableEntity::class, ['write', 'Default']);
            $this->fail('ValidationException attendue');
        } catch (ValidationException $e) {
            $violations = $e->getViolations();
            
            $this->assertNotEmpty($violations);
            $this->assertArrayHasKey('property', $violations[0]);
            $this->assertArrayHasKey('message', $violations[0]);
        }
    }
}
