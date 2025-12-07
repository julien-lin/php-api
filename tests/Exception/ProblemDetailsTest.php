<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Exception;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Exception\ProblemDetails;
use JulienLinard\Api\Exception\ApiException;
use JulienLinard\Api\Exception\ValidationException;
use JulienLinard\Api\Exception\NotFoundException;

class ProblemDetailsTest extends TestCase
{
    public function testConstructor(): void
    {
        $problem = new ProblemDetails(
            type: 'https://example.com/problems/test',
            title: 'Test Error',
            status: 400,
            detail: 'Test detail',
            instance: '/test',
            extensions: ['key' => 'value']
        );
        
        $this->assertEquals('https://example.com/problems/test', $problem->type);
        $this->assertEquals('Test Error', $problem->title);
        $this->assertEquals(400, $problem->status);
        $this->assertEquals('Test detail', $problem->detail);
        $this->assertEquals('/test', $problem->instance);
        $this->assertEquals(['key' => 'value'], $problem->extensions);
    }
    
    public function testToArray(): void
    {
        $problem = new ProblemDetails(
            type: 'https://example.com/problems/test',
            title: 'Test Error',
            status: 400,
            detail: 'Test detail',
            extensions: ['key' => 'value']
        );
        
        $array = $problem->toArray();
        
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('detail', $array);
        $this->assertArrayHasKey('key', $array);
        $this->assertEquals('value', $array['key']);
    }
    
    public function testToArrayWithoutOptionalFields(): void
    {
        $problem = new ProblemDetails(
            type: 'https://example.com/problems/test',
            title: 'Test Error',
            status: 400
        );
        
        $array = $problem->toArray();
        
        $this->assertArrayNotHasKey('detail', $array);
        $this->assertArrayNotHasKey('instance', $array);
    }
    
    public function testFromExceptionApiException(): void
    {
        $exception = new ApiException('Test error', 400);
        $problem = ProblemDetails::fromException($exception);
        
        $this->assertEquals(400, $problem->status);
        $this->assertEquals('Bad Request', $problem->title);
        $this->assertEquals('Test error', $problem->detail);
    }
    
    public function testFromExceptionValidationException(): void
    {
        $violations = [
            ['property' => 'email', 'message' => 'Email invalide'],
        ];
        $exception = new ValidationException($violations);
        $problem = ProblemDetails::fromException($exception);
        
        $this->assertEquals(422, $problem->status);
        $this->assertEquals('Validation Error', $problem->title);
        $this->assertArrayHasKey('violations', $problem->extensions);
        $this->assertEquals($violations, $problem->extensions['violations']);
    }
    
    public function testFromExceptionNotFoundException(): void
    {
        $exception = new NotFoundException('Resource not found');
        $problem = ProblemDetails::fromException($exception);
        
        $this->assertEquals(404, $problem->status);
        $this->assertEquals('Not Found', $problem->title);
    }
    
    public function testFromExceptionGenericException(): void
    {
        $exception = new \RuntimeException('Generic error');
        $problem = ProblemDetails::fromException($exception);
        
        $this->assertEquals(500, $problem->status);
        $this->assertEquals('Internal Server Error', $problem->title);
    }
    
    public function testFromExceptionWithBaseUrl(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/products/123';
        
        $exception = new NotFoundException('Not found');
        $problem = ProblemDetails::fromException($exception, 'https://example.com');
        
        $this->assertEquals('https://example.com/api/products/123', $problem->instance);
        
        unset($_SERVER['REQUEST_URI']);
    }
    
    public function testGetTypeFromStatusCode(): void
    {
        $problem400 = ProblemDetails::fromException(new ApiException('', 400));
        $this->assertStringContainsString('bad-request', $problem400->type);
        
        $problem404 = ProblemDetails::fromException(new ApiException('', 404));
        $this->assertStringContainsString('not-found', $problem404->type);
        
        $problem422 = ProblemDetails::fromException(new ApiException('', 422));
        $this->assertStringContainsString('validation-error', $problem422->type);
        
        $problem500 = ProblemDetails::fromException(new ApiException('', 500));
        $this->assertStringContainsString('internal-server-error', $problem500->type);
    }
}
