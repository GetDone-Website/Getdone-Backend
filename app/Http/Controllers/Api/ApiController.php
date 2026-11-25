<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, string $message = 'Berhasil.', int $status = 200): JsonResponse
    { return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status); }
}
