<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Exception;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Exception\ApiException;
use JulienLinard\Api\Exception\NotFoundException;

class ApiExceptionTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new ApiException('Test error', 400);
        
        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertEquals(400, $exception->getStatusCode());
    }
    
    public function testGetStatusCode(): void
    {
        $exception = new ApiException('Error', 404);
        
        $this->assertEquals(404, $exception->getStatusCode());
    }
    
    public function testDefaultStatusCode(): void
    {
        $exception = new ApiException('Error');
        
        $this->assertEquals(500, $exception->getStatusCode());
    }
    
    public function testWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new ApiException('Error', 500, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
    
    public function testNotFoundException(): void
    {
        $exception = new NotFoundException('Resource not found');
        
        $this->assertEquals('Resource not found', $exception->getMessage());
        $this->assertEquals(404, $exception->getStatusCode());
    }
}
