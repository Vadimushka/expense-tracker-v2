<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSplitRequest;
use App\Models\Expense;
use App\Services\SplitService;
use Illuminate\Http\JsonResponse;

class SplitController extends Controller
{
    public function __construct(private SplitService $splitService) {}

    public function index(): JsonResponse
    {
        return response()->json($this->splitService->getAll());
    }

    public function store(StoreSplitRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->splitService->save($validated['expense_id'], $validated['parts']);

        return response()->json(['message' => 'ok']);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->splitService->delete($expense);

        return response()->json(status: 204);
    }
}
