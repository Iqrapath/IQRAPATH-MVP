<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class SearchController extends Controller
{
    public function __construct(
        private MessageService $messageService
    ) {}

    /**
     * Search messages by content.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $results = $this->messageService->searchMessages(
            $request->user(),
            $request->query,
            $request->input('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Search conversations by participant name.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function searchByParticipant(Request $request): JsonResponse
    {
        $request->validate([
            'participant_name' => 'required|string|min:2',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $results = $this->messageService->searchByParticipant(
            $request->user(),
            $request->participant_name,
            $request->input('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Search messages by date range.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function searchByDateRange(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $results = $this->messageService->searchByDateRange(
            $request->user(),
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date),
            $request->input('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
