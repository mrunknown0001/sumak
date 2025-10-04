<?php

namespace App\Http\Controllers\OpenAi;

use App\Http\Controllers\Controller;
use App\Models\ChatGptApiLog;
use App\Services\OpenAiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiUsageController extends Controller
{
    public function __construct(private OpenAiService $openAiService) {}

    /**
     * Get usage statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->input('user_id', auth()->id());
        $dateFrom = $request->input('date_from');

        // Only allow users to see their own stats unless admin
        if ($userId != auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = $this->openAiService->getUserApiStats($userId, $dateFrom);

        return response()->json(['data' => $stats]);
    }

    /**
     * Get API logs
     */
    public function logs(Request $request): JsonResponse
    {
        $query = ChatGptApiLog::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc');

        if ($request->has('request_type')) {
            $query->where('request_type', $request->input('request_type'));
        }

        if ($request->has('success')) {
            $query->where('success', $request->boolean('success'));
        }

        $logs = $query->paginate($request->input('per_page', 15));

        return response()->json($logs);
    }

    /**
     * Get cost breakdown
     */
    public function costBreakdown(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $period = $request->input('period', 'month'); // today, week, month, year

        $dateRange = match($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $breakdown = ChatGptApiLog::where('user_id', $userId)
            ->whereBetween('created_at', $dateRange)
            ->selectRaw('
                request_type,
                COUNT(*) as count,
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as total_cost,
                AVG(response_time_ms) as avg_response_time
            ')
            ->groupBy('request_type')
            ->get();

        return response()->json([
            'period' => $period,
            'date_range' => [
                'from' => $dateRange[0]->toIso8601String(),
                'to' => $dateRange[1]->toIso8601String(),
            ],
            'breakdown' => $breakdown,
            'total_cost' => $breakdown->sum('total_cost'),
            'total_requests' => $breakdown->sum('count'),
        ]);
    }

    /**
     * Export usage data
     */
    public function export(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $format = $request->input('format', 'csv'); // csv, json

        $logs = ChatGptApiLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($format === 'json') {
            return response()->json(['data' => $logs]);
        }

        // CSV export
        $filename = 'openai_usage_' . now()->format('Y-m-d') . '.csv';
        
        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'ID', 'Request Type', 'Model', 'Total Tokens', 
                'Cost', 'Response Time (ms)', 'Success', 'Created At'
            ]);
            
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->request_type,
                    $log->model,
                    $log->total_tokens,
                    $log->estimated_cost,
                    $log->response_time_ms,
                    $log->success ? 'Yes' : 'No',
                    $log->created_at->toDateTimeString(),
                ]);
            }
            
            fclose($handle);
        }, $filename);
    }
}