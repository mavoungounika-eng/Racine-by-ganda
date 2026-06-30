<?php

namespace Tests\Unit\Responses;

use App\Http\Responses\PosApiResponse;
use Tests\TestCase;

class PosApiResponseTest extends TestCase
{
    public function test_success_response_has_correct_structure(): void
    {
        $response = PosApiResponse::success(['foo' => 'bar'], 'OK');
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertSame(['foo' => 'bar'], $data['data']);
        $this->assertNull($data['error']);
        $this->assertArrayHasKey('meta', $data);
    }

    public function test_success_response_has_meta_with_request_id_and_timestamp(): void
    {
        $response = PosApiResponse::success();
        $data = $response->getData(true);

        $this->assertArrayHasKey('request_id', $data['meta']);
        $this->assertArrayHasKey('timestamp', $data['meta']);
    }

    public function test_error_response_has_correct_structure(): void
    {
        $response = PosApiResponse::error('POS_ERROR', 'Something failed');
        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertNull($data['data']);
        $this->assertSame('POS_ERROR', $data['error']['code']);
        $this->assertSame('Something failed', $data['error']['message']);
    }

    public function test_error_response_has_error_code(): void
    {
        $response = PosApiResponse::error('INVALID', 'Invalid');
        $data = $response->getData(true);

        $this->assertSame('INVALID', $data['error']['code']);
    }

    public function test_validation_error_returns_422(): void
    {
        $response = PosApiResponse::validationError(['field' => ['required']]);
        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_not_found_returns_404(): void
    {
        $response = PosApiResponse::notFound('Thing');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_unauthorized_returns_401(): void
    {
        $response = PosApiResponse::unauthorized();
        $this->assertSame(401, $response->getStatusCode());
    }
}
