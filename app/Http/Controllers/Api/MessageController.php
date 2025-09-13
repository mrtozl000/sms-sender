<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListMessagesRequest;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="SMS Sender API",
 *     version="1.0.0",
 *     description="API for managing and sending SMS messages"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8080/api",
 *     description="Local Development Server"
 * )
 */
class MessageController extends Controller
{
    protected MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * @OA\Get(
     *     path="/messages/sent",
     *     summary="Get list of sent messages",
     *     description="Retrieve a paginated list of sent messages with their external message IDs",
     *     operationId="getSentMessages",
     *     tags={"Messages"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="phone_number",
     *         in="query",
     *         description="Filter by phone number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter messages sent from this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter messages sent until this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="phone_number", type="string"),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="message_id", type="string"),
     *                     @OA\Property(property="sent_at", type="string", format="date-time"),
     *                     @OA\Property(property="cached_data", type="object", nullable=true)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function getSentMessages(ListMessagesRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $messages = $this->messageService->getSentMessages($filters);

        // Transform the data to include cached information
        $transformedData = $messages->through(function ($message) {
            $cachedData = $this->messageService->getCachedMessageData($message->id);

            return [
                'id' => $message->id,
                'phone_number' => $message->phone_number,
                'content' => $message->content,
                'message_id' => $message->message_id,
                'sent_at' => $message->sent_at?->toIso8601String(),
                'cached_data' => $cachedData,
            ];
        });

        return response()->json([
            'data' => $transformedData->items(),
            'meta' => [
                'current_page' => $transformedData->currentPage(),
                'total' => $transformedData->total(),
                'per_page' => $transformedData->perPage(),
                'last_page' => $transformedData->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/messages",
     *     summary="Create a new message",
     *     description="Create a new message to be sent",
     *     operationId="createMessage",
     *     tags={"Messages"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_number", "content"},
     *             @OA\Property(property="phone_number", type="string", example="+905551234567"),
     *             @OA\Property(property="content", type="string", example="Test message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:20',
            'content' => 'required|string|max:' . config('sms.max_length'),
        ]);

        try {
            $message = $this->messageService->createMessage($validated);

            return response()->json([
                'message' => 'Message created successfully',
                'data' => $message,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
