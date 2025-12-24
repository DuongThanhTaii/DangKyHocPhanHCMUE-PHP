<?php

namespace Tests\Unit\Common;

use PHPUnit\Framework\TestCase;
use App\Application\Common\UseCaseResponse;

/**
 * Unit Tests for UseCaseResponse
 */
class UseCaseResponseTest extends TestCase
{
    public function test_success_response_creation(): void
    {
        $response = UseCaseResponse::success(['foo' => 'bar'], 'Thành công');

        $this->assertTrue($response->isSuccess);
        $this->assertEquals(['foo' => 'bar'], $response->data);
        $this->assertEquals('Thành công', $response->message);
        $this->assertNull($response->errors);
    }

    public function test_fail_response_creation(): void
    {
        $response = UseCaseResponse::fail('Có lỗi xảy ra', ['field' => 'error']);

        $this->assertFalse($response->isSuccess);
        $this->assertNull($response->data);
        $this->assertEquals('Có lỗi xảy ra', $response->message);
        $this->assertEquals(['field' => 'error'], $response->errors);
    }

    public function test_success_with_default_message(): void
    {
        $response = UseCaseResponse::success(['data' => 1]);

        $this->assertTrue($response->isSuccess);
        $this->assertEquals('Thành công', $response->message);
    }

    public function test_to_array_conversion(): void
    {
        $response = UseCaseResponse::success(['id' => 1], 'OK');
        $array = $response->toArray();

        $this->assertArrayHasKey('isSuccess', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertTrue($array['isSuccess']);
    }

    public function test_fail_to_array_includes_errors(): void
    {
        $response = UseCaseResponse::fail('Error', ['code' => 'INVALID']);
        $array = $response->toArray();

        $this->assertArrayHasKey('errors', $array);
        $this->assertEquals(['code' => 'INVALID'], $array['errors']);
    }

    public function test_success_to_array_excludes_errors(): void
    {
        $response = UseCaseResponse::success([]);
        $array = $response->toArray();

        $this->assertArrayNotHasKey('errors', $array);
    }
}
